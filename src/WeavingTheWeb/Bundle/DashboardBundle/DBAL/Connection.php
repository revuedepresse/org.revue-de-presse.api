<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\DBAL;

use Doctrine\ORM\EntityManager;

use WeavingTheWeb\Bundle\DashboardBundle\Exception\InvalidQueryParametersException,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\NotImplementedException,
    WeavingTheWeb\Bundle\DashboardBundle\Validator\Constraints\Query;

use Psr\Log\LoggerInterface;

use Symfony\Component\Translation\Translator,
    Symfony\Component\Validator\Validator\RecursiveValidator as Validator;

/**
 * @package WeavingTheWeb\Bundle\DashboardBundle\DBAL
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Connection
{
    const QUERY_TYPE_DEFAULT = 0;

    /**
     * @var \mysqli
     */
    public $connection;

    public $queryCount;

    public $charset;

    public $database;

    public $host;

    public $port;

    public $username;

    public $logger;

    public $password;

    /**
     * @var $entityManager EntityManager
     */
    public $entityManager;

    /**
     * @var $translator Translator
     */
    public $translator;

    /**
     * @var $validator Validator
     */
    public $validator;

    /**
     * @var \mysqli_result
     */
    private $lastResult;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * @var array
     */
    private $bindingArguments;

    /**
     * @var \mysqli_stmt
     */
    protected $statement;

    /**
     * @var integer
     */
    protected $affectedRows;

    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    public function __construct(Validator $validator, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->validator = $validator;
    }

    public function getWrappedConnection()
    {
        return $this->connection;
    }

    /**
     * @return \stdClass
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
        $result = $this->getWrappedConnection()->set_charset($this->charset);
        if (!$result) {
            throw new \Exception(sprintf(
                'Impossible to set charset (%s): %S', $this->charset, \mysqli::$error));
        }

        $this->connection->use_result();
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

    /**
     * @param $query
     * @param array $parameters
     * @return $this
     * @throws \Exception
     */
    public function execute($query, $parameters = [])
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
            $this->queryCount = 1;
        }

        $shouldPrepareQuery = count($parameters) > 0;

        if ($shouldPrepareQuery) {
            /** @var \mysqli_stmt $statement */
            $this->statement = $this->connection->prepare($query);
            $types = array_keys($parameters);
            $this->queryParams = array_values($parameters);
            $this->bindingArguments = [implode($types)];
            foreach ($this->queryParams as $key => $value) {
                $this->bindingArguments[] = &$this->queryParams[$key];
            }
            if (!call_user_func_array([$this->statement, 'bind_param'], $this->bindingArguments)) {
                throw new \Exception('Could not bind parameters to MySQL statement');
            }
            $this->lastResult = $this->statement->execute();
        } else {
            $this->lastResult = $this->connection->query($query);
        }

        if (!$this->lastResult) {
            throw new \Exception($this->connection->error);
        }

        if ($shouldPrepareQuery && $this->lastResult && isset($this->statement)) {
            $this->affectedRows = $this->statement->affected_rows;
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
     * @param array $parameters
     * @return \stdClass
     */
    public function executeQuery($sql, $parameters = [])
    {
        $query          = new \stdClass;
        $query->error   = null;
        $query->records = [];
        $query->sql     = $sql;

        if ($this->allowedQuery($query->sql)) {
            try {
                if ($this->pdoSafe($query->sql)) {
                    $query->records = $this->delegateQueryExecution($query->sql);
                } else {
                    $query->records = $this->connect()->execute($query->sql, $parameters)->fetchAll();
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
            if (isset($this->connection->field_count) && !$this->connection->field_count) {
                if (strlen($this->connection->error) > 0) {
                    $error = $this->connection->error;
                    if ($this->connection->errno === 2014) { // Repair all tables of the test database
                        $this->logger->error('[CONNECTION] ' . $error);
                    } else {
                        $this->logger->info('[CONNECTION] '. $error);
                    }

                    throw new \Exception($error);
                } else {
                    $results[1] = $this->translator->trans('no_record', array(), 'messages');
                    if (!is_null($this->statement) && $this->statement instanceof \mysqli_stmt) {
                        /**
                         * @see http://stackoverflow.com/a/25377031/2820730 about using \mysqli and xdebug
                         */
                        $this->statement->close();
                        unset($this->statement);
                    }
                }
            } else {
                $queryResult = $this->lastResult;

                /**
                 * @var \mysqli_result $queryResult
                 */
                if (is_object($queryResult) && ($queryResult instanceof \mysqli_result)) {
                    $results = [];
                    while ($result = $queryResult->fetch_array(MYSQLI_ASSOC)) {
                        if (!is_null($result)) {
                            $results[] = $result;
                        }
                    }
                }
            }

            $this->queryCount--;
        } while ($this->queryCount > 0);

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
    public function allowedQuery($sql)
    {
        $queryConstraint = new Query();
        $errorList = $this->validator->validateValue($sql, $queryConstraint);

        return count($errorList) === 0;
    }

    /**
     * @param $table
     * @return bool
     * @throws \Exception
     */
    public function isTableValid($table)
    {
        $results = $this->executeQuery('Show tables;');
        $records = $this->getRecordsSafely($results);
        $tables = [];
        array_walk($records, function ($value) use (&$tables) {
            list(, $table) = each($value);
            array_push($tables, $table);
        });

        return array_key_exists($table, array_flip($tables));
    }

    /**
     * @param $table
     * @return mixed
     * @throws NotImplementedException
     * @throws \Exception
     */
    public function getTablePrimaryKey($table)
    {
        $results = $this->executeQuery(sprintf('SHOW KEYS FROM %s WHERE Key_name = \'PRIMARY\'', $table));
        $records = $this->getRecordsSafely($results);
        if (count($records) === 0) {
            throw new \Exception(sprintf('There is no primary key for table "%s".', $table));
        } elseif (count($records) > 1) {
            throw new NotImplementedException('Handling of composite primary keys is not implemented.');
        } elseif (!array_key_exists('Column_name', $records[0])) {
            throw new \Exception(sprintf('The primary key can not be identified for table "%s"', $table));
        }

        return $records[0]['Column_name'];
    }

    /**
     * @param $value
     * @param $table
     * @return bool
     * @throws NotImplementedException
     * @throws \Exception
     */
    public function isPrimaryKeyValidForTable($value, $table)
    {
        $primaryKey = $this->getTablePrimaryKey($table);
        $results = $this->executeQuery(sprintf('SELECT count(*) count_ FROM %s WHERE %s = %d',
            $table,
            $primaryKey,
            $value));
        $records = $this->getRecordsSafely($results);

        return count($records) === 1 && intval($records[0]['count_']) === 1;
    }

    /**
     * @param $column
     * @param $table
     * @return bool
     * @throws \Exception
     */
    public function isColumnValidForTable($column, $table)
    {
        $results = $this->executeQuery(sprintf('DESCRIBE %s', $table));
        $records = $this->getRecordsSafely($results);
        $columns = [];
        array_walk($records, function ($value) use (&$columns) {
            array_push($columns, $value['Field']);
        });

        return array_key_exists($column, array_flip($columns));
    }

    public function saveContent($content, $table, $key, $column)
    {
        $key = intval($key);
        $validColumn = false;
        $validKey = false;

        $validTable = $this->isTableValid($table);

        if ($validTable) {
            $validKey = $this->isPrimaryKeyValidForTable($key, $table);
            if ($validKey) {
                $validColumn = $this->isColumnValidForTable($column, $table);
            }
        }

        $invalidQueryParameters = [];

        if (!$validTable) {
            $this->logger->info(sprintf('[CONNECTION] Invalid table ("%s") provided to save content', $table));
            $invalidQueryParameters[] = 'table';
        }

        if (!$validKey) {
            $this->logger->info(sprintf('[CONNECTION] Invalid key ("%d") provided to save content', $key));
            $invalidQueryParameters[] = 'key';
        }

        if (!$validColumn) {
            $this->logger->info(sprintf('[CONNECTION] Invalid column ("%d") provided to save content', $column));
            $invalidQueryParameters[] = 'column';
        }

        if (!$validTable || !$validKey || !$validColumn) {
            throw new InvalidQueryParametersException(sprintf('Invalid query parameters (%s)',
                implode(', ', $invalidQueryParameters)));
        } else {
            $primaryKey = $this->getTablePrimaryKey($table);
            $query = sprintf('UPDATE %s SET %s = ? WHERE %s = ?',
                $table,
                $column,
                $primaryKey
            );

            $this->logger->info(sprintf('[CONNECTION] Executing: [%s]', $query));
            $results = $this->executeQuery($query, [
                's' => $content,
                'i' => intval($key)
            ]);
            if ($results->error) {
                throw new \Exception($results->error);
            }

            $this->logger->info(sprintf('[CONNECTION] %d row(s) affected by executing the last query', $this->affectedRows));
            $this->affectedRows = null;

            return $this->getRecordsSafely($results);
        }
    }

    /**
     * @param $results
     * @return mixed
     * @throws \Exception
     */
    protected function getRecordsSafely($results)
    {
        if (!is_null($results->error)) {
            throw new \Exception($results->error);
        }

        return $results->records;
    }
}
