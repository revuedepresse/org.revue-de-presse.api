<?php

namespace WeavingTheWeb\Bundle\ApiBundle\ORM;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\QueryBuilder,
    Doctrine\Common\Util\Inflector;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Json;

/**
 * Class QueryFactory
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WeavingTheWeb\Bundle\ApiBundle\ORM
 */
class QueryFactory
{
    /**
     * @var $entityManager EntityManager
     */
    protected $entityManager;

    protected $logger;

    public function __construct($entityManager, $logger = null)
    {
        $this->entityManager = $entityManager;
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->logger = $logger;
    }

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function getEntityRepository($repository)
    {
        return $this->entityManager->getRepository($repository);
    }

    public function prepareQueryBuilder($repository, $alias)
    {
        $repository = $this->getEntityRepository($repository);

        return $repository->createQueryBuilder($alias);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param $column
     * @param $alias
     * @return QueryFactory
     */
    public function filterByColumnValue(QueryBuilder $queryBuilder, $column, $alias)
    {
        list($name, $value) = each($column);
        $parameterName = $alias . '_' . $name;

        $queryBuilder->andWhere($alias . '.' . $name .' = :' . $parameterName);
        $queryBuilder->setParameter($parameterName, $value);

        return $this;
    }

    /**
     * @param array $constraints
     *
     * @return mixed
     */
    public function countDocuments(array $constraints = [])
    {
        // disables pagination
        unset($constraints['limit']);
        unset($constraints['offset']);

        $alias = $this->getJsonAlias();
        $queryBuilder = $this->prepareQueryBuilder(
            'WeavingTheWebApiBundle:Json', $alias);
        $this->count($queryBuilder, $alias);
        $this->addConstraints($queryBuilder, $constraints, $alias);

        $sql = $queryBuilder->getQuery()->getSql();
        $sql = preg_replace('#count\([^\)]+\) AS \S+#', 'count(*) as count_', $sql);
        list(, $result) = each($this->entityManager->getConnection()->executeQuery($sql,
            array($constraints['status'], $constraints['type']))->fetchAll(\PDO::FETCH_NUM)[0]);

        return $result;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param              $alias
     *
     * @return QueryBuilder
     */
    public function count(QueryBuilder $queryBuilder, $alias) {
        return $queryBuilder->select('count(' . $alias . ')');
    }

    /**
     * @param array $constraints
     * @param null  $offset
     * @param null  $limit
     *
     * @return mixed
     */
    public function selectJson(array $constraints = array(), $offset = null, $limit = null)
    {
        $jsonAlias    = $this->getJsonAlias();
        $queryBuilder = $this->prepareQueryBuilder('WeavingTheWebApiBundle:Json', $jsonAlias);
        $queryBuilder->orderBy($jsonAlias . '.id', 'DESC');

        $constraints['offset'] = $limit;
        $constraints['limit'] = $offset;
        $this->addConstraints($queryBuilder, $constraints, $jsonAlias);

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $constraints
     * @param              $alias
     */
    public function addConstraints(QueryBuilder $queryBuilder, array $constraints = array(), $alias)
    {
        $constraints = $this->paginates($queryBuilder, $constraints);
        $this->orderBy($constraints, $queryBuilder, $alias);
        $this->groupBy($constraints, $queryBuilder, $alias);

        foreach ($constraints as $column => $value) {
            $this->addConstraint($queryBuilder, $column, $value, $alias);
        }
    }

    /**
     * @param $constraints
     * @param $key
     */
    public function ensuresHasArrayValue(&$constraints, $key) {
        if (array_key_exists($key, $constraints)) {
            $sortingColumns = $constraints[$key];
        } else {
            $sortingColumns = array();
        }

        $constraints[$key] = $sortingColumns;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param              $name
     * @param              $value
     * @param              $alias
     *
     * @return $this
     */
    public function addConstraint(QueryBuilder $queryBuilder, $name, $value, $alias)
    {
        if (is_string($value)) {
            $operand = ' LIKE ';
            $value = '%' . $value . '%';
        } else {
            $operand = ' = ';
        }

        $queryBuilder->andWhere($alias . '.' . $name . ' ' .
            $operand . ' :'.$name);
        $queryBuilder->setParameter($name, $value);

        return $this;
    }

    /**
     * @param       $repository
     * @param array $constraints
     *
     * @return mixed
     */
    public function getSelectQueryBuilder(
        $repository,
        array $constraints = array())
    {
        list($alias, $repository) = each($repository);

        $queryBuilder = $this->prepareQueryBuilder($repository, $alias );
        $this->orderBy($constraints, $queryBuilder, $alias);
        $this->addConstraints($queryBuilder, $constraints, $alias);

        return $queryBuilder;
    }

    /**
     * @param $queryBuilder
     * @param $constraints
     *
     * @return mixed
     */
    public function paginates($queryBuilder, $constraints)
    {
        $offset = $this->validatesPaginationConstraint('offset', $constraints);
        if (!is_null($offset)) {
            $queryBuilder->setFirstResult($offset);
        }
        $limit = $this->validatesPaginationConstraint('limit', $constraints);
        if (!is_null($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        return $constraints;
    }

    /**
     * @param array $constraints
     * @param       $queryBuilder
     * @param       $alias
     */
    public function groupBy(array &$constraints, QueryBuilder $queryBuilder, $alias)
    {
        $this->ensuresHasArrayValue($constraints, 'grouping_columns');
        $groupedColumns = $constraints['grouping_columns'];

        foreach ($groupedColumns as $column) {
            $queryBuilder->groupBy($alias . '.' . $column);
        }

        unset($constraints['grouping_columns']);
    }

    /**
     * @param array $constraints
     * @param       $queryBuilder
     * @param       $alias
     */
    public function orderBy(array &$constraints, QueryBuilder $queryBuilder, $alias)
    {
        $this->ensuresHasArrayValue($constraints, 'sorting_columns');
        $sortingColumns = $constraints['sorting_columns'];

        foreach ($sortingColumns as $dir => $column) {
            $queryBuilder->orderBy($alias . '.' . $column, $dir);
        }

        unset($constraints['sorting_columns']);
    }

    /**
     * @param       $repository
     * @param array $constraints
     *
     * @return mixed
     */
    public function querySelection(
        $repository,
        array $constraints = array())
    {
        $alias = $this->getRepositoryAlias($repository);
        $offset = $this->validatesPaginationConstraint('offset', $constraints);
        $limit = $this->validatesPaginationConstraint('limit', $constraints);

        return $this->getSelectQueryBuilder(
            array($alias => $repository),
            array_merge(array(
                'limit' => $limit,
                'offset' => $offset), $constraints));
    }

    /**
     * @param $repository
     *
     * @return string
     */
    public function getRepositoryAlias($repository)
    {
        return '_' . substr(sha1($repository), 8);
    }

    public function validatesPaginationConstraint($name, &$constraints)
    {
        if (false !== array_key_exists($name, $constraints)) {
            $constraint = $constraints[$name];
            unset($constraints[$name]);
        } else {
            $constraint = null;
        }

        return $constraint;
    }

    /**
     * @return string
     */
    public function getGithubRepositoriesAlias()
    {
        return 'g';
    }

    /**
     * @return string
     */
    public function getJsonAlias()
    {
        return 'j';
    }

    /**
     * @param array $constraints
     * @param null  $offset
     * @param null  $limit
     *
     * @return mixed
     */
    public function queryJsonSelection(
        array $constraints = array(),
        $offset = null,
        $limit = null)
    {
        return $this->querySelection('WeavingTheWebApiBundle:Json',
            array_merge(array(
                'limit' => $limit,
                'offset' => $offset), $constraints));
    }

    /**
     * @param array $feeds
     *
     * @return int
     */
    public function serializeFeed(array $feeds = array())
    {
        return $this->serializeResource($feeds, 'makeFeed');
    }

    /**
     * @param array $repositories
     *
     * @return int
     */
    public function serializeRepository(array $repositories = array())
    {
        return $this->serializeResource($repositories, 'makeGithubRepository');
    }

    /**
     * @param array $repositories
     *
     * @return int
     */
    public function serializeUserStream(array $repositories = array())
    {
        return $this->serializeResource($repositories, 'makeUserStream');
    }

    /**
     * @param array $collection
     * @param       $maker
     *
     * @return int
     */
    public function serializeResource(array $collection = array(), $maker)
    {
        $repositoriesCount = 0;
        $delimiter = $this->getDelimiter();

        foreach ($collection as $id => $stores) {
            list($jsonId, $owner) = explode($delimiter, $id);
            foreach ($stores as $store) {
                if ($maker === 'makeFeed') {
                    foreach ($store as $publication) {

                        $instance = $this->$maker($publication,
                            'WTW\CodeGeneration\AnalysisBundle\Entity\\' . ucfirst($publication['type']));

                        if (!is_null($instance)) {
                            $this->entityManager->persist($instance);
                            $repositoriesCount++;
                        }
                    }
                } else {
                    $store['identifier'] = $owner;
                    $instance = $this->$maker($store);

                    $this->entityManager->persist($instance);
                    $repositoriesCount++;
                }
            }

            $this->disableJsonById($jsonId);
            $this->entityManager->flush();
        }

        if ($repositoriesCount > 0) {
            $this->entityManager->clear();
        }

        return $repositoriesCount;
    }

    /**
     * @return string
     */
    public function getDelimiter() {
        return '$_$_$';
    }

    /**
     * @param $store
     *
     * @return GithubRepository
     */
    public function makeGithubRepository($store)
    {
        $properties = [
            'github_id'   => $store['id'],
            'forks'       => $store['forks'],
            'watchers'    => $store['watchers'],
            'clone_url'   => $store['clone_url'],
            'avatar_url'  => $store['owner']['avatar_url'],
            'description' => $store['description'],
            'name'        => $store['name'],
            'owner'       => $store['identifier'],
            'owner_id'    => $store['owner']['id'],
            'language'    => $store['language'],
            'status'      => 0
        ];
        return $this->makeInstance('WeavingTheWeb\Bundle\ApiBundle\GithubRepository', $properties);
    }


    /**
     * @param $store
     *
     * @return UserStream
     */
    public function makeUserStream($store)
    {
        return $this->makeInstance('WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream', $store);
    }

    /**
     * @param $store
     * @param $type
     *
     * @return mixed
     */
    public function makeFeed($store, $type)
    {
        return $this->makeInstance($type, $store);
    }

    /**
     * @param $entity
     * @param $properties
     *
     * @return mixed
     */
    public function makeInstance($entity, $properties)
    {
        $instance = null;

        if (class_exists($entity)) {
            $instance = new $entity();
            $instance = $this->setProperties($instance, $properties);
        }

        return $instance;
    }

    /**
     * @param $jsonId
     */
    public function disableJsonById($jsonId)
    {
        $queryBuilder = $this->queryJsonSelection(array('id' => (int)$jsonId));
        $results = $queryBuilder->getQuery()->getResult();

        if (count($results) && (false !== array_key_exists(0, $results))) {
            /**
             * @var $json Json
             */
            $json = $results[0];
            $json->setStatus(0);
            $this->entityManager->persist($json);

            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }

    /**
     * @param $instance
     * @param $properties
     */
    public function setProperties($instance, $properties)
    {
        if (!array_key_exists('created_at', $properties)) {
            $properties['created_at'] = new \DateTime();
        }
        if (!array_key_exists('updated_at', $properties)) {
            $properties['updated_at'] = null;
        }

        $entity = get_class($instance);
        $fieldNames = $this->getEntityManager()->getClassMetadata($entity)->getFieldNames();
        $missedProperties = [];

        foreach ($properties as $name => $value) {
            $classifiedName = Inflector::classify($name);

            if (in_array(lcfirst($classifiedName), $fieldNames)) {
                $method = 'set' . $classifiedName;
                $instance->$method($value);
            } else {
                $missedProperties[] = $name .': ' . $value;
            }
        }

        if (count($missedProperties) >  0) {
            $output = 'property missed at introspection for entity ' . $entity . "\n" .
                implode("\n", $missedProperties ) . "\n";
            echo $output;
        }

        return $instance;
    }
}