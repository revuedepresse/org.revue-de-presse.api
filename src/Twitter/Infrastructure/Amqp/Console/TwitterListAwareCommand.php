<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Console;

use App\Twitter\Infrastructure\Http\Repository\PublishersListRepository;
use App\Twitter\Infrastructure\PublishersList\TwitterListAwareTrait;
use Doctrine\ORM\EntityManager;

abstract class TwitterListAwareCommand extends TwitterApiAwareCommand
{
    use TwitterListAwareTrait;

    protected PublishersListRepository $aggregateRepository;

    public function setAggregateRepository(PublishersListRepository $aggregateRepository): self
    {
        $this->aggregateRepository = $aggregateRepository;

        return $this;
    }

    protected EntityManager $entityManager;

    public function setEntityManager(EntityManager $entityManager): self
    {
        $this->entityManager = $entityManager;

        return $this;
    }
}
