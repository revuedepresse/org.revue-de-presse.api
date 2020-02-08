<?php

namespace App\Tests\StatusCollection\Mapping;

use App\StatusCollection\Mapping\Command\MapStatusCollectionCommand;
use App\StatusCollection\Mapping\RefreshStatusMapping;
use App\Tests\StatusCollection\SelectStatusCollectionFixturesTrait;
use Prophecy\Argument;
use App\Api\Entity\Status;
use WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase;

class MapStatusCollectionCommandTest extends CommandTestCase
{
    use SelectStatusCollectionFixturesTrait;

    /**
     * @test
     * @group it_should_execute_a_command_mapping_a_service_to_a_collection_of_statuses
     */
    public function it_should_execute_a_command_mapping_a_service_to_a_collection_of_statuses()
    {
        $this->commandClass = $this->getParameter('command.map_status_collection.class');
        $this->setUpApplication();

        $this->commandTester = $this->getCommandTester('press-review:map-status-collection');

        $earliestDate = '2018-08-19 23:00';
        $latestDate = '2018-08-20 22:59';
        $this->saveStatuses(new \DateTime($earliestDate), new \DateTime($latestDate));

        $refreshStatusProphecy = $this->prophesize(RefreshStatusMapping::class);
        $refreshStatusProphecy->setOAuthTokens(Argument::type('array'))
            ->willReturn();
        $refreshStatusProphecy->apply(Argument::type(Status::class))->will(function ($arguments) {
            return $arguments[0];
        });
        $refreshStatusMock = $refreshStatusProphecy->reveal();

        /** @var MapStatusCollectionCommand $command */
        $command = $this->getCommand();
        $command->statusRepository = $this->get('command.map_status_collection')->statusRepository;
        $command->refreshStatusMapping = $refreshStatusMock;

        $options = [
            'command' => $this->getCommandName(),
            '--mapping' => 'mapping.refresh_status',
            '--screen-name' => 'bob',
            '--earliest-date' => $earliestDate,
            '--latest-date' => $latestDate,
        ];
        $this->commandTester->execute($options);
        $commandDisplay = $this->commandTester->getDisplay();

        $this->assertContains(
            sprintf(
                '3 statuses of "%s" member between %s and %s have been mapped to "mapping.refresh_status".',
                'bob',
                $earliestDate,
                $latestDate
            ),
            $commandDisplay,
            'The command should exit with a success output display'
        );
        $this->assertEquals(
            0,
            $this->commandTester->getStatusCode(),
            'The command should exit with a success status code'
        );
    }
}
