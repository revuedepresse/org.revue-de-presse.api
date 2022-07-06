<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Curation\Entity\ListCollectedEvent;
use App\Twitter\Domain\Curation\Repository\PaginatedBatchCollectedEventRepositoryInterface;
use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Domain\Http\Resource\ResourceList;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Curation\Entity\FriendsListCollectedEvent;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Http\Resource\FriendsList;
use App\Twitter\Infrastructure\Http\Selector\FollowersListSelector;
use App\Twitter\Infrastructure\Http\Selector\FriendsListSelector;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use Closure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Throwable;
use function json_encode;

/**
 * @method FriendsListCollectedEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method FriendsListCollectedEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method FriendsListCollectedEvent[]    findAll()
 * @method FriendsListCollectedEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendsListCollectedEventRepository extends ServiceEntityRepository implements PaginatedBatchCollectedEventRepositoryInterface
{
    use LoggerTrait;

    public function aggregatedLists(
        CursorAwareHttpClientInterface $accessor,
        string                         $screenName
    ): ResourceList {
        $correlationId = CorrelationId::generate();

        $selector = new FriendsListSelector(
            $screenName,
            ListSelectorInterface::DEFAULT_CURSOR,
            $correlationId
        );

        $list = $this->collectedList(
            $accessor,
            $selector
        );
        $nextList = $list;

        while ($nextList->count() === 200 && $nextList->nextCursor() !== -1) {
            $selector = new FollowersListSelector(
                $screenName,
                $nextList->nextCursor(),
                $correlationId
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
        CursorAwareHttpClientInterface $accessor,
        ListSelectorInterface          $selector
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

    private function startCollectOfFriends(ListSelectorInterface $selector): ListCollectedEvent {
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
        ListSelectorInterface $selector,
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