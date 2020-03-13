<?php
declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Collection\Entity\PublicationListCollectedEvent;
use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Api\ApiAccessorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Throwable;
use const JSON_THROW_ON_ERROR;

class PublicationListCollectedEventRepository extends ServiceEntityRepository implements PublicationListCollectedEventRepositoryInterface
{
    use LoggerTrait;
    use ApiAccessorTrait;

    public function collectedPublicationList(
        ApiAccessorInterface $accessor,
        array $options
    ): MemberCollection {
        $listId   = $options[self::OPTION_PUBLICATION_LIST_ID];
        $listName = $options[self::OPTION_PUBLICATION_LIST_NAME];

        $event                = $this->startCollectOfPublicationList(
            (int) $listId,
            $listName
        );
        $membershipCollection = $accessor->getListMembers($listId);
        $this->finishCollectOfPublicationList(
            $event,
            json_encode(
                [
                    'method'   => 'getListMembers',
                    'options'  => $options,
                    'response' => array_map(
                        fn(MemberIdentity $memberIdentity) => $memberIdentity->toArray(),
                        $membershipCollection->toArray()
                    )
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $membershipCollection;
    }

    private function finishCollectOfPublicationList(
        PublicationListCollectedEvent $event,
        string $payload
    ): PublicationListCollectedEvent {
        $event->finishCollect($payload);

        return $this->save($event);
    }

    private function save(PublicationListCollectedEvent $event): PublicationListCollectedEvent
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

    private function startCollectOfPublicationList(
        int $listId,
        string $listName
    ): PublicationListCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new PublicationListCollectedEvent(
            $listId,
            $listName,
            $now,
            $now
        );

        return $this->save($event);
    }
}