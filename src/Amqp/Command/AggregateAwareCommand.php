<?php
declare(strict_types=1);

namespace App\Amqp\Command;

use App\Aggregate\AggregateAwareTrait;
use App\Api\Repository\AggregateRepository;
use App\Console\CommandReturnCodeAwareInterface;
use Doctrine\ORM\EntityManager;

/**
 * @package App\Amqp\Command
 */
abstract class AggregateAwareCommand extends AccessorAwareCommand implements CommandReturnCodeAwareInterface
{
    protected const NOT_FOUND_MEMBER = 10;

    protected const UNAVAILABLE_RESOURCE = 20;

    protected const UNEXPECTED_ERROR = 40;

    protected const SUSPENDED_USER = 50;

    protected const PROTECTED_ACCOUNT = 60;

    use AggregateAwareTrait;

    /**
     * @var AggregateRepository
     */
    protected AggregateRepository $aggregateRepository;

    /**
     * @param AggregateRepository $aggregateRepository
     *
     * @return $this
     */
    public function setAggregateRepository(AggregateRepository $aggregateRepository): self
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
