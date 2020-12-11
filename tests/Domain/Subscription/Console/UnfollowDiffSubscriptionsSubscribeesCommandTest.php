<?php
declare (strict_types=1);

namespace App\Tests\Domain\Subscription\Console;

use App\Domain\Resource\MemberCollection;
use App\Domain\Subscription\Console\UnfollowDiffSubscriptionsSubscribeesCommand;
use App\Infrastructure\Repository\Membership\MemberRepositoryInterface;
use App\Infrastructure\Twitter\Api\Mutator\FriendshipMutatorInterface;
use App\Member\Repository\NetworkRepositoryInterface;
use App\Membership\Entity\MemberInterface;
use App\Tests\Builder\Infrastructure\Collection\Repository\FollowersListCollectedEventRepositoryBuilder;
use App\Tests\Builder\Infrastructure\Collection\Repository\FriendsListCollectedEventRepositoryBuilder;
use App\Tests\Builder\MemberRepositoryBuilder;
use App\Membership\Entity\Member;
use PhpParser\Node\Arg;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group diffing
 */
class UnfollowDiffSubscriptionsSubscribeesCommandTest extends KernelTestCase
{
    private const SUBSCRIBER_SCREEN_NAME = 'thierrymarianne';

    private UnfollowDiffSubscriptionsSubscribeesCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        self::$container = $kernel->getContainer();

        /** @var UnfollowDiffSubscriptionsSubscribeesCommand $command */
        $command = self::$container->get('test.'.UnfollowDiffSubscriptionsSubscribeesCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find('press-review:unfollow-diff-subscriptions-subscribees');
        $this->command->setSubscriptionsRepository(FriendsListCollectedEventRepositoryBuilder::make());
        $this->command->setSubscribeesRepository(FollowersListCollectedEventRepositoryBuilder::make());
        $this->command->setMemberRepository($this->buildMemberRepository());
        $this->command->setMutator($this->buildMutator());
        $this->command->setNetworkRepository($this->buildNetworkRepository());

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function it_diffs_subscriptions_and_subscribees(): void
    {
        $this->commandTester->execute(['screen_name' => self::SUBSCRIBER_SCREEN_NAME]);

        self::assertEquals(
            $this->commandTester->getStatusCode(),
            $this->command::SUCCESS,
            'The status code of a command should be successful',
        );
    }

    private function buildMutator(): FriendshipMutatorInterface
    {
        $mutatorProphecy = $this->prophesize(FriendshipMutatorInterface::class);
        $mutatorProphecy->unfollowMembers(
            Argument::type(MemberCollection::class),
            Argument::type(MemberInterface::class)
        )
        ->willReturn(new MemberCollection([]));

        return $mutatorProphecy->reveal();
    }

    private function buildNetworkRepository(): NetworkRepositoryInterface
    {
        $repository = $this->prophesize(NetworkRepositoryInterface::class);

        return $repository->reveal();
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