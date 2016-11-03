<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests\Command;

use Gendoria\CommandQueueDoctrineDriverBundle\Command\CreateSchemaCommand;
use Gendoria\CommandQueueDoctrineDriverBundle\Tests\DbTestCase;
use PHPUnit_Framework_MockObject_Generator;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests for create schema command.
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class CreateSchemaCommandTest extends DbTestCase
{
    protected function setUp()
    {
    }
    
    public function testExecute()
    {
        $connection = $this->getDoctrineConnection();
        
        $schemaManager = $connection->getSchemaManager();
        if ($schemaManager->tablesExist(array('cmq'))) {
            $schemaManager->dropTable('cmq');
        }
        if ($schemaManager->tablesExist(array('cmq_2'))) {
            $schemaManager->dropTable('cmq_2');
        }
        
        $container = new ContainerBuilder();
        $container->setParameter('gendoria_command_queue_doctrine_driver.drivers', array(
            'default' => array(
                'connection' => 'default',
                'serializer' => '@gendoria_command_queue.serializer.symfony',
                'table_name' => 'cmq',
            ),
            'other' => array(
                'connection' => 'default2',
                'serializer' => '@gendoria_command_queue.serializer.symfony',
                'table_name' => 'cmq_2',
            ),
        ));
        $container->set('doctrine.dbal.default_connection', $connection);
        $container->set('doctrine.dbal.default2_connection', $connection);
        $container->compile();
        $kernel = $this->createKernel($container);

        $application = new Application($kernel);
        $testedCommand = new CreateSchemaCommand();
        $application->add($testedCommand);

        $command = $application->find('cmq:doctrine:schema-create');
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute(array());
        $this->assertEquals(0, $exitCode);
        
        $this->assertTrue($schemaManager->tablesExist(array('cmq', 'cmq_2')), 'Tables were not created.');
        
        //Run command again - it should not fail with existing tables
        $commandTester2 = new CommandTester($command);
        $exitCode2 = $commandTester2->execute(array());
        $this->assertEquals(0, $exitCode2);        
    }
    
    public function testExecuteNoParameter()
    {
        $container = new ContainerBuilder();
        $container->compile();
        $kernel = $this->createKernel($container);

        $application = new Application($kernel);
        $testedCommand = new CreateSchemaCommand();
        $application->add($testedCommand);

        $command = $application->find('cmq:doctrine:schema-create');
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute(array());
        $this->assertEquals(1, $exitCode);
    }

    /**
     * Create kernel mock.
     * 
     * @param ContainerBuilder $container
     * @return PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_Generator|KernelInterface
     */
    private function createKernel(ContainerBuilder $container)
    {
        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
        $kernel->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($container));
        $kernel->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue(array()));
        return $kernel;
    }

}
