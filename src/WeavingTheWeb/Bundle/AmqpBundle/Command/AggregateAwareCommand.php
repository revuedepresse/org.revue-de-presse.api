<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command;

use App\Aggregate\AggregateAwareTrait;
use App\Console\CommandReturnCodeAwareInterface;

/**
 * @package WeavingTheWeb\Bundle\AmqpBundle\Command
 */
abstract class AggregateAwareCommand extends AccessorAwareCommand implements CommandReturnCodeAwareInterface
{
    use AggregateAwareTrait;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository
     */
    protected $aggregateRepository;

    /**
     * @var \WTW\UserBundle\Repository\UserRepository
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
