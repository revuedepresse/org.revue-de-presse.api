<?php
declare(strict_types=1);

namespace App\Amqp\Command;

use App\Aggregate\AggregateAwareTrait;
use App\Api\Repository\PublicationListRepository;
use App\Console\CommandReturnCodeAwareInterface;
use App\Domain\Membership\MemberFacingStrategyInterface;
use Doctrine\ORM\EntityManager;

/**
 * @package App\Amqp\Command
 */
abstract class AggregateAwareCommand extends AccessorAwareCommand implements CommandReturnCodeAwareInterface
{
    use AggregateAwareTrait;

    /**
     * @var PublicationListRepository
     */
    protected PublicationListRepository $aggregateRepository;

    /**
     * @param PublicationListRepository $aggregateRepository
     *
     * @return $this
     */
    public function setAggregateRepository(PublicationListRepository $aggregateRepository): self
    {
        $this->aggregateRepository = $aggregateRepository;

        return $this;
    }

    /**
     * @var EntityManager
     */
    protected EntityManager $entityManager;

    /**
     * @param EntityManager $entityManager
     *
     * @return $this
     */
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
