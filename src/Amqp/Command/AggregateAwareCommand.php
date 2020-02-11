<?php

namespace App\Amqp\Command;

use App\Aggregate\AggregateAwareTrait;
use App\Api\Repository\AggregateRepository;
use App\Console\CommandReturnCodeAwareInterface;
use App\Membership\Repository\MemberRepository;
use Doctrine\ORM\EntityManager;

/**
 * @package App\Amqp\Command
 */
abstract class AggregateAwareCommand extends AccessorAwareCommand implements CommandReturnCodeAwareInterface
{
    const NOT_FOUND_MEMBER = 10;

    const UNAVAILABLE_RESOURCE = 20;

    const API_ERROR = 30;

    const UNEXPECTED_ERROR = 40;

    const SUSPENDED_USER = 50;

    const PROTECTED_ACCOUNT = 60;

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
     * @var MemberRepository
     */
    protected MemberRepository $userRepository;

    /**
     * @param MemberRepository $memberRepository
     *
     * @return $this
     */
    public function setMemberRepository(MemberRepository $memberRepository): self
    {
        $this->userRepository = $memberRepository;

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
