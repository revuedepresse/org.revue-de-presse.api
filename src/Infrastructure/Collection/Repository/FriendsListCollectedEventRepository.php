<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Collection\Entity\FriendsListCollectedEvent;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Infrastructure\Twitter\Api\Resource\FriendsList;
use Closure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use InvalidArgumentException;
use Throwable;
use function json_encode;

class FriendsListCollectedEventRepository extends ServiceEntityRepository implements ListCollectedEventRepositoryInterface
{
    use LoggerTrait;

    public function aggregatedLists(
        ListAccessorInterface $accessor,
        string $screenName
    ): FriendsList {
            $friendsList = $this->collectedList(
                $accessor,
                [self::OPTION_SCREEN_NAME => $screenName]
            );
            $nextFriendsList = $friendsList;

            while ($nextFriendsList->count() === 200 && $nextFriendsList->nextCursor() !== -1) {
                $nextFriendsList = $this->collectedList(
                    $accessor,
                    [
                        self::OPTION_SCREEN_NAME => $screenName,
                        self::OPTION_CURSOR => $friendsList->nextCursor()
                    ]
                );
                $friendsList = FriendsList::fromResponse(array_merge(
                    ['users' => array_merge($friendsList->getList(), $nextFriendsList->getList())],
                    ['next_cursor_str' => $nextFriendsList->nextCursor()]
                ));
            }

            return $friendsList;
    }

    public function collectedList(
        ListAccessorInterface $accessor,
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
            $friendsList = $accessor->getListAtDefaultCursor(
                $screenName,
                $this->onFinishCollection(
                    $this->startCollectOfMemberFriends($screenName),
                    'getMemberFriendsListAtDefaultCursor',
                    $options
                )
            );
        } else {
            $cursor = $options[self::OPTION_CURSOR];

            $friendsList = $accessor->getListAtCursor(
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
        FriendsListCollectedEvent $event,
        string $payload
    ): FriendsListCollectedEvent {
        $event->finishCollect($payload);

        return $this->save($event);
    }

    private function save(FriendsListCollectedEvent $event): FriendsListCollectedEvent
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
    ): FriendsListCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new FriendsListCollectedEvent(
            $screenName,
            $cursor,
            $now,
            $now
        );

        return $this->save($event);
    }

    /**
     * @param FriendsListCollectedEvent $event
     * @param string $method
     * @param array $options
     * @return Closure
     */
    private function onFinishCollection(
        FriendsListCollectedEvent $event,
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