<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests;

use Gendoria\CommandQueueDoctrineDriverBundle\DependencyInjection\GendoriaCommandQueueDoctrineDriverExtension;
use Gendoria\CommandQueueDoctrineDriverBundle\GendoriaCommandQueueDoctrineDriverBundle;
use PHPUnit_Framework_TestCase;

/**
 * Description of GendoriaCommandQueueRabbitMqDriverBundleTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class GendoriaCommandQueueDoctrineDriverBundleTest extends PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new GendoriaCommandQueueDoctrineDriverBundle();
        $this->assertInstanceOf(GendoriaCommandQueueDoctrineDriverExtension::class, $bundle->getContainerExtension());
    }
}
