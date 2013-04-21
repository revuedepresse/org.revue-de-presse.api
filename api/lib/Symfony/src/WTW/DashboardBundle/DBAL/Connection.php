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
    const QUERY_TYPE_DEFAULT = 0;

    public $connection;

    public $queryCount;

    public $charset;

    public $database;

    public $host;

    public $port;

    public $logger;

    public $password;

    public $entityManager;

    public $translator;

    public $username;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    public function connect()
    {
        $connection = new \mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port);

        if (!$connection) {
            throw new \Exception($connection->connect_error);
        } else {
            $this->setConnection($connection);
        }

        $this->setConnectionCharset();

        return $this;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    protected function setConnectionCharset()
    {
        if (!$this->getConnection()->set_charset($this->charset)) {
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
        $count = substr_count($query, ';');
        $message = 'Have you forgotten a query delimiter? (maybe the final one)';
        $missingDelimiter = false;

        if ($count >= 1) {
            $queries = explode(';', $query);

            if (count($queries) - 1 < $count) {
                $missingDelimiter = true;
            } else {
                $this->queryCount = $count;
            }
        } else {
            $missingDelimiter = true;
        }

        if ($missingDelimiter) {
            throw new \Exception($message);
        }

        if (!$this->connection->multi_query($query)) {
            throw new \Exception($this->connection->error);
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
            throw new \Exception('Please inject Doctrine entity manager');
        }

        $doctrineConnection = $this->entityManager->getConnection();
        $stmt               = $doctrineConnection->prepare($query);
        $stmt->execute();

        try {
            $results = $stmt->fetchAll();
        } catch (\Exception $exception) {
            $results = [$stmt->errorInfo()];
        }

        return $results;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function fetchResults()
    {
        if (is_null($this->translator)) {
            throw new \Exception('Please inject a translator');
        }

        $results = [$this->translator->trans('sorry', array(), 'messages'),
            $this->translator->trans('wrong_query_execution', array(), 'messages')];

        do {
            if (!$this->connection->field_count) {
                if (strlen($this->connection->error) > 0) {
                    $error = $this->connection->error;
                    $this->logger->info($error);
                    throw new \Exception($error);
                } else {
                    $results[1] = $this->translator->trans('no_record', array(), 'messages');
                }
            } else {
                $queryResult = $this->connection->use_result();
                unset($results);
                while ($result = $queryResult->fetch_array(MYSQLI_ASSOC)) {
                    $results[] = $result;
                }
                $result->close();
            }

            if ($this->connection->more_results()) {
                $this->connection->next_result();
            }

            $this->queryCount--;
        } while ($this->queryCount > 0);

        $this->queryCount = null;
        $this->connection->close();

        return $results;
    }

    /**
     * @param $query
     *
     * @return bool
     */
    public function pdoSafe($query)
    {
        return (false === strpos(strtolower($query), ':=')) &&
            (false === strpos(strtolower($query), '@')) &&
            (false === strpos(strtolower($query), 'update'));
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
            (false === strpos(strtolower($query), 'delete')) &&
            (false === strpos(strtolower($query), 'truncate')) &&
            (
                false === strpos(strtolower($query), 'drop') ||
                (1 === substr_count($query, 'drop')) &&
                (false !== strpos(strtolower($query), 'drop table tmp_'))
            ) &&
            (false === strpos(strtolower($query), 'alter')) &&
            (false === strpos(strtolower($query), 'grant'));
    }
}