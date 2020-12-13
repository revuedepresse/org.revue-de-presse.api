<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Subscription\Console;

use App\Twitter\Infrastructure\Subscription\Console\ListMemberSubscribeesCommand;
use App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository\FollowersListCollectedEventRepositoryBuilder;
use App\Tests\Twitter\Infrastructure\Api\Builder\Accessor\FollowersListAccessorBuilder;
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

        self::$container = $kernel->getContainer();

        /** @var ListMemberSubscribeesCommand $command */
        $command = self::$container->get('test.'.ListMemberSubscribeesCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find('press-review:list-member-subscribees');
        $this->command->setAccessor(FollowersListAccessorBuilder::make());
        $this->command->setRepository(FollowersListCollectedEventRepositoryBuilder::make());

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