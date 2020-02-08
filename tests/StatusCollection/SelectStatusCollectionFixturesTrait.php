<?php

namespace App\Tests\StatusCollection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use App\Api\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

trait SelectStatusCollectionFixturesTrait
{
    public function setUp()
    {
        parent::setUp();

        $this->client = $this->getClient();
        $this->removeFixtures();
    }

    public function tearDown()
    {
        $this->removeFixtures();

        parent::tearDown();
    }

    private function removeFixtures(): void
    {
        $timelyStatusRepository = $this->get('repository.timely_status');
        $timelyStatuses = $timelyStatusRepository->findBy([]);

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        (new ArrayCollection($timelyStatuses))->map(function ($timelyStatus) use ($entityManager) {
            $entityManager->remove($timelyStatus);
        });

        $entityManager->flush();

        /** @var StatusRepository $statusRepository */
        $statusRepository = $this->get('weaving_the_web_twitter.repository.status');
        $statuses = $statusRepository->findBy([]);

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        (new ArrayCollection($statuses))->map(function ($status) use ($entityManager) {
            $entityManager->remove($status);
        });

        $entityManager->flush();
    }

    /**
     * @param $earliestDate
     * @param $latestDate
     */
    private function saveStatuses(\DateTime $earliestDate, \DateTime $latestDate): void
    {
        /** @var StatusRepository $statusRepository */
        $statusRepository = $this->get('weaving_the_web_twitter.repository.status');
        /** @var AggregateRepository $aggregateRepository */
        $aggregateRepository = $this->get('weaving_the_web_twitter.repository.aggregate');

        $includingBobAggregate = $aggregateRepository->make('bob', 'news :: France');
        $aggregateRepository->save($includingBobAggregate);

        $includingAliceAggregate = $aggregateRepository->make('alice', 'news :: France');
        $aggregateRepository->save($includingAliceAggregate);

        $excludingAggregate = $aggregateRepository->make('alice', 'news :: Elsewhere');
        $aggregateRepository->save($excludingAggregate);

        $statusBeforeEarliestDate = $statusRepository->fromArray([
            'screen_name' => 'bob',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => (clone $earliestDate)->modify('-1 hour')
        ]);
        $statusAfterEarliestDate = $statusRepository->fromArray([
            'screen_name' => 'bob',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => $earliestDate,
            'aggregate' => $includingBobAggregate,
        ]);
        $statusAfterEarliestDateBelongingToAlice = $statusRepository->fromArray([
            'screen_name' => 'alice',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => $earliestDate,
            'aggregate' => $includingAliceAggregate
        ]);
        $statusBetweenDates = $statusRepository->fromArray([
            'screen_name' => 'bob',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => (clone $earliestDate)->modify('+1 hour'),
            'aggregate' => $excludingAggregate,
        ]);
        $statusBetweenDatesBelongingToAlice = $statusRepository->fromArray([
            'screen_name' => 'alice',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => (clone $earliestDate)->modify('+1 hour')
        ]);
        $statusBeforeLatestDate = $statusRepository->fromArray([
            'screen_name' => 'bob',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => $latestDate,
            'aggregate' => $excludingAggregate,
        ]);
        $statusAfterLatestDate = $statusRepository->fromArray([
            'screen_name' => 'bob',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => (clone $latestDate)->modify('+1 hour')
        ]);
        $statusRepository->saveBatch(new ArrayCollection([
            $statusBeforeEarliestDate,
            $statusAfterEarliestDate,
            $statusAfterEarliestDateBelongingToAlice,
            $statusBetweenDates,
            $statusBetweenDatesBelongingToAlice,
            $statusBeforeLatestDate,
            $statusAfterLatestDate
        ]));
    }
}
