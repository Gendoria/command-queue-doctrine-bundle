<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests\Worker;

use DateTime;
use Doctrine\DBAL\Connection;
use Exception;
use Gendoria\CommandQueueDoctrineDriverBundle\Tests\DbTestCase;
use Gendoria\CommandQueueDoctrineDriverBundle\Worker\DoctrineWorker;
use Gendoria\CommandQueueDoctrineDriverBundle\Worker\DoctrineWorkerRunner;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;

/**
 * Description of DoctrineWorkerRunnerTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class DoctrineWorkerRunnerTest extends DbTestCase
{
    public function testRun()
    {
        $initialRowCount = $this->getConnection()->getRowCount('cmq');
        $worker = $this->getMockBuilder(DoctrineWorker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->getDoctrineConnection();
        $runner = new DoctrineWorkerRunner($worker, $connection, 'cmq', 'default');
        $output = new BufferedOutput(Output::VERBOSITY_DEBUG);
        $worker->expects($this->exactly($initialRowCount-1))
            ->method('process');
        
        $runner->run(array('run_times' => $initialRowCount, 'sleep_intervals' => array(0,0)), $output);
        $fetchedOutput = $output->fetch();
        $this->assertContains('Processing command', $fetchedOutput);
        $this->assertEquals(1, $this->getConnection()->getRowCount('cmq'));
        $this->assertNotContains('Exception while fetching data', $fetchedOutput);
    }
    
    public function testRunProcessException()
    {
        $worker = $this->getMockBuilder(DoctrineWorker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->getDoctrineConnection();
        $runner = new DoctrineWorkerRunner($worker, $connection, 'cmq', 'default');
        $output = new BufferedOutput(Output::VERBOSITY_DEBUG);
        $worker->expects($this->exactly(2))
            ->method('process')
            ->will($this->throwException(new Exception("Process error")));
        
        $runner->run(array('run_times' => 2, 'sleep_intervals' => array(0,0)), $output);
        $fetchedOutput = $output->fetch();
        $this->assertNotContains('There is already an active transaction', $fetchedOutput);
        $this->assertContains('Processing command', $fetchedOutput);
        $this->assertContains('Process error', $fetchedOutput);
        $this->assertContains('Command failed too many times, discarding.', $fetchedOutput);
        $queryTable = $this->getConnection()->createQueryTable('cmq', "SELECT * FROM cmq WHERE pool='default' ORDER BY id");
        $this->assertEquals(1, $queryTable->getRowCount());
        $row = $queryTable->getRow(0);
        $afterDateTime = new DateTime($row['process_after']);
        $currentDateTime = new DateTime();
        $this->assertGreaterThan($currentDateTime, $afterDateTime);
    }
    
    public function testRunEmpty()
    {
        $worker = $this->getMockBuilder(DoctrineWorker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $origConnection = $this->getDoctrineConnection();
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($origConnection->getDatabasePlatform()));
        $connection->expects($this->once())
            ->method('beginTransaction');
        $connection->expects($this->once())
            ->method('commit');
        $connection->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue(false));
        
        $runner = new DoctrineWorkerRunner($worker, $connection, 'cmq', 'default');
        $output = new BufferedOutput(Output::VERBOSITY_DEBUG);
        
        $runner->run(array('run_times' => 1, 'sleep_intervals' => array(0,0)), $output);
        $fetchedOutput = $output->fetch();
        $this->assertNotContains('Processing command', $fetchedOutput);
        $this->assertNotContains('Exception while fetching data', $fetchedOutput);
    }    
    
    public function testRunRaceCondition()
    {
        $worker = $this->getMockBuilder(DoctrineWorker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $origConnection = $this->getDoctrineConnection();
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($origConnection->getDatabasePlatform()));
        $connection->expects($this->atLeast(1))
            ->method('beginTransaction');
        $connection->expects($this->never())
            ->method('commit');
        $connection->expects($this->atLeast(1))
            ->method('rollBack');
        $connection->expects($this->atLeast(1))
            ->method('fetchAssoc')
            ->will($this->returnValue(array(
                'id' => 1,
                'command_class' => 'test',
                'command' => '{}',
                'pool' => 'default',
                'processed' => 0,
                'failed_no' => 0,
                'process_after' => '0000-00-00 00:00:00'
            )));
        $worker->expects($this->never())
            ->method('process');
        $runner = new DoctrineWorkerRunner($worker, $connection, 'cmq', 'default');
        $output = new BufferedOutput(Output::VERBOSITY_DEBUG);
        
        $runner->run(array('run_times' => 11, 'sleep_intervals' => array(0, 0)), $output);
        $fetchedOutput = $output->fetch();
        $this->assertNotContains('Processing command', $fetchedOutput);
        $this->assertContains('Race condition detected. Aborting.', $fetchedOutput);
        $this->assertContains('Too many consequent fetch errors - exiting.', $fetchedOutput);
        $this->assertNotContains('There is already an active transaction', $fetchedOutput);
    }
}
