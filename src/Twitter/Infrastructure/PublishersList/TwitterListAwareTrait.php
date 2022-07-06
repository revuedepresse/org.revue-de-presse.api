<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\PublishersList;

trait TwitterListAwareTrait
{

    protected function byName(
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
