<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Curation\Entity\FriendsListCollectedEvent;
use App\Twitter\Domain\Curation\Entity\ListCollectedEvent;
use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Twitter\Api\Resource\FriendsList;
use App\Twitter\Infrastructure\Twitter\Api\Resource\ResourceList;
use App\Twitter\Infrastructure\Twitter\Api\Selector\FollowersListSelector;
use App\Twitter\Infrastructure\Twitter\Api\Selector\FriendsListSelector;
use App\Twitter\Infrastructure\Twitter\Api\Selector\ListSelector;
use Closure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Ramsey\Uuid\Rfc4122\UuidV4;
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
        $correlationId = UuidV4::uuid4();

        $selector = new FriendsListSelector(
            $correlationId,
            $screenName,
            ListSelector::DEFAULT_CURSOR
        );

        $list = $this->collectedList(
            $accessor,
            $selector
        );
        $nextList = $list;

        while ($nextList->count() === 200 && $nextList->nextCursor() !== -1) {
            $selector = new FollowersListSelector(
                $correlationId,
                $screenName,
                $nextList->nextCursor()
            );

            $nextList = $this->collectedList(
                $accessor,
                $selector
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
        ListSelector $selector
    ): ResourceList {
        return $accessor->getListAtCursor(
            $selector,
            $this->onFinishCollection(
                $this->startCollectOfFriends($selector),
                $selector,
                'getListAtCursor'
            )
        );
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

    private function startCollectOfFriends(ListSelector $selector): ListCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new FriendsListCollectedEvent(
            $selector,
            $now,
            $now
        );

        return $this->save($event);
    }

    private function onFinishCollection(
        ListCollectedEvent $event,
        ListSelector $selector,
        string $method
    ): Closure {
        return function (array $list) use ($event, $method, $selector) {
            $this->finishCollectOfMemberFriendsList(
                $event,
                json_encode(
                    [
                        'method' => $method,
                        'options' => [
                            'screen_name' => $selector->screenName(),
                            'cursor' => $selector->cursor(),
                            'correlation_id' => $selector->correlationId(),
                        ],
                        'response' => $list,
                    ],
                    JSON_THROW_ON_ERROR
                )
            );
        };
    }
}