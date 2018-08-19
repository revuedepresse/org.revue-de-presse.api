<?php

namespace App\Tests\StatusSelection;

use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;
use WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase;

class MapSelectionCommandTest extends CommandTestCase
{
    use SelectStatusCollectionFixturesTrait;

    /**
     * @test
     * @group it_should_map_a_service_to_a_selection_of_statuses
     */
    public function it_should_map_a_service_to_a_selection_of_statuses()
    {
        /** @var StatusRepository $statusRepository */
        $statusRepository = $this->get('weaving_the_web_twitter.repository.status');

        $earliestDate = new \DateTime('2018-08-19 23:00', new \DateTimeZone('UTC'));
        $latestDate = new \DateTime('2018-08-20 22:59', new \DateTimeZone('UTC'));

        $this->saveStatuses($earliestDate, $latestDate);

        $refreshStatus = $this->get('mapping.identity');

        $statusCollection = $statusRepository->selectStatusCollection(
            'bob',
            $earliestDate,
            $latestDate
        );

        $totalStatusesMappedToAService = $statusRepository->mapStatusCollectionToService(
            $refreshStatus,
            $statusCollection
        );

        $this->assertEquals(
            3,
            $totalStatusesMappedToAService->count(),
            'There should be three statuses mapped to a service'
        );
    }
}
