<?php
declare (strict_types=1);

namespace App\Tests\Domain\Subscription\Console;

use App\Domain\Subscription\Console\UnfollowInactiveMembersCommand;
use App\Tests\Builder\Infrastructure\Collection\Repository\FriendsListCollectedEventRepositoryBuilder;
use App\Tests\Builder\Twitter\Api\Mutator\FriendshipMutatorBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group member_subscription
 */
class UnfollowInactiveMembersCommandTest extends KernelTestCase
{
    private UnfollowInactiveMembersCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        /** @var UnfollowInactiveMembersCommand $command */
        $command = self::$container->get('test.'.UnfollowInactiveMembersCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find('press-review:unfollow-inactive-members');
        $this->command->setRepository(FriendsListCollectedEventRepositoryBuilder::make());
        $this->command->setMutator(FriendshipMutatorBuilder::make());

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_unfollow_inactive_members(): void
    {
        $this->commandTester->execute(['screen_name' => 'thierrymarianne']);

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of a command should be successful',
        );
    }
}