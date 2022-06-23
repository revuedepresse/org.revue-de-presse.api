<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Curation\Repository\PublicationBatchCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Curation\Entity\PublicationBatchCollectedEvent;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Throwable;
use const JSON_THROW_ON_ERROR;

class PublicationBatchCollectedEventRepository extends ServiceEntityRepository implements PublicationBatchCollectedEventRepositoryInterface
{
    use LoggerTrait;
    use MemberRepositoryTrait;
    use ApiAccessorTrait;

    public function collectedPublicationBatch(
        CurationSelectorsInterface $selectors,
        array                      $options
    ) {
        $event    = $this->startCollectOfPublicationBatch($selectors);
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
        CurationSelectorsInterface $selectors
    ): PublicationBatchCollectedEvent {
        $member = $this->memberRepository->findOneBy(
            ['twitter_username' => $selectors->screenName()]
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