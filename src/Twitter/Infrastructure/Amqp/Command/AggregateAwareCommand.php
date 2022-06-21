<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Command;

use App\Twitter\Infrastructure\PublishersList\AggregateAwareTrait;
use App\Twitter\Infrastructure\Api\Repository\PublishersListRepository;
use Doctrine\ORM\EntityManager;

/**
 * @package App\Twitter\Infrastructure\Amqp\Command
 */
abstract class AggregateAwareCommand extends AccessorAwareCommand
{
    use AggregateAwareTrait;

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
