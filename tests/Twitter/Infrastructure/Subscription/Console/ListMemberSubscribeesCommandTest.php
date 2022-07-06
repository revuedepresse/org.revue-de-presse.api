<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Subscription\Console;

use App\Twitter\Infrastructure\Subscription\Console\ListMemberSubscribeesCommand;
use App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository\FollowersListCollectedEventRepositoryBuilder;
use App\Tests\Twitter\Infrastructure\Http\Builder\Client\FollowersBatchAwareHttpClientBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group member_subscribee
 */
class ListMemberSubscribeesCommandTest extends KernelTestCase
{
    private ListMemberSubscribeesCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        /** @var ListMemberSubscribeesCommand $command */
        $command = static::getContainer()->get('test.'.ListMemberSubscribeesCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find('app:list-member-subscribees');
        $this->command->setCursorAwareHttpClient(FollowersBatchAwareHttpClientBuilder::build());
        $this->command->setRepository(FollowersListCollectedEventRepositoryBuilder::build());

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_lists_member_subscribees(): void
    {
        $this->commandTester->execute([
            'screen_name' => 'thierrymarianne'
        ]);

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The return code of this command execution should be successful.',
        );

        $display = $this->commandTester->getDisplay();

        self::assertStringContainsString(
            'Name',
            $display,
            'The command output contains a name.'
        );

        self::assertStringContainsString(
            'Description',
            $display,
            'The command output contains a description.'
        );

        self::assertStringContainsString(
            'URL',
            $display,
            'The command output contains a URL.'
        );

        self::assertStringContainsString(
            'Followers',
            $display,
            'The command output contains a followers count.'
        );

        self::assertStringContainsString(
            'Friends',
            $display,
            'The command output contains a friends count.'
        );

        self::assertStringContainsString(
            'Location',
            $display,
            'The command output contains a location.'
        );
    }
}
