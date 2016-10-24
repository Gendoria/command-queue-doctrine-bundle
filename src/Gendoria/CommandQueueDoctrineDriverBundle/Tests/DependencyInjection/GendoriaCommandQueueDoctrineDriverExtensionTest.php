<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests\DependencyInjection;

use Doctrine\ORM\EntityManagerInterface;
use Gendoria\CommandQueue\ProcessorFactory\ProcessorFactoryInterface;
use Gendoria\CommandQueueBundle\DependencyInjection\Pass\WorkerRunnersPass;
use Gendoria\CommandQueueBundle\Serializer\SymfonySerializer;
use Gendoria\CommandQueueDoctrineDriverBundle\DependencyInjection\GendoriaCommandQueueDoctrineDriverExtension;
use Gendoria\CommandQueueRabbitMqDriverBundle\DependencyInjection\GendoriaCommandQueueRabbitMqDriverExtension;
use Gendoria\CommandQueueRabbitMqDriverBundle\Worker\RabbitMqWorkerRunner;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Description of GendoriaCommandQueueRabbitMqDriverExtension
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class GendoriaCommandQueueDoctrineDriverExtensionTest extends PHPUnit_Framework_TestCase
{

    public function testLoad()
    {
        $container = new ContainerBuilder();
        $extension = new GendoriaCommandQueueDoctrineDriverExtension();
        $config = array(
            'drivers' => array(
                'default' => array(
                    'connection' => 'default',
                    'serializer' => '@gendoria_command_queue.serializer.symfony',
                ),
            ),
        );
        
//        $container->set('logger', new NullLogger());
//        $container->set('doctrine', $this->getMockBuilder(EntityManagerInterface::class)->getMock());
//        $container->set('gendoria_command_queue.serializer.symfony', $this->getMockBuilder(SymfonySerializer::class)->disableOriginalConstructor()->getMock());
//        $container->set('old_sound_rabbit_mq.default_producer', $this->getMockBuilder(ProducerInterface::class)->getMock());
//        $container->set('old_sound_rabbit_mq.default_reschedule_delayed_producer', $this->getMockBuilder(ProducerInterface::class)->getMock());
//        $container->set('event_dispatcher', $this->getMockBuilder(EventDispatcherInterface::class)->getMock());
//        $container->set('gendoria_command_queue.processor_factory', $this->getMockBuilder(ProcessorFactoryInterface::class)->geTMock());
//        
        $extension->load(array($config), $container);
    }
}
