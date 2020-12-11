<?php
declare (strict_types=1);

namespace App\Domain\Subscription\Console;

use App\Console\AbstractCommand;
use App\Domain\Collection\Entity\FriendsListCollectedEvent;
use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\Collection\Repository\ListCollectedEventRepositoryInterface;
use App\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Infrastructure\Repository\Membership\MemberRepositoryInterface;
use App\Infrastructure\Twitter\Api\Mutator\FriendshipMutatorInterface;
use App\Membership\Entity\MemberInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnfollowInactiveMembersCommand extends AbstractCommand
{
    use MemberRepositoryTrait;

    private const ARGUMENT_SCREEN_NAME = 'screen_name';

    private ListCollectedEventRepositoryInterface $repository;

    private FriendshipMutatorInterface $mutator;

    private MemberInterface $subscriber;

    public function setListCollectedEventRepository(ListCollectedEventRepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }

    public function setMutator(FriendshipMutatorInterface $mutator): void
    {
        $this->mutator = $mutator;
    }

    public function __construct($name = 'press-review:unfollow-inactive-members')
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription(
                'Unfollow inactive members followed by member whose screen name has been passed as argument.'
            )
            ->addArgument(
                self::ARGUMENT_SCREEN_NAME,
                InputArgument::REQUIRED,
                'screen name of member who would like to unfollow inactive members'
            )
            ->setAliases(['pr:ufw']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $screenName = $input->getArgument(self::ARGUMENT_SCREEN_NAME);

        $memberFriendsListCollectedEvents = $this->repository->findBy(
            ['screenName' => $screenName]
        );

        $this->subscriber = $this->memberRepository->findOneBy(
            ['twitter_username' => $screenName]
        );

        if ($memberFriendsListCollectedEvents === null) {
            return self::SUCCESS;
        }

        array_walk(
            $memberFriendsListCollectedEvents,
            [$this, 'processMemberFriendList']
        );

        return self::SUCCESS;
    }

    private function processMemberFriendList(FriendsListCollectedEvent $event): void
    {
        $decodedPayload = json_decode($event->payload(), true);

        $memberIdentities = array_filter(
            array_map(
                [$this, 'convertUserAttributesToMemberIdentity'],
                $decodedPayload['response']['users']
            )
        );

        $coll = MemberCollection::fromArray($memberIdentities);

        if ($coll instanceof MemberCollection) {
            $this->mutator->unfollowMembers($coll, $this->subscriber);
        }
    }

    private function convertUserAttributesToMemberIdentity(array $userAttributes): ?MemberIdentity {
        if (!array_key_exists('status', $userAttributes)) {
            return new MemberIdentity(
                $userAttributes['screen_name'],
                $userAttributes['id_str'],
            );
        }

        $thisYear = (new \DateTime('now'))
            ->format('Y');
        $lastPublicationYear = (int) (new \DateTime($userAttributes['status']['created_at']))
            ->format('Y');

        if ($lastPublicationYear < (int) $thisYear) {
            return new MemberIdentity(
                $userAttributes['screen_name'],
                $userAttributes['id_str'],
            );
        }

        return null;
    }
}