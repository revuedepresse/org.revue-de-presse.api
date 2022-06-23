<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Subscription\Console;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Tests\Membership\Builder\Repository\MemberRepositoryBuilder;
use App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository\FollowersListCollectedEventRepositoryBuilder;
use App\Tests\Twitter\Domain\Curation\Infrastructure\Builder\Repository\FriendsListCollectedEventRepositoryBuilder;
use App\Twitter\Infrastructure\Api\Mutator\FriendshipMutatorInterface;
use App\Twitter\Infrastructure\Api\Resource\MemberCollection;
use App\Twitter\Infrastructure\Subscription\Console\UnfollowDiffSubscriptionsSubscribeesCommand;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group diffing
 */
class UnfollowDiffSubscriptionsSubscribeesCommandTest extends KernelTestCase
{
    use ProphecyTrait;

    private const SUBSCRIBER_SCREEN_NAME = 'thierrymarianne';

    private UnfollowDiffSubscriptionsSubscribeesCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = static::bootKernel();

        /** @var UnfollowDiffSubscriptionsSubscribeesCommand $command */
        $command = static::getContainer()->get('test.'.UnfollowDiffSubscriptionsSubscribeesCommand::class);

        $application = new Application($kernel);

        $this->command = $application->find('app:unfollow-diff-subscriptions-subscribees');
        $this->command->setSubscriptionsRepository(FriendsListCollectedEventRepositoryBuilder::build());
        $this->command->setSubscribeesRepository(FollowersListCollectedEventRepositoryBuilder::build());
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
            'The return code of this command execution should be successful.',
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
                (new Member())->setTwitterScreenName(self::SUBSCRIBER_SCREEN_NAME)
            )
            ->build();
    }
}
