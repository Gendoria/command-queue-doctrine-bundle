<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests\Worker;

use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\CommandProcessor\CommandProcessorInterface;
use Gendoria\CommandQueue\ProcessorFactory\ProcessorFactory;
use Gendoria\CommandQueue\Serializer\SerializedCommandData;
use Gendoria\CommandQueue\Serializer\SerializerInterface;
use Gendoria\CommandQueueDoctrineDriverBundle\Worker\DoctrineWorker;
use PHPUnit_Framework_TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests for Doctrine Worker.
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class DoctrineWorkerTest extends PHPUnit_Framework_TestCase
{
    public function testGetSubsystemName()
    {
        $processorFactory = new ProcessorFactory();
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $worker = new DoctrineWorker($processorFactory, $serializer, $eventDispatcher);
        $this->assertEquals(DoctrineWorker::SUBSYSTEM_NAME, $worker->getSubsystemName());
    }
    
    public function testProcess()
    {
        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        $commandRow = [
            'command_class' => get_class($command),
            'command' => '{prop: 1}',
        ];
        $processor = $this->getMockBuilder(CommandProcessorInterface::class)->getMock();
        $processor->expects($this->atLeast(1))
            ->method('supports')
            ->with($this->equalTo($command))
            ->will($this->returnValue(true));
        $processorFactory = new ProcessorFactory();
        $processorFactory->registerProcessorForCommand(get_class($command), $processor);
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer->expects($this->once())
            ->method('unserialize')
            ->with($this->equalTo(new SerializedCommandData('{prop: 1}', get_class($command))))
            ->will($this->returnValue($command));
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        
        $worker = new DoctrineWorker($processorFactory, $serializer, $eventDispatcher);
        $worker->process($commandRow);
    }    
}
