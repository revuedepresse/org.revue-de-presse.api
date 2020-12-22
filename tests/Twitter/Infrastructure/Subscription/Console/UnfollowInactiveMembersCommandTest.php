<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Subscription\Console;

use App\Twitter\Infrastructure\Subscription\Console\UnfollowInactiveMembersCommand;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Membership\Domain\Entity\Legacy\Member;
use App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository\FriendsListCollectedEventRepositoryBuilder;
use App\Tests\Membership\Builder\Repository\MemberRepositoryBuilder;
use App\Tests\Twitter\Infrastructure\Api\Builder\Mutator\FriendshipMutatorBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group member_subscription
 */
class UnfollowInactiveMembersCommandTest extends KernelTestCase
{
    private const SUBSCRIBER_SCREEN_NAME = 'thierrymarianne';

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
        $this->command->setListCollectedEventRepository(FriendsListCollectedEventRepositoryBuilder::make());
        $this->command->setMemberRepository($this->buildMemberRepository());
        $this->command->setMutator(FriendshipMutatorBuilder::make());

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_unfollows_inactive_members(): void
    {
        $this->commandTester->execute(['screen_name' => self::SUBSCRIBER_SCREEN_NAME]);

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of a command should be successful',
        );
    }

    private function buildMemberRepository(): MemberRepositoryInterface
    {
        return MemberRepositoryBuilder::newMemberRepositoryBuilder()
            ->willFindAMemberByTwitterScreenName(
                self::SUBSCRIBER_SCREEN_NAME,
                (new Member())->setScreenName(self::SUBSCRIBER_SCREEN_NAME)
            )
            ->build();
    }
}