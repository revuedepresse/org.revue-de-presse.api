<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\Command;

use App\PublicationList\AggregateAwareTrait;
use App\Infrastructure\Api\Repository\PublicationListRepository;
use App\Infrastructure\Console\CommandReturnCodeAwareInterface;
use Doctrine\ORM\EntityManager;

/**
 * @package App\Infrastructure\Amqp\Command
 */
abstract class AggregateAwareCommand extends AccessorAwareCommand implements CommandReturnCodeAwareInterface
{
    use AggregateAwareTrait;

    protected PublicationListRepository $aggregateRepository;

    public function setAggregateRepository(PublicationListRepository $aggregateRepository): self
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
