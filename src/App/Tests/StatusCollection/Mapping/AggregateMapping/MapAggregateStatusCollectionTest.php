<?php

namespace App\Tests\StatusCollection\Mapping\AggregateMapping;

use App\Tests\StatusCollection\SelectStatusCollectionFixturesTrait;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;
use WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase;

class MapAggregateStatusCollectionTest extends CommandTestCase
{
    use SelectStatusCollectionFixturesTrait;

    /**
     * @test
     * @group it_should_map_a_service_to_a_collection_of_aggregate_statuses
     */
    public function it_should_map_a_service_to_a_collection_of_aggregate_statuses()
    {
        /** @var StatusRepository $statusRepository */
        $statusRepository = $this->get('weaving_the_web_twitter.repository.status');

        $earliestDate = new \DateTime('2018-08-19 23:00', new \DateTimeZone('UTC'));
        $latestDate = new \DateTime('2018-08-20 22:59', new \DateTimeZone('UTC'));

        $this->saveStatuses($earliestDate, $latestDate);

        $refreshStatus = $this->get('mapping.identity');

        $aggregateStatusCollection = $statusRepository->selectAggregateStatusCollection(
            'news :: France',
            $earliestDate,
            $latestDate
        );

        $totalStatusesMappedToAService = $statusRepository->mapStatusCollectionToService(
            $refreshStatus,
            $aggregateStatusCollection
        );

        $this->assertEquals(
            2,
            $totalStatusesMappedToAService->count(),
            'There should be three statuses mapped to a service'
        );
    }
}
