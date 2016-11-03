<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests\DependencyInjection;

use Gendoria\CommandQueueDoctrineDriverBundle\DependencyInjection\GendoriaCommandQueueDoctrineDriverExtension;
use PHPUnit_Framework_TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        
        $extension->load(array($config), $container);
        $expectedConfig = array(
            'default' => array(
                'connection' => $config['drivers']['default']['connection'],
                'serializer' => $config['drivers']['default']['serializer'],
                'table_name' => 'cmq',
            ),
        );
        $this->assertEquals($expectedConfig, $container->getParameter('gendoria_command_queue_doctrine_driver.drivers'));
    }
}
