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
        $list = $this->aggregateRepository->make(
            $screenName,
            $listName
        );

        $this->entityManager->persist($list);

        if ($listId !== null) {
            $list->listId = $listId;
        }

        $this->entityManager->flush();

        return $list;
    }
}
