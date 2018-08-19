<?php

namespace App\Tests\StatusSelection;

use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;
use WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase;

class StatusCollectionTest extends CommandTestCase
{
    use SelectStatusCollectionFixturesTrait;

    /**
     * @test
     * @group it_should_select_statuses_matching_criteria
     */
    public function it_should_select_statuses_matching_criteria()
    {
        /** @var StatusRepository $statusRepository */
        $statusRepository = $this->get('weaving_the_web_twitter.repository.status');

        $earliestDate = new \DateTime('2018-08-19 23:00', new \DateTimeZone('UTC'));
        $latestDate = new \DateTime('2018-08-20 22:59', new \DateTimeZone('UTC'));

        $this->saveStatuses($earliestDate, $latestDate);

        $totalStatuses = $statusRepository->selectStatusCollection(
            'bob',
            $earliestDate,
            $latestDate
        );

        $this->assertEquals(
            3,
            $totalStatuses->count(),
            'There should be at least one status in the selection'
        );
    }
}
