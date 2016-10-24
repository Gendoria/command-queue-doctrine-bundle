<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Worker;

use Gendoria\CommandQueue\Serializer\SerializedCommandData;
use Gendoria\CommandQueueBundle\Worker\BaseSymfonyWorker;

/**
 * Doctrine worker
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class DoctrineWorker extends BaseSymfonyWorker
{
    /**
     * Subsystem name.
     * 
     * @var string
     */
    const SUBSYSTEM_NAME = 'DoctrineWorker';
    
    /**
     * {@inheritdoc}
     * @param array $commandData
     */
    protected function getSerializedCommandData($commandData)
    {
        return new SerializedCommandData($commandData['command'], $commandData['command_class']);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubsystemName()
    {
        return self::SUBSYSTEM_NAME;
    }
}
