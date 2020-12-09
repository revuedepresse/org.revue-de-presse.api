<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Collection\Entity\FriendsListCollectedEvent;
use App\Domain\Collection\Entity\ListCollectedEvent;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Infrastructure\Twitter\Api\Resource\FriendsList;
use App\Infrastructure\Twitter\Api\Resource\ResourceList;
use Closure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use InvalidArgumentException;
use Throwable;
use function json_encode;

/**
 * @method FriendsListCollectedEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method FriendsListCollectedEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method FriendsListCollectedEvent[]    findAll()
 * @method FriendsListCollectedEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendsListCollectedEventRepository extends ServiceEntityRepository implements ListCollectedEventRepositoryInterface
{
    use LoggerTrait;

    public function aggregatedLists(
        ListAccessorInterface $accessor,
        string $screenName
    ): ResourceList {
            $list = $this->collectedList(
                $accessor,
                [self::OPTION_SCREEN_NAME => $screenName]
            );
            $nextList = $list;

            while ($nextList->count() === 200 && $nextList->nextCursor() !== -1) {
                $nextList = $this->collectedList(
                    $accessor,
                    [
                        self::OPTION_SCREEN_NAME => $screenName,
                        self::OPTION_CURSOR => $list->nextCursor()
                    ]
                );
                $list = FriendsList::fromResponse(array_merge(
                    ['users' => array_merge($list->getList(), $nextList->getList())],
                    ['next_cursor_str' => $nextList->nextCursor()]
                ));
            }

            return $list;
    }

    public function collectedList(
        ListAccessorInterface $accessor,
        array $options
    ): ResourceList {
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
            $list = $accessor->getListAtDefaultCursor(
                $screenName,
                $this->onFinishCollection(
                    $this->startCollectOfFriends($screenName),
                    'getListAtDefaultCursor',
                    $options
                )
            );
        } else {
            $cursor = $options[self::OPTION_CURSOR];

            $list = $accessor->getListAtCursor(
                $screenName,
                $cursor,
                $this->onFinishCollection(
                    $this->startCollectOfFriends($screenName, $cursor),
                    'getListAtCursor',
                    $options
                )
            );
        }

        return $list;
    }

    private function finishCollectOfMemberFriendsList(
        ListCollectedEvent $event,
        string $payload
    ): ListCollectedEvent {
        $event->finishCollect($payload);

        return $this->save($event);
    }

    private function save(ListCollectedEvent $event): ListCollectedEvent
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

    private function startCollectOfFriends(
        string $screenName,
        string $cursor = '-1'
    ): ListCollectedEvent {
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
     * @param ListCollectedEvent $event
     * @param string $method
     * @param array $options
     * @return Closure
     */
    private function onFinishCollection(
        ListCollectedEvent $event,
        string $method,
        array $options
    ): Closure {
        return function (array $list) use ($event, $method, $options) {
            $this->finishCollectOfMemberFriendsList(
                $event,
                json_encode(
                    [
                        'method' => $method,
                        'options' => $options,
                        'response' => $list,
                    ],
                    JSON_THROW_ON_ERROR
                )
            );
        };
    }
}