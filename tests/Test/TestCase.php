<?php

namespace App\Test;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain,
    Doctrine\DBAL\Schema\SchemaException,
    Doctrine\ORM\Configuration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Driver\PDOException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class TestCase extends WebTestCase implements TestCaseInterface, DataFixturesAwareInterface
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client $client
     */
    protected $client;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     *  @var \Doctrine\Common\DataFixtures\Executor\AbstractExecutor
     */
    protected $executor;

    /**
     *  @var \Symfony\Component\Finder\Finder $finder
     */
    protected $finder;

    /**
     * @var \Doctrine\Common\DataFixtures\Loader
     */
    protected $loader;

    /**
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    protected $schemaManipulator;

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    protected static $kernel;

    /**
     * @param  array                                  $options
     * @param  array                                  $server
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    public function getClient(array $options = array(), array $server = array())
    {
        $client = self::createClient($options, $server);

        if ($this->requiredFixtures()) {
            $this->importFixturesServices();
            $this->regenerateSchema();

            try {
                $this->loadFixtures();
            } catch (\Exception $exception) {
                $this->fail(sprintf('Fixtures can not be loaded: %s.', $exception->getMessage()));
            }
        }

        return $client;
    }

    public function requiredFixtures()
    {
        return false;
    }

    public function importFixturesServices()
    {
        if ($this->requiredMySQLDatabase()) {
            $entityManager = $this->getService('doctrine.orm.test_mysql_entity_manager');
            $executor = $this->getService('weaving_the_web.quality_assurance.fixtures.test_mysql_executor');
            $schemaManipulator = $this->getService('weaving_the_web.quality_assurance.fixtures.test_mysql_schema_manipulator');
        } else {
            $entityManager = $this->getService('doctrine.orm.entity_manager');
            $executor = $this->getService('weaving_the_web.quality_assurance.fixtures.executor');
            $schemaManipulator = $this->getService('weaving_the_web.quality_assurance.fixtures.schema_manipulator');
        }

        $this->entityManager = $entityManager;
        $this->executor = $executor;
        $this->finder = $this->getService('weaving_the_web.quality_assurance.fixtures.finder');
        $this->loader = $this->getService('weaving_the_web.quality_assurance.fixtures.loader');
        $this->schemaManipulator = $schemaManipulator;
    }

    /**
     * Generate the schema.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     *
     * @return void
     */
    public function regenerateSchema()
    {
        /** @var Connection $connection */
        $connection = $this->getService('doctrine.dbal.test_mysql_connection');
        $name = $connection->getDatabase();

        if (strpos($name, 'test') === false) {
            throw new \Exception(
                'Can not regenerate a schema for a database with a name not containing "test"'
            );
        }

        if ($this->dropOrCreateDatabase($connection, $name) &&
            $this->shouldSkipSchemaCreation()
        ) {
            return;
        }

        try {
            $connection->executeQuery('use '.$name);
            $this->createSchema();
        } catch (\Exception $exception) {
            $this->dropOrCreateDatabase($connection, $name);
            $this->createSchema();
        }
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function createSchema()
    {
        if ($this->shouldSkipSchemaCreation()) {
            return;
        }

        $metadata = $this->getSchemaMetadata();

        if (!empty($metadata)) {
            $this->schemaManipulator->createSchema($metadata);
        } else {
            throw new SchemaException('No Metadata Classes to process.');
        }
    }

    public function loadFixtures()
    {
        $directories = $this->getFixturesDirectories();
        $fixtures = $this->getFixtures($directories);

        $this->executor->execute($fixtures);
    }

    /**
     * @return array
     */
    public function getSchemaMetadata()
    {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        return $entityManager->getMetadataFactory()->getAllMetadata();
    }

    public function dropDatabase()
    {
        $connection = $this->entityManager->getConnection();
        $name = $connection->getDatabase();

        if ($this->requiredMySQLDatabase()) {
            $metadata = $this->getSchemaMetadata();
            $this->schemaManipulator->dropSchema($metadata);
        } else {
            $connection->getSchemaManager()->dropDatabase($name);
        }
    }

    /**
     * @param $name
     */
    public function createDatabase($name)
    {
        $entityManagerClass = get_class($this->entityManager);

        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new MappingDriverChain());
        $cacheDir = $this->getParameter('kernel.cache_dir');
        $configuration->setProxyDir($cacheDir . '/doctrine/orm/Proxies');
        $configuration->setProxyNamespace('TestProxies');

        /**
         * @var \Doctrine\Dbal\Connection $connection
         */
        $connection         = $this->get('doctrine.dbal.test_mysql_connection');

        $params             = $connection->getParams();
        unset($params['dbname']);

        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $entityManagerClass::create($params, $configuration);
        $entityManager->getConnection()->getSchemaManager()->createDatabase($name);
    }

    /**
     * @param array $options
     * @param array $server
     * @return \Symfony\Component\BrowserKit\Client
     */
    public function getAuthenticatedClient(array $options = array(), $server = array())
    {
        $followRedirects = $this->extractOption('follow_redirects', $options);
        $superAdmin = $this->extractOption('super_admin', $options);

        if (!array_key_exists('user', $options)) {
            if (null === $this->getContainer()) {
                static::bootKernel($options);
            }

            $credentials = $this->getCredentials($superAdmin);
        } else {
            if (false !== strpos($options['user']['user_name'], 'weaving_the_web.quality_assurance')) {
                $credentials = [
                    'username' => $this->getParameter($options['user']['user_name']),
                    'password' => $this->getParameter($options['user']['password'])
                ];
            } else {
                $credentials = [
                    'username' => $options['user']['user_name'],
                    'password' => $options['user']['password']
                ];
            }
        }

        $server = array_merge($server,
            array(
                'PHP_AUTH_USER' => $credentials['username'],
                'PHP_AUTH_PW' => $credentials['password']
            ));

        $client = $this->getClient($options, $server);
        $client->followRedirects($followRedirects);

        return $client;
    }

    /**
     * @param $superAdmin
     * @return array
     */
    protected function getCredentials($superAdmin)
    {
        return [
            'username' => $this->getParameter('api_wtw_repositories_user_name' . ($superAdmin ? '_super' : '')),
            'password' => $this->getParameter('api_wtw_repositories_password')
        ];
    }

    /**
     * @param $name
     * @param array $options
     * @return bool
     */
    protected function extractOption($name, array &$options)
    {
        if (array_key_exists($name, $options)) {
            $option = $options[$name];
            unset($options[$name]);
        } else {
            $option = false;
        }

        return $option;
    }

    /**
     * @param $directories
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getFixtures($directories)
    {
        foreach ($directories as $directory) {
            $this->loader->loadFromDirectory($directory->getRealPath());
        }
        $fixtures = $this->loader->getFixtures();

        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('An error occurred on loading fixtures.')
            );
        }

        return $fixtures;
    }

    /**
     * @return mixed
     */
    public function getFixturesDirectories()
    {
        return $this->finder->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->depth('<= 5')
            ->directories()
            ->in(__DIR__ . '/../../../../')
            ->name('DataFixtures');
    }

    /**
     * @return mixed
     */
    public function getContainer()
    {
        return static::$kernel->getContainer();
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->getContainer()->getParameter($name);
    }

    /**
     * @param string $serviceId
     * @return mixed
     */
    public function getService($serviceId)
    {
        return $this->get($serviceId);
    }

    /**
     * @param  string $serviceId
     * @return mixed
     */
    public function get($serviceId)
    {
        return $this->getContainer()->get($serviceId);
    }

    /**
     * @return bool
     */
    public function requiredMySQLDatabase()
    {
        return false;
    }

    /**
     * @param  array  $options
     * @param  array  $server
     * @return \Symfony\Component\BrowserKit\Client
     */
    public function setupAuthenticatedClient(array $options = array(), $server = array())
    {
        $this->client = $this->getAuthenticatedClient($options, $server);

        return $this->client;
    }

    /**
     * @param $directory
     */
    public function removeDirectory($directory)
    {
        $projectDir = realpath($this->getParameter('kernel.root_dir') . '/..');
        $realTargetPath = realpath($directory);
        $directoryBelongsToProject = strpos($realTargetPath, $projectDir) !== false;

        if (file_exists($directory) && is_dir($directory) && $directoryBelongsToProject) {
            $files = glob($directory . '/*/*');

            foreach ($files as $file) {
                unlink($file);
            }

            $subdirectories = glob($directory . '/*');

            foreach ($subdirectories as $subdirectory) {
                rmdir($subdirectory);
            }

            rmdir($directory);
        }
    }

    /**
     * @param array $options
     * @return \Symfony\Component\HttpKernel\KernelInterface
     */
    public static function bootKernel(array $options = array())
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }

        static::$kernel = static::createKernel($options);
        static::$kernel->boot();

        return static::$kernel;
    }

    /**
     * @param $connection
     * @param $name
     * @return bool
     * @throws ConnectionException
     */
    private function dropOrCreateDatabase($connection, $name): bool
    {
        $databaseAlreadyExisted = false;

        try {
            $statement = $connection->executeQuery('SHOW DATABASES');
            $results = $statement->fetchAll();
            $databaseAlreadyExisted = count(array_filter(
                $results,
                function ($database) use ($name) {
                    return $database['Database'] === $name;
                }
            )) === 1;

        } catch (ConnectionException $exception) {
            // Unknown database, which might mean it has been dropped in the past
            if (!($exception->getPrevious() instanceof PDOException)
                || $exception->getPrevious()->getCode() !== 1049
            ) {
                throw $exception;
            }

            $this->createDatabase($name);
        }

        return $databaseAlreadyExisted;
    }

    /**
     * @return bool
     */
    private function shouldSkipSchemaCreation(): bool
    {
        return !$this->shouldNotSkipSchemaCreation();
    }

    /**
     * @return bool
     */
    private function shouldNotSkipSchemaCreation(): bool
    {
        $connection = $this->get('doctrine.dbal.test_mysql_connection');
        $statement = $connection->executeQuery('SHOW TABLES');

        return count($statement->fetchAll()) === 0;
    }
}
