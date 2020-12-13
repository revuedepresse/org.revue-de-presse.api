<?php
declare(strict_types=1);

namespace App\PublishersList;

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
        $aggregate = $this->aggregateRepository->make(
            $screenName,
            $listName
        );

        $this->entityManager->persist($aggregate);

        if ($listId !== null) {
            $aggregate->listId = $listId;
        }

        $this->entityManager->flush();

        return $aggregate;
    }
}
