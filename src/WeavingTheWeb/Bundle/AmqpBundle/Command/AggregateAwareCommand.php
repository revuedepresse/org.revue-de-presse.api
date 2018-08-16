<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command;

/**
 * @package WeavingTheWeb\Bundle\AmqpBundle\Command
 */
abstract class AggregateAwareCommand extends AccessorAwareCommand
{
    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository
     */
    protected $aggregateRepository;

    /**
     * @var \WTW\UserBundle\Repository\UserRepository
     */
    protected $userRepository;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    protected function setupAggregateRepository()
    {
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->aggregateRepository = $this->getContainer()->get('weaving_the_web_twitter.repository.aggregate');
        $this->userRepository = $this->getContainer()->get('user_manager');
    }

    /**
     * @param $screenName
     * @param $listName
     * @return null|object|\WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function getListAggregateByName($screenName, $listName)
    {
        $aggregate = $this->aggregateRepository->findOneBy(['name' => $listName, 'screenName' => $screenName]);

        if (!$aggregate) {
            $aggregate = $this->aggregateRepository->make($screenName, $listName);

            $this->entityManager->persist($aggregate);
            $this->entityManager->flush();
        }

        return $aggregate;
    }
}
