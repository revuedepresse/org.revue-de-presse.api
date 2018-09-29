<?php

namespace App\Aggregate;

trait AggregateAwareTrait
{
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
