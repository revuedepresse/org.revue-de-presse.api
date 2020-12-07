<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Collection\Entity\MemberFriendsListCollectedEvent;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\Twitter\Api\Accessor\FriendsAccessorInterface;
use App\Infrastructure\Twitter\Api\Resource\FriendsList;
use Closure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use InvalidArgumentException;
use Throwable;
use function json_encode;

class MemberFriendsListCollectedEventRepository extends ServiceEntityRepository implements MemberFriendsListCollectedEventRepositoryInterface
{
    use LoggerTrait;

    public function aggregatedMemberFriendsLists(
        FriendsAccessorInterface $accessor,
        string $screenName
    ): FriendsList {
            $friendsList = $this->collectedMemberFriendsList(
                $accessor,
                [self::OPTION_SCREEN_NAME => $screenName]
            );
            $nextFriendsList = $friendsList;

            while ($nextFriendsList->count() === 200 && $nextFriendsList->nextCursor() !== -1) {
                $nextFriendsList = $this->collectedMemberFriendsList(
                    $accessor,
                    [
                        self::OPTION_SCREEN_NAME => $screenName,
                        self::OPTION_CURSOR => $friendsList->nextCursor()
                    ]
                );
                $friendsList = FriendsList::fromResponse(array_merge(
                    ['users' => array_merge($friendsList->getFriendsList(), $nextFriendsList->getFriendsList())],
                    ['next_cursor_str' => $nextFriendsList->nextCursor()]
                ));
            }

            return $friendsList;
    }

    public function collectedMemberFriendsList(
        FriendsAccessorInterface $accessor,
        array $options
    ): FriendsList {
        if (!array_key_exists(self::OPTION_SCREEN_NAME, $options)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Missing "%s" option',
                    self::OPTION_SCREEN_NAME
                )
            );
        }

        $screenName = $options[self::OPTION_SCREEN_NAME];

        if (!array_key_exists(self::OPTION_CURSOR, $options)) {
            $friendsList = $accessor->getMemberFriendsListAtDefaultCursor(
                $screenName,
                $this->onFinishCollection(
                    $this->startCollectOfMemberFriends($screenName),
                    'getMemberFriendsListAtDefaultCursor',
                    $options
                )
            );
        } else {
            $cursor = $options[self::OPTION_CURSOR];

            $friendsList = $accessor->getMemberFriendsListAtCursor(
                $screenName,
                $cursor,
                $this->onFinishCollection(
                    $this->startCollectOfMemberFriends($screenName, $cursor),
                    'getMemberFriendsListAtCursor',
                    $options
                )
            );
        }

        return $friendsList;
    }

    private function finishCollectOfMemberFriendsList(
        MemberFriendsListCollectedEvent $event,
        string $payload
    ): MemberFriendsListCollectedEvent {
        $event->finishCollect($payload);

        return $this->save($event);
    }

    private function save(MemberFriendsListCollectedEvent $event): MemberFriendsListCollectedEvent
    {
        $entityManager = $this->getEntityManager();

        try {
            $entityManager->persist($event);
            $entityManager->flush();
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $event;
    }

    private function startCollectOfMemberFriends(
        string $screenName,
        string $cursor = '-1'
    ): MemberFriendsListCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new MemberFriendsListCollectedEvent(
            $screenName,
            $cursor,
            $now,
            $now
        );

        return $this->save($event);
    }

    /**
     * @param MemberFriendsListCollectedEvent $event
     * @param string $method
     * @param array $options
     * @return Closure
     */
    private function onFinishCollection(
        MemberFriendsListCollectedEvent $event,
        string $method,
        array $options
    ): Closure {
        return function (array $friendsList) use ($event, $method, $options) {
            $this->finishCollectOfMemberFriendsList(
                $event,
                json_encode(
                    [
                        'method' => $method,
                        'options' => $options,
                        'response' => $friendsList,
                    ],
                    JSON_THROW_ON_ERROR
                )
            );
        };
    }
}