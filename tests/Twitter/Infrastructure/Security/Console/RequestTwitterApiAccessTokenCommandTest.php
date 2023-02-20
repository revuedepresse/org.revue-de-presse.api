<?php

namespace App\Tests\Twitter\Infrastructure\Security\Console;

use App\Twitter\Infrastructure\Security\Console\RequestTwitterApiAccessTokenCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group request_token
 */
class RequestTwitterApiAccessTokenCommandTest extends TestCase
{
    private RequestTwitterApiAccessTokenCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        $command = static::getContainer()->get('test.'.RequestTwitterApiAccessTokenCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find(RequestTwitterApiAccessTokenCommand::COMMAND_NAME);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_terminates_successfully()
    {
        // Act
        $this->commandTester->execute([]);

        // Assert

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            sprintf(
                'When executing "%s" command, it status code should should be %d (for a successfully executed command)',
                $this->command::COMMAND_NAME,
                $this->command::SUCCESS
            )
        );
    }
}