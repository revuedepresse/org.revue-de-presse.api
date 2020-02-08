<?php

namespace App\Amqp\Command;

use App\Aggregate\AggregateAwareTrait;
use App\Console\CommandReturnCodeAwareInterface;

/**
 * @package WeavingTheWeb\Bundle\AmqpBundle\Command
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
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository
     */
    protected $aggregateRepository;

    /**
     * @var \WTW\UserBundle\Repository\MemberRepository
     */
    protected $userRepository;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    protected function setupAggregateRepository()
    {
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->aggregateRepository = $this->getContainer()->get('weaving_the_web_twitter.repository.aggregate');
        $this->userRepository = $this->getContainer()->get('user_manager');
    }
}
