<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Curation\Entity\OwnershipBatchCollectedEvent;
use App\Twitter\Domain\Curation\Repository\OwnershipBatchCollectedEventRepositoryInterface;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Domain\Resource\PublishersList;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Domain\Api\ApiAccessorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Throwable;
use const JSON_THROW_ON_ERROR;

class OwnershipBatchCollectedEventRepository extends ServiceEntityRepository implements OwnershipBatchCollectedEventRepositoryInterface
{
    use LoggerTrait;
    use ApiAccessorTrait;

    public function collectedOwnershipBatch(
        ApiAccessorInterface $accessor,
        array $options
    ): OwnershipCollection {
        $screenName = $options[self::OPTION_SCREEN_NAME];
        $nextPage   = $options[self::OPTION_NEXT_PAGE];

        $event               = $this->startCollectOfOwnershipBatch($screenName);
        $ownershipCollection = $accessor->getMemberOwnerships(
            $screenName,
            $nextPage
        );
        $this->finishCollectOfOwnershipBatch(
            $event,
            json_encode(
                [
                    'method'   => 'getMemberOwnerships',
                    'options'  => $options,
                    'response' => array_map(
                        fn (PublishersList $ownership) => $ownership->toArray(),
                        $ownershipCollection->toArray()
                    )
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $ownershipCollection;
    }

    private function finishCollectOfOwnershipBatch(
        OwnershipBatchCollectedEvent $event,
        string $payload
    ): OwnershipBatchCollectedEvent {
        $event->finishCollect($payload);

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

    private function startCollectOfOwnershipBatch(
        string $screenName
    ): OwnershipBatchCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new OwnershipBatchCollectedEvent(
            $screenName,
            $now,
            $now
        );

        return $this->save($event);
    }
}