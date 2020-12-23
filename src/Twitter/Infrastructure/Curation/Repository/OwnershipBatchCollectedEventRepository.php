<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Api\MemberOwnershipsAccessorInterface;
use App\Twitter\Domain\Curation\Entity\OwnershipBatchCollectedEvent;
use App\Twitter\Domain\Curation\Repository\OwnershipBatchCollectedEventRepositoryInterface;
use App\Twitter\Domain\Resource\OwnershipCollectionInterface;
use App\Twitter\Domain\Resource\PublishersList;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Throwable;
use const JSON_THROW_ON_ERROR;

class OwnershipBatchCollectedEventRepository extends ServiceEntityRepository implements OwnershipBatchCollectedEventRepositoryInterface
{
    use LoggerTrait;

    public function collectedOwnershipBatch(
        MemberOwnershipsAccessorInterface $accessor,
        ListSelectorInterface $selector
    ): OwnershipCollectionInterface {
        $event               = $this->startCollectOfOwnershipBatch($selector);
        $ownershipCollection = $accessor->getMemberOwnerships($selector);
        $this->finishCollectOfOwnershipBatch(
            $event,
            json_encode(
                [
                    'method'   => __METHOD__,
                    'options'  => [
                        self::OPTION_SCREEN_NAME => $selector->screenName(),
                        self::OPTION_NEXT_PAGE => $selector->cursor(),
                    ],
                    'response' => array_map(
                        fn (PublishersList $ownership) => $ownership->toArray(),
                        $ownershipCollection->toArray()
                    )
                ],
                JSON_THROW_ON_ERROR
            ),
            $ownershipCollection->isEmpty()
        );

        return $ownershipCollection;
    }

    private function finishCollectOfOwnershipBatch(
        OwnershipBatchCollectedEvent $event,
        string $payload,
        bool $nothingToSave
    ): OwnershipBatchCollectedEvent {
        $event->finishCollect($payload);

        if ($nothingToSave) {
            return $this->remove($event);
        }

        return $this->save($event);
    }

    private function save(OwnershipBatchCollectedEvent $event): OwnershipBatchCollectedEvent
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

    private function remove(OwnershipBatchCollectedEvent $event): OwnershipBatchCollectedEvent
    {
        $entityManager = $this->getEntityManager();

        try {
            $entityManager->remove($event);
            $entityManager->flush();
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $event;
    }

    private function startCollectOfOwnershipBatch(
        ListSelectorInterface $selector
    ): OwnershipBatchCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new OwnershipBatchCollectedEvent(
            $selector,
            $now,
            $now
        );

        return $this->save($event);
    }
}