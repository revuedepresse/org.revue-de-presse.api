<?php

namespace App\Tests\StatusSelection;

use Doctrine\Common\Collections\ArrayCollection;
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

    /**
     * @param $query
     * @return mixed
     */
    private function logExceptionOnQueryExecution($query)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        try {
            return $entityManager->getConnection()->executeQuery($query);
        } catch (\Exception $exception) {
            $this->get('monolog.logger.development')->error(
                sprintf(
                    'An error occurred when tearing down the test (message: "%s")',
                    $exception->getMessage()
                )
            );
        }
    }

    private function removeFixtures(): void
    {
        // Remove previously inserted records of aggregates
        $query = <<<QUERY
            DELETE FROM weaving_aggregate WHERE id in (
                SELECT aggregate_id FROM weaving_status_aggregate WHERE status_id IN (
                    SELECT id FROM weaving_status WHERE ust_created_at >= '2018-08-19 22:00'
                    AND ust_created_at <= '2018-08-20 23:59'
                )
            );
QUERY;
        $this->logExceptionOnQueryExecution($query);

        // Remove previously insert records of statuses
        $query = <<<QUERY
            DELETE FROM weaving_status WHERE ust_created_at >= '2018-08-19 22:00'
            AND ust_created_at <= '2018-08-20 23:59'
QUERY;
        $this->logExceptionOnQueryExecution($query);
    }

    /**
     * @param $earliestDate
     * @param $latestDate
     */
    private function saveStatuses(\DateTime $earliestDate, \DateTime $latestDate): void
    {
        /** @var StatusRepository $statusRepository */
        $statusRepository = $this->get('weaving_the_web_twitter.repository.status');

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
            'created_at' => $earliestDate
        ]);
        $statusAfterEarliestDateBelongingToAlice = $statusRepository->fromArray([
            'screen_name' => 'alice',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => $earliestDate
        ]);
        $statusBetweenDates = $statusRepository->fromArray([
            'screen_name' => 'bob',
            'name' => '',
            'text' => '',
            'user_avatar' => '',
            'identifier' => '1',
            'created_at' => (clone $earliestDate)->modify('+1 hour')
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
            'created_at' => $latestDate
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
