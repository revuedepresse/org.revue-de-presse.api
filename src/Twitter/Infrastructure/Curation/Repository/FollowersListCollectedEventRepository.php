<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Curation\Entity\FollowersListCollectedEvent;
use App\Twitter\Domain\Curation\Entity\ListCollectedEvent;
use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Twitter\Api\Resource\FollowersList;
use App\Twitter\Infrastructure\Twitter\Api\Resource\ResourceList;
use App\Twitter\Infrastructure\Twitter\Api\Selector\FollowersListSelector;
use App\Twitter\Infrastructure\Twitter\Api\Selector\ListSelector;
use Closure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Throwable;
use function json_encode;

/**
 * @method FollowersListCollectedEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method FollowersListCollectedEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method FollowersListCollectedEvent[]    findAll()
 * @method FollowersListCollectedEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FollowersListCollectedEventRepository extends ServiceEntityRepository implements ListCollectedEventRepositoryInterface
{
    use LoggerTrait;

    public function aggregatedLists(
        ListAccessorInterface $accessor,
        string $screenName
    ): ResourceList {
        $correlationId = UuidV4::uuid4();

        $selector = new FollowersListSelector(
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

            $list = FollowersList::fromResponse(array_merge(
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
                $this->startCollectOfFollowers($selector),
                $selector,
                'getListAtCursor',
            )
        );
    }

    private function finishCollectOfFollowersList(
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

    private function startCollectOfFollowers(ListSelector $selector): ListCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new FollowersListCollectedEvent(
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
            $this->finishCollectOfFollowersList(
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