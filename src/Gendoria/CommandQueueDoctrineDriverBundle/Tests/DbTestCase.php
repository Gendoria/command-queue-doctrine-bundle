<?php

namespace Gendoria\CommandQueueDoctrineDriverBundle\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;
use PHPUnit_Extensions_Database_DataSet_IDataSet;
use PHPUnit_Extensions_Database_DataSet_YamlDataSet;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;
use PHPUnit_Extensions_Database_TestCase;

/**
 * Description of DoctrineSendDriverTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class DbTestCase extends PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;
    
    protected $dataset = 'fixtures/default.yml';
    
    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'] );
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }
        return $this->conn;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        $path = dirname(__FILE__) .'/'. $this->dataset;
        return new PHPUnit_Extensions_Database_DataSet_YamlDataSet($path);
    }
    
    /**
     * Get Doctrine connection
     * @return Connection
     */
    protected function getDoctrineConnection()
    {
        $params = array(
            'pdo' => $this->getConnection()->getConnection(),
        );
        return DriverManager::getConnection($params);
    }
}
