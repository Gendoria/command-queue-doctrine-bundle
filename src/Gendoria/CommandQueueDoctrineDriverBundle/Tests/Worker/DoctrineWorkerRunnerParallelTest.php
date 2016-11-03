<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests\Worker;

use Gendoria\CommandQueueDoctrineDriverBundle\Tests\DbTestCase;
use Gendoria\CommandQueueDoctrineDriverBundle\Worker\DoctrineWorker;
use Gendoria\CommandQueueDoctrineDriverBundle\Worker\DoctrineWorkerRunner;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;

/**
 * Description of DoctrineWorkerRunnerTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class DoctrineWorkerRunnerParallelTest extends DbTestCase
{

    const ASSERTION_PROCESSING_COMMAND = 2;
    const ASSERTION_EXCEPTION_FETCHING_DATA = 3;
    
    protected function setUp()
    {
        $this->dataset = 'fixtures/concurrency.yml';
        parent::setUp();
    }

    public function testRun()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('No process control installed');
        }

        $pids = array();
        $errors = array();

        for ($k = 0; $k < 5; $k++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new RuntimeException("Problems while forking");
            } elseif ($pid > 0) {
                $pids[$pid] = $pid;
            } else {
                $this->runParallelWorker();
            }
        }

        $status = null;
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $exitStatus = pcntl_wexitstatus($status);
            switch ($exitStatus) {
                case 0:
                    break;
                case self::ASSERTION_EXCEPTION_FETCHING_DATA:
                    $errors[] = "Error while fetching data";
                    break;
                case self::ASSERTION_PROCESSING_COMMAND:
                    $errors[] = "Error while processing command";
                    break;
                default:
                    $errors[] = "Unknown error in child process";
                    break;
            }
            unset($pids[$pid]);
        }
        
        $this->cleanUpPdo();
        $this->assertEquals(array(), $errors, "Errors in child processes");
        $this->assertEquals(0, $this->getConnection()->getRowCount('cmq'));
    }

    private function runParallelWorker()
    {
        try {
            $this->cleanUpPdo();
            $worker = $this->getMockBuilder(DoctrineWorker::class)
                ->disableOriginalConstructor()
                ->getMock();
            $connection = $this->getDoctrineConnection();
            $runner = new DoctrineWorkerRunner($worker, $connection, 'cmq', 'default');
            $output = new BufferedOutput(Output::VERBOSITY_DEBUG);

            $runner->run(array('sleep_intervals' => array(0, 0), 'exit_if_empty' => true), $output);
            $fetchedOutput = $output->fetch();
            if (strpos($fetchedOutput, 'Processing command') === false) {
                throw new \Exception('Processing command assertion failed', self::ASSERTION_PROCESSING_COMMAND);
            }
            if (strpos($fetchedOutput, 'Exception while fetching data') !== false) {
                throw new \Exception('Exception while fetching data', self::ASSERTION_EXCEPTION_FETCHING_DATA);
            }
            exit(0);
        } catch (\Exception $e) {
            exit($e->getCode() > 0 ? $e->getCode() : 1);
        }
    }

}
