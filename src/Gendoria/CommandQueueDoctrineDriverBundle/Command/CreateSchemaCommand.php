<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create database schema command.
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class CreateSchemaCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('cmq:doctrine:schema-create')
            ->setDescription("Create database schema for doctrine command queue")
            ->setHelp(<<<EOT
The <info>%command.name%</info> command creates tables for pools defined in your configuration:

    <info>php %command.full_name%</info>
                
Only non existing tables will be created. Existing tables ill not be modified.
EOT
                )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getContainer()->hasParameter('gendoria_command_queue_doctrine_driver.drivers')) {
            $output->writeln("<error>Bundle configuration not present.</error>");
            return 1;
        }
        $connections = $this->prepareConnectionsAndTables();
        foreach ($connections as $connection) {
            foreach ($connection['tables'] as $tableName) {
                $this->createTable($connection['driver'], $tableName);
            }
        }
    }

    private function prepareConnectionsAndTables()
    {
        $connections = array();
        $container = $this->getContainer();
        $drivers = $container->getParameter('gendoria_command_queue_doctrine_driver.drivers');
        foreach ($drivers as $driver) {
            $connectionService = sprintf('doctrine.dbal.%s_connection', $driver['connection']);
            if (empty($connections[$connectionService])) {
                $connections[$connectionService] = array(
                    'driver' => $container->get($connectionService),
                    'tables' => array(),
                );
            }
            $connections[$connectionService]['tables'][$driver['table_name']] = $driver['table_name'];
        }
        return $connections;
    }

    private function createTable(Connection $connection, $tableName)
    {
        $schemaManager = $connection->getSchemaManager();
        if ($schemaManager->tablesExist(array($tableName))) {
            return;
        }
        /* @var $schema Schema */
        $schema = $schemaManager->createSchema();
        $table = $schema->createTable($tableName);
        $table->addColumn('id', 'bigint', array('unsigned' => true, 'autoincrement' => true));
        $table->addColumn('command_class', 'string', array('length' => 255));
        $table->addColumn('command', 'blob');
        $table->addColumn('pool', 'string', array('length' => 100));
        $table->addColumn('failed_no', 'smallint', array('default' => 0));
        $table->addColumn('processed', 'boolean', array('default' => 0));
        $table->addColumn('process_after', 'datetime');
        $table->setPrimaryKey(array('id'));
        $table->addIndex(array('pool', 'processed', 'process_after'));
        
        $schemaManager->createTable($table);
    }

}
