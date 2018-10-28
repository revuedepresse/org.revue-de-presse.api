<?php

namespace App\Aggregate;

trait AggregateAwareTrait
{
    /**
     * @param string      $screenName
     * @param string      $listName
     * @param string|null $listId
     * @return mixed
     */
    protected function getListAggregateByName(
        string $screenName,
        string $listName,
        string $listId = null
    ) {
        $aggregate = $this->aggregateRepository->findOneBy([
            'name' => $listName,
            'screenName' => $screenName
        ]);

        if (!$aggregate) {
            $aggregate = $this->aggregateRepository->make(
                $screenName,
                $listName
            );

            $this->entityManager->persist($aggregate);
        }

        if (!is_null($listId)) {
            $aggregate->listId = $listId;
        }

        $this->entityManager->flush();

        return $aggregate;
    }
}
