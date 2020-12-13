<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Curation\CollectionStrategyInterface;
use App\Twitter\Domain\Curation\Entity\PublicationBatchCollectedEvent;
use App\Twitter\Domain\Curation\Repository\PublicationBatchCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Membership\Domain\Entity\MemberInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Throwable;
use const JSON_THROW_ON_ERROR;

class PublicationBatchCollectedEventRepository extends ServiceEntityRepository implements PublicationBatchCollectedEventRepositoryInterface
{
    use LoggerTrait;
    use MemberRepositoryTrait;
    use ApiAccessorTrait;

    public function collectedPublicationBatch(
        CollectionStrategyInterface $collectionStrategy,
        array $options
    ) {
        $event    = $this->startCollectOfPublicationBatch($collectionStrategy);
        $statuses = $this->apiAccessor->fetchStatuses($options);
        $this->finishCollectOfPublicationBatch(
            $event,
            json_encode(
                [
                    'method'   => 'fetchStatuses',
                    'options'  => $options,
                    'response' => $statuses
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $statuses;
    }

    private function finishCollectOfPublicationBatch(
        PublicationBatchCollectedEvent $event,
        string $payload
    ): PublicationBatchCollectedEvent {
        $event->finishCollect($payload);

        return $this->save($event);
    }

    private function startCollectOfPublicationBatch(
        CollectionStrategyInterface $collectionStrategy
    ): PublicationBatchCollectedEvent {
        $member = $this->memberRepository->findOneBy(
            ['twitter_username' => $collectionStrategy->screenName()]
        );

        return $this->startCollectOfPublicationBatchForMember(
            $member
        );
    }

    private function save(PublicationBatchCollectedEvent $event): PublicationBatchCollectedEvent
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

    private function startCollectOfPublicationBatchForMember(
        MemberInterface $member
    ): PublicationBatchCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new PublicationBatchCollectedEvent(
            $member,
            $now,
            $now
        );

        return $this->save($event);
    }
}