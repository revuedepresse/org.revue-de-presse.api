<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Tests\DBAL;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\WebTestCase;
use WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ConnectionTest extends WebTestCase
{
    /**
     * @var $connection Connection
     */
    public $connection;

    public function setUp()
    {
        $this->client = $this->getClient();

        $this->connection = $this->getTestedConnection();
    }

    /**
     * @group connection
     * @group isolated-testing
     */
    public function testConnect()
    {
        $wrappedConnection = $this->connection->connect()->getWrappedConnection();

        $this->assertInternalType('object', $wrappedConnection);
        $this->assertEquals('mysqli', get_class($wrappedConnection));
    }

    /**
     * @dataProvider getQueriesAllowance
     * @group connection
     * @group isolated-testing
     */
    public function testAllowedQuery($sql, $expectedAllowance)
    {
        $allowedQuery = $this->connection->allowedQuery($sql);
        $this->assertEquals($expectedAllowance, $allowedQuery);
    }

    public function getQueriesAllowance()
    {
        return array(
            array('query' => 'DELETE FROM tmp_', 'allowance' => true),
            array('query' => 'DELETE FROM bidule', 'allowance' => false),
            array('query' => 'DROP TABLE IF EXISTS tmp_', 'allowance' => true),
            array('query' => 'DROP TABLE bidule', 'allowance' => false),
        );
    }

    /**
     * @return Connection
     * @group isolated-testing
     */
    public function getTestedConnection()
    {
        /**
         * @var $connection Connection
         */
        $connection = $this->get('weaving_the_web_dashboard.dbal_connection');

        $connection->setUsername($this->getParameter('database_user_test'));
        $connection->setPassword($this->getParameter('database_password_test'));
        $connection->setDatabase($this->getParameter('database_name_test'));
        $connection->setHost($this->getParameter('database_host_test'));
        $connection->setPort($this->getParameter('database_port_test'));
        $connection->setCharset($this->getParameter('database_charset_test'));
        $connection->setCharset($this->getParameter('database_charset_test'));

        $entityManager = $this->get('doctrine.orm.test_mysql_entity_manager');
        $connection->setEntityManager($entityManager);

        return $connection;
    }

    public function requiredMySQLDatabase()
    {
        return true;
    }
}
