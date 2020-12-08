<?php
declare (strict_types=1);

namespace App\Domain\Subscription\Console;

use App\Console\AbstractCommand;
use App\Domain\Collection\Entity\MemberFriendsListCollectedEvent;
use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\Collection\Repository\MemberFriendsListCollectedEventRepositoryInterface;
use App\Infrastructure\Twitter\Api\Mutator\FriendshipMutatorInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnfollowInactiveMembersCommand extends AbstractCommand
{
    private const ARGUMENT_SCREEN_NAME = 'screen_name';

    /**
     * @var MemberFriendsListCollectedEventRepositoryInterface
     */
    private MemberFriendsListCollectedEventRepositoryInterface $repository;
    /**
     * @var FriendshipMutatorInterface
     */
    private FriendshipMutatorInterface $mutator;

    public function setRepository(MemberFriendsListCollectedEventRepositoryInterface $repository): void
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
        $memberFriendsListCollectedEvents = $this->repository->findBy(
            ['screenName' => $input->getArgument(self::ARGUMENT_SCREEN_NAME)]
        );

        if ($memberFriendsListCollectedEvents === null) {
            return self::SUCCESS;
        }

        array_walk(
            $memberFriendsListCollectedEvents,
            function (MemberFriendsListCollectedEvent $event) {
                $decodedPayload = json_decode($event->payload(), true);

                $memberIdentities = array_filter(
                    array_map(
                        static function ($userAttributes) {
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
                        },
                        $decodedPayload['response']['users']
                    )
                );

                $coll = MemberCollection::fromArray($memberIdentities);

                if ($coll instanceof MemberCollection) {
                    $this->mutator->unfollowMembers($coll);
                }
            }
        );

        return self::SUCCESS;
    }
}