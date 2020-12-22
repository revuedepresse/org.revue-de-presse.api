<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Command;

use App\PublishersList\AggregateAwareTrait;
use App\Twitter\Infrastructure\Api\Repository\PublishersListRepository;
use App\Twitter\Infrastructure\Console\CommandReturnCodeAwareInterface;
use Doctrine\ORM\EntityManager;

/**
 * @package App\Twitter\Infrastructure\Amqp\Command
 */
abstract class AggregateAwareCommand extends AccessorAwareCommand implements CommandReturnCodeAwareInterface
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

    protected function setupAggregateRepository()
    {
        // noop for backward compatibility
        // TODO remove all 5 calls to this method
    }
}
