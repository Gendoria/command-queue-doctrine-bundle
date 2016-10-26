<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests\SendDriver;

use DateTime;
use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\Serializer\SerializedCommandData;
use Gendoria\CommandQueue\Serializer\SerializerInterface;
use Gendoria\CommandQueueDoctrineDriverBundle\SendDriver\DoctrineSendDriver;
use Gendoria\CommandQueueDoctrineDriverBundle\Tests\DbTestCase;

/**
 * Description of DoctrineSendDriverTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class DoctrineSendDriverTest extends DbTestCase
{

    public function testSend()
    {
        $initialRowCount = $this->getConnection()->getRowCount('cmq');
        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer->expects($this->once())
            ->method('serialize')
            ->with($this->equalTo($command))
            ->will($this->returnValue(new SerializedCommandData('{param1: 1}', get_class($command))));
        $connection = $this->getDoctrineConnection();
        $sendDriver = new DoctrineSendDriver($serializer, $connection, 'cmq', 'default');
        $sendDriver->send($command);
        $this->assertEquals($initialRowCount + 1, $this->getConnection()->getRowCount('cmq'));
        $queryTable = $this->getConnection()->createQueryTable('cmq', "SELECT * FROM cmq ORDER BY id DESC LIMIT 1");
        $expectedTable = $this->createArrayDataSet([
            'cmq' => [
                [
                    'id' => 2,
                    'command_class' => get_class($command),
                    'command' => '{param1: 1}',
                    'pool' => 'default',
                    'failed_no' => 0,
                    'processed' => 0,
                    'process_after' => (new \DateTime())->format($connection->getDatabasePlatform()->getDateTimeFormatString())
                ],
            ]
        ])->getTable('cmq');
        $this->assertTablesEqual($expectedTable, $queryTable);
    }

}
