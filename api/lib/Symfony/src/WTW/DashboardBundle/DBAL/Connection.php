<?php

namespace WTW\DashboardBundle\DBAL;

use Doctrine\ORM\EntityManager;

/**
 * Class Connection
 *
 * @package WTW\DashboardBundle\DBAL
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Connection
{
    protected $connection;

    protected $host;

    protected $username;

    protected $port;

    protected $database;

    protected $password;

    protected $entityManager;

    protected $charset;

    public function connect() {
        $this->connection = new \mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port);

        if (!$this->connection) {
            throw new \Exception(mysqli::$connect_error);
        }

        $this->setConnectionCharset();

        return $this;
    }

    protected function setConnectionCharset()
    {
        if (!$this->connection->set_charset($this->charset)) {
            throw new \Exception(sprintf(
                'Impossible to set charset (%s): %S', $this->charset, mysqli::$error));
        }
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function execute($query)
    {
        if (!$this->connection->multi_query($query)) {
            throw new \Exception(mysqli::error);
        }

        return $this;
    }

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * @param $query
     *
     * @return mixed
     * @throws \Exception
     */
    public function delegateQueryExecution($query)
    {
        /**
         * @var $em EntityManager
         */
        if (is_null($this->entityManager)) {
            throw new \Exception('Doctrine entity manager has to be injected');
        }

        $doctrineConnection = $this->entityManager->getConnection();
        $stmt               = $doctrineConnection->prepare($query);
        $stmt->execute();

        try {
            $results = $stmt->fetchAll();
        } catch (\Exception $exception) {
            throw new \Exception($stmt->errorInfo());
        }

        return $results;
    }

    /**
     * @return array
     */
    public function fetchResults()
    {
        $records = ['Sorry...', 'Something went wrong when executing your query.'];

        do {
            if (!$this->connection->field_count) {
                $records[1] = 'No record was found when executing your query';
            } else {
                $result = $this->connection->use_result();
                unset($records);
                while ($records[] = $result->fetch_array(MYSQLI_ASSOC)) ;
                $result->free();
            }
        } while ($this->connection->more_results() && $this->connection->next_result());

        return $records;
    }

    /**
     * @param $query
     *
     * @return bool
     */
    public function pdoSafe($query)
    {
        return (false === strpos(strtolower($query), ':=')) &&
            (false === strpos(strtolower($query), '@'));
    }

    /**
     * @param $query
     *
     * @return bool
     */
    public function idempotentQuery($query)
    {
        return
            (strlen($query) > 0) &&
            (false === strpos(strtolower($query), 'update')) &&
            (false === strpos(strtolower($query), 'insert')) &&
            (false === strpos(strtolower($query), 'delete')) &&
            (false === strpos(strtolower($query), 'truncate')) &&
            (false === strpos(strtolower($query), 'drop')) &&
            (false === strpos(strtolower($query), 'alter')) &&
            (false === strpos(strtolower($query), 'grant'));
    }
}