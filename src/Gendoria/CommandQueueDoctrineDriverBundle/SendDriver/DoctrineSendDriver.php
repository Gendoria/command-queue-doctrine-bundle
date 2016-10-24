<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\SendDriver;

use Doctrine\DBAL\Connection;
use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\SendDriver\SendDriverInterface;
use Gendoria\CommandQueue\Serializer\SerializerInterface;

/**
 * Command queue send driver using RabbitMQ server.
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class DoctrineSendDriver implements SendDriverInterface
{
    /**
     * Serializer instance.
     *
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * Doctrine connection.
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
     * Class constructor.
     *
     * @param SerializerInterface $serializer Serializer.
     * @param Connection          $connection Database connection.
     * @param string              $tableName Table name.
     * @param string              $pool Pool name.
     */
    public function __construct(SerializerInterface $serializer, Connection $connection, $tableName, $pool)
    {
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->tableName = (string)$tableName;
        $this->pool = (string)$pool;
    }

    /**
     * Send command using RabbitMQ server.
     *
     * {@inheritdoc}
     */
    public function send(CommandInterface $command)
    {
        $serialized = $this->serializer->serialize($command);
        $data = array(
            'command_class' => $serialized->getCommandClass(),
            'command' => $serialized->getSerializedCommand(),
            'pool' => $this->pool,
        );
        $this->connection->insert($this->tableName, $data);
    }
}
