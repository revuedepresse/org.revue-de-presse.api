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

    /**
     * @return string
     */
    public function getDefaultQuery()
    {
        $defaultQuery = new \stdClass();
        $defaultQuery->sql = 'invalid query';
        $defaultQuery->error = null;

        $baseQuery = <<< QUERY
SELECT per_value AS query
FROM {database}weaving_perspective
WHERE per_type = {type}
LIMIT 1
QUERY;

        $query = strtr($baseQuery, array(
            '{database}' => $this->database . '.',
            '{type}' => self::QUERY_TYPE_DEFAULT));

        try {
            $results = $this->connect()->execute($query)->fetchAll();
            if (count($results) > 0) {
                $defaultQuery->sql = $results[0]['query'];
            }
        } catch (\Exception $exception) {
            $defaultQuery->error = $exception->getMessage();
        }

        return $defaultQuery;
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

        if ($count >= 1) {
            $queries = explode(';', $query);

            if ((count($queries) === $count + 1) ||
                (count($queries) === $count)) {
                $this->queryCount = $count;
            } else {
                throw new \Exception('confusing_query');
            }
        } else {
            $query .= ';';
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
     * @param $sql
     *
     * @return \stdClass
     */
    public function executeQuery($sql)
    {
        $query          = new \stdClass;
        $query->error   = null;
        $query->records = [];
        $query->sql = $sql;

        if ($this->idempotentQuery($query->sql)) {
            try {
                if ($this->pdoSafe($query->sql)) {
                    $query->records = $this->delegateQueryExecution($query->sql);
                } else {
                    $query->records = $this->connect()->execute($query->sql)->fetchAll();
                }
            } catch (\Exception $exception) {
                $query->error = $exception->getMessage();
            }
        } else {
            $query->records = [$this->translator->trans('requirement_valid_query', array(), 'messages')];
        }

        return $query;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function fetchAll()
    {
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
                $queryResult->close();
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
     * @param $sql
     *
     * @return bool
     */
    public function pdoSafe($sql)
    {
        return (false === strpos(strtolower($sql), ':=')) &&
            (false === strpos(strtolower($sql), '@')) &&
            (false === strpos(strtolower($sql), 'update')) &&
            (false === strpos(strtolower($sql), 'drop'));
    }

    /**
     * @param $sql
     *
     * @return bool
     */
    public function idempotentQuery($sql)
    {
        return
            (strlen($sql) > 0) &&
            (
                (false === strpos(strtolower($sql), 'delete')) ||
                (1 === substr_count(strtolower($sql), 'delete')) &&
                (false !== strpos(strtolower($sql), 'delete from tmp_'))
            ) &&
            (false === strpos(strtolower($sql), 'truncate')) &&
            (
                (false === strpos(strtolower($sql), 'drop')) ||
                (1 === substr_count(strtolower($sql), 'drop')) && (
                    (false !== strpos(strtolower($sql), 'drop table tmp_')) ||
                    (false !== strpos(strtolower($sql), 'drop table if exists tmp_'))
                )
            ) &&
            (false === strpos(strtolower($sql), 'alter')) &&
            (false === strpos(strtolower($sql), 'grant'));
    }
}