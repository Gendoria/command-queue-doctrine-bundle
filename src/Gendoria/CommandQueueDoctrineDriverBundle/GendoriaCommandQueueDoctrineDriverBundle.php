<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle;

use Gendoria\CommandQueueDoctrineDriverBundle\DependencyInjection\GendoriaCommandQueueDoctrineDriverExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * RabbitMQ integration bundle for Gendoria Command Queue.
 */
class GendoriaCommandQueueDoctrineDriverBundle extends Bundle
{
    /**
     * Get bundle extension instance.
     *
     * @return GendoriaCommandQueueDoctrineDriverExtension
     */
    public function getContainerExtension()
    {
        if (null === $this->extension || false === $this->extension) {
            $this->extension = new GendoriaCommandQueueDoctrineDriverExtension();
        }

        return $this->extension;
    }
}
