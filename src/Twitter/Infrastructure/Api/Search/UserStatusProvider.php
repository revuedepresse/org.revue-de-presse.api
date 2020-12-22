<?php

namespace App\Twitter\Infrastructure\Api\Search;

use Doctrine\ORM\NoResultException;
use FOS\ElasticaBundle\Doctrine\ORM\Provider;

class UserStatusProvider extends Provider
{
    /**
     * @see FOS\ElasticaBundle\Provider\ProviderInterface::populate()
     */
    public function populate(\Closure $loggerClosure = null, array $options = array())
    {
        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->createQueryBuilder();
        $nbObjects = $this->countObjects($queryBuilder);
        $offset = isset($options['offset']) ? (int) $options['offset'] : 0;
        $sleep = isset($options['sleep']) ? (int) $options['sleep'] : 0;
        $batchSize = isset($options['batch-size']) ? (int) $options['batch-size'] : $this->options['batch_size'];

        for (; $offset < $nbObjects; $offset += $batchSize) {
            if ($loggerClosure) {
                $stepStartTime = microtime(true);
            }
            $objects = $this->fetchSlice($queryBuilder, $batchSize, $offset);

            if (count($objects) > 0) {
                $this->objectPersister->insertMany($objects);
                foreach ($objects as $object) {
                    $object->setIndexed(true);
                }

                $manager = $this->managerRegistry->getManager();
                $manager->persist($object);
                $manager->flush();

                if ($this->options['clear_object_manager']) {
                    $this->managerRegistry->getManagerForClass($this->objectClass)->clear();
                }

                usleep($sleep);

                if ($loggerClosure) {
                    $stepNbObjects = count($objects);
                    $stepCount = $stepNbObjects + $offset;
                    $percentComplete = 100 * $stepCount / $nbObjects;
                    $objectsPerSecond = $stepNbObjects / (microtime(true) - $stepStartTime);
                    $loggerClosure(sprintf('%0.1f%% (%d/%d), %d objects/s', $percentComplete, $stepCount, $nbObjects, $objectsPerSecond));
                }
            } elseif ($offset > 0) {
                try {
                    /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
                    $queryBuilder = $this->createQueryBuilder();
                    $nbObjects = $this->countObjects($queryBuilder);
                } catch (NoResultException $exception) {
                    $nbObjects = ceil($nbObjects / 2);
                    $loggerClosure('Could not count remaining objects using database engine');
                }

                $offset = 0;
            }
        }
    }
}