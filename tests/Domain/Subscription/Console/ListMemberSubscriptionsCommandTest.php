<?php
declare (strict_types=1);

namespace App\Tests\Domain\Subscription\Console;

use App\Domain\Subscription\Console\ListMemberSubscriptionsCommand;
use App\Tests\Builder\Twitter\Api\Accessor\FriendsAccessorBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;

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

        /** @var Command $command */
        $command = self::$container->get(ListMemberSubscriptionsCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find('press-review:list-member-subscriptions');
        $this->command->setAccessor(FriendsAccessorBuilder::make());

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
            'The status code of a command should be successful',
        );

        $display = $this->commandTester->getDisplay();

        self::assertContains(
            'Name',
            $display,
            'The command output contains a name.'
        );

        self::assertContains(
            'Description',
            $display,
            'The command output contains a description.'
        );

        self::assertContains(
            'URL',
            $display,
            'The command output contains a URL.'
        );

        self::assertContains(
            'Followers',
            $display,
            'The command output contains a followers count.'
        );

        self::assertContains(
            'Friends',
            $display,
            'The command output contains a friends count.'
        );

        self::assertContains(
            'Location',
            $display,
            'The command output contains a location.'
        );
    }
}