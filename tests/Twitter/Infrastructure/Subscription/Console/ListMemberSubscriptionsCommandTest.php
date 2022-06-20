<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Subscription\Console;

use App\Twitter\Infrastructure\Subscription\Console\ListMemberSubscriptionsCommand;
use App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository\FriendsListCollectedEventRepositoryBuilder;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FriendsListAccessorBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group member_subscription
 */
class ListMemberSubscriptionsCommandTest extends KernelTestCase
{
    private ListMemberSubscriptionsCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        /** @var ListMemberSubscriptionsCommand $command */
        $command = self::$container->get('test.'.ListMemberSubscriptionsCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find('app:list-member-subscriptions');
        $this->command->setAccessor(FriendsListAccessorBuilder::build());
        $this->command->setRepository(FriendsListCollectedEventRepositoryBuilder::build());

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_lists_member_subscriptions(): void
    {
        $this->commandTester->execute([
            'screen_name' => 'mipsytipsy'
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
