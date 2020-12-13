<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Subscription\Console;

use App\Twitter\Domain\Curation\Entity\FollowersListCollectedEvent;
use App\Twitter\Domain\Curation\Entity\FriendsListCollectedEvent;
use App\Twitter\Domain\Resource\MemberCollection;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Subscription\MemberSubscriptionRepositoryTrait;
use App\Twitter\Infrastructure\Twitter\Api\Mutator\FriendshipMutatorInterface;
use App\Membership\Domain\Entity\MemberInterface;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnfollowDiffSubscriptionsSubscribeesCommand extends AbstractCommand
{
    use LoggerTrait;
    use MemberRepositoryTrait;
    use MemberSubscriptionRepositoryTrait;

    private const ARGUMENT_SCREEN_NAME = 'screen_name';

    private ListCollectedEventRepositoryInterface $subscriptionsRepository;

    private ListCollectedEventRepositoryInterface $subscribeesRepository;

    private FriendshipMutatorInterface $mutator;

    private NetworkRepositoryInterface $networkRepository;

    public function setSubscriptionsRepository(ListCollectedEventRepositoryInterface $repository): void
    {
        $this->subscriptionsRepository = $repository;
    }

    public function setSubscribeesRepository(ListCollectedEventRepositoryInterface $repository): void
    {
        $this->subscribeesRepository = $repository;
    }

    public function setMutator(FriendshipMutatorInterface $mutator): void
    {
        $this->mutator = $mutator;
    }

    public function setNetworkRepository(NetworkRepositoryInterface $repository): void
    {
        $this->networkRepository = $repository;
    }

    protected function configure(): void
    {
        $this->setName('press-review:unfollow-diff-subscriptions-subscribees')
            ->setDescription('Unfollow diff between subscriptions and subscribees')
            ->addArgument(
                self::ARGUMENT_SCREEN_NAME,
                InputArgument::REQUIRED,
                'The screen name of a member'
            )
            ->setAliases(['pr:undiff-sbp-sbb'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $screenName = $input->getArgument(self::ARGUMENT_SCREEN_NAME);
        $subscriber = $this->memberRepository->findOneBy(
            ['twitter_username' => $screenName]
        );

        $subscriptions = $this->subscriptionsRepository->findBy(['screenName' => $screenName]);
        $subscribees = $this->subscribeesRepository->findBy(['screenName' => $screenName]);

        $subscriptionsIds = $this->pluckSubscriptionIds($subscriptions);
        $subscribeesIds = $this->pluckSubscribeesIds($subscribees);

        $cancelledSubscriptionsIds = $this->memberSubscriptionRepository->getCancelledMemberSubscriptions($subscriber);
        $subscriptionsDifference = array_diff($subscriptionsIds, $subscribeesIds, $cancelledSubscriptionsIds);

        $memberCollection = MemberCollection::fromArray(
            array_filter(
                array_map(
                    function (string $id) {
                        $member = $this->networkRepository->ensureMemberExists($id);

                        if (
                            !($member instanceof MemberInterface) ||
                            $member->hasBeenDeclaredAsNotFound() ||
                            $member->isSuspended()
                        ) {
                            return null;
                        }

                        return new MemberIdentity(
                            $member->getTwitterUsername(),
                            $member->getTwitterID()
                        );
                    },
                    $subscriptionsDifference
                )
            )
        );

        $this->mutator->unfollowMembers($memberCollection, $subscriber);

        return self::RETURN_STATUS_SUCCESS;
    }

    private function pluckSubscriptionIds(array $subscriptions): array
    {
        $nestedSubscriptionsIds = array_map(
            function (FriendsListCollectedEvent $event) {
                $payload = $event->payload();

                try {
                    $decodedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    $this->logger->error($exception->getMessage(), ['event' => $event->id()]);

                    return null;
                }

                if (
                    !array_key_exists('response', $decodedPayload) ||
                    !array_key_exists('users', $decodedPayload['response'])
                ) {
                    $this->logger->error('Invalid friends list', ['event' => $event->id()]);

                    return null;
                }

                return array_map(
                    fn(array $user) => $user['id_str'],
                    $decodedPayload['response']['users']
                );
            },
            $subscriptions
        );

        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($nestedSubscriptionsIds));
        $subscriptionsIds = iterator_to_array($iterator, false);
        sort($subscriptionsIds);

        return $subscriptionsIds;
    }

    private function pluckSubscribeesIds(array $subscribees): array
    {
        $nestedSubscribeesIds = array_map(
            function (FollowersListCollectedEvent $event) {
                $payload = $event->payload();

                try {
                    $decodedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    $this->logger->error($exception->getMessage(), ['event' => $event->id()]);

                    return null;
                }

                if (
                    !array_key_exists('response', $decodedPayload) ||
                    !array_key_exists('users', $decodedPayload['response'])
                ) {
                    $this->logger->error('Invalid followers list', ['event' => $event->id()]);

                    return null;
                }

                return array_map(
                    fn(array $user) => $user['id_str'],
                    $decodedPayload['response']['users']
                );
            },
            $subscribees
        );

        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($nestedSubscribeesIds));
        $subscribeesIds = iterator_to_array($iterator, false);
        sort($subscribeesIds);

        return $subscribeesIds;
    }
}