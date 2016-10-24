<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gendoria\CommandQueueDoctrineDriverBundle\Worker;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\LockMode;
use Exception;
use Gendoria\CommandQueue\Worker\WorkerRunnerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of DoctrineWorkerRunner
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class DoctrineWorkerRunner implements WorkerRunnerInterface
{

    /**
     * Number of maximum consecutive failed fetch operations before worker will fail permanently.
     * 
     * @var integer
     */
    const MAX_FAILED_FETCHES = 10;
    
    /**
     * Maximum process failures before command is discarded.
     * 
     * @var integer
     */
    const MAX_FAILED_PROCESS_ATTEMPTS = 10;
    
    /**
     * Doctrine worker.
     * 
     * @var DoctrineWorker
     */
    private $worker;

    /**
     * Database connection.
     * 
     * @var Connection
     */
    private $connection;

    /**
     * Table name.
     * 
     * @var string
     */
    private $tableName;

    /**
     * Pool.
     * 
     * @var string
     */
    private $pool;

    /**
     * Logger.
     * 
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Number of consecutive failed fetches.
     * 
     * @var integer
     */
    private $failedFetches = 0;

    /**
     * Class constructor.
     * 
     * @param DoctrineWorker $worker
     * @param Connection $connection
     * @param string $tableName
     * @param string $pool
     */
    function __construct(DoctrineWorker $worker, Connection $connection, $tableName, $pool, LoggerInterface $logger = null)
    {
        $this->worker = $worker;
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->pool = $pool;
        $this->logger = $logger ? $logger : new NullLogger();
    }

    public function run(array $options, OutputInterface $output = null)
    {
        $output->writeln(sprintf("Worker run with options: %s", print_r($options, true)));
        $this->logger->debug(sprintf("Worker run with options: %s", print_r($options, true)));
        
        $this->connection->setAutoCommit(false);
        $this->connection->setTransactionIsolation(Connection::TRANSACTION_SERIALIZABLE);
        
        while ($this->runIteration($output)) {
            $this->logger->debug('Doctrine worker tick.');
        }
    }
    
    private function runIteration(OutputInterface $output)
    {
        try {
            $commandData = $this->fetchNext($output);
            if (is_array($commandData)) {
                $this->processNext($commandData, $output);
            } else {
                $output->writeln("No messages to process yet.", OutputInterface::VERBOSITY_DEBUG);
                //Sleep to prevent hammering database
                $this->sleep();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Fetch next command data to process.
     * 
     * @param OutputInterface $output
     * @return array|false
     * @throws Exception\FetchException Thrown, when fetch failed permanently.
     */
    private function fetchNext(OutputInterface $output)
    {
        try {
            //Get one new row for update
            return $this->fetchNextCommandData();
        } catch (Exception\FetchException $e) {
            $this->logger->error('Exception while processing message.', array($e));
            $output->writeln(sprintf("<error>Error when processing message: %s</error>\n%s", $e->getMessage(), $e->getTraceAsString()));
            if ($this->failedFetches > self::MAX_FAILED_FETCHES) {
                $output->writeln("<error>Too many consequent fetch errors - exiting.</error>");
                throw $e;
            }
            $this->sleep();
            return false;
        }
    }

    private function processNext($commandData, OutputInterface $output)
    {
        try {
            $output->writeln("Processing command.", OutputInterface::VERBOSITY_DEBUG);
            $this->worker->process($commandData);
            $this->connection->delete($this->tableName, array('id' => $commandData['id']));
        } catch (\Exception $e) {
            $this->logger->error('Exception while processing message.', array($e));
            $output->writeln(sprintf("<error>Error when processing message: %s</error>\n%s", $e->getMessage(), $e->getTraceAsString()));
            if ($commandData['failed_no'] >= self::MAX_FAILED_PROCESS_ATTEMPTS-1) {
                $this->logger->error('Command failed too many times, discarding.');
                $output->writeln(sprintf("<error>Command failed too many times, discarding.</error>"));
                $this->connection->delete($this->tableName, array('id' => $commandData['id']));
            } else {
                $this->updateProcessed($commandData['id'], false, $commandData['failed_no']++);
            }
        }
    }

    /**
     * Fetch next command data.
     * 
     * @return array|boolean Array with command data, if there are pending commands; false otherwise.
     * @throws Exception\FetchException Thrown, when fetch resulted in an error (lock impossible).
     */
    private function fetchNextCommandData()
    {
        $platform = $this->connection->getDatabasePlatform();
        try {
            $this->connection->beginTransaction();
            $sqlProto = "SELECT * "
                . " FROM " . $platform->appendLockHint($this->tableName, LockMode::PESSIMISTIC_WRITE)
                . " WHERE processed = ? AND pool = ? "
            ;
            $sql = $platform->modifyLimitQuery($sqlProto, 1) . " " . $platform->getWriteLockSQL();
            $row = $this->connection->fetchAssoc($sql, array(0, $this->pool));

            if (!$row) {
                $this->connection->commit();
                return $row;
            }

            $rows = $this->updateProcessed($row['id'], true, $row['failed_no']);

            if ($rows !== 1) {
                throw new DBALException("Race condition detected. Aborting.");
            }
            $this->connection->commit();
            $this->failedFetches = 0;
            return $row;
        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->failedFetches++;
            throw new Exception\FetchException("Exception while fetching data.", 500, $e);
        }
    }
    
    /**
     * Sleep to prevent hammering database.
     * 
     * Sleep interval is somewhat random to minify risk of database collisions.
     * 
     * @return void
     */
    private function sleep()
    {
        usleep(mt_rand(3000000, 6000000));
    }
    
    /**
     * Update processed status for row.
     * 
     * @param integer $id
     * @param boolean $isBeingProcessed
     * @return integer
     */
    private function updateProcessed($id, $isBeingProcessed, $failedRetries = 0)
    {
        $updateSql = "UPDATE " . $this->tableName . " " .
            "SET processed = ?, failed_no = ?" .
            "WHERE id = ?";
        $parameters = array(
            (int)(bool)$isBeingProcessed,
            (int)$id,
            (int)$failedRetries,
        );
        return $this->connection->executeUpdate($updateSql, $parameters);
    }
}
