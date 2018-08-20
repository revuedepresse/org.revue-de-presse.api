<?php

namespace App\Tests\StatusCollection;

use WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase;

class SelectStatusCollectionCommandTest extends CommandTestCase
{
    use SelectStatusCollectionFixturesTrait;

    /**
     * @test
     * @group it_should_execute_a_command_selecting_statuses_matching_criteria
     */
    public function it_should_count_statuses_matching_criteria()
    {
        $this->commandClass = $this->getParameter('command.select_status_collection.class');
        $this->setUpApplication();

        $this->commandTester = $this->getCommandTester('press-review:select-status-collection');

        $earliestDate = '2018-08-19 23:00';
        $latestDate = '2018-08-20 22:59';
        $this->saveStatuses(new \DateTime($earliestDate), new \DateTime($latestDate));

        $this->getCommand()->statusRepository = $this->get('command.select_status_collection')->statusRepository;

        $options = [
            'command' => $this->getCommandName(),
            '--screen-name' => 'bob',
            '--earliest-date' => $earliestDate,
            '--latest-date' => $latestDate,
        ];
        $this->commandTester->execute($options);
        $commandDisplay = $this->commandTester->getDisplay();

        $this->assertContains(
            sprintf(
            '3 statuses of "%s" member between %s and %s have been found.',
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
