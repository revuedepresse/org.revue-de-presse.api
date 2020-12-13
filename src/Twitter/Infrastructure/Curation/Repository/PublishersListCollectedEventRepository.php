<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Curation\Entity\PublishersListCollectedEvent;
use App\Twitter\Domain\Curation\Repository\PublishersListCollectedEventRepositoryInterface;
use App\Twitter\Domain\Resource\MemberCollection;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Domain\Api\ApiAccessorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Throwable;
use const JSON_THROW_ON_ERROR;

class PublishersListCollectedEventRepository extends ServiceEntityRepository implements PublishersListCollectedEventRepositoryInterface
{
    use LoggerTrait;
    use ApiAccessorTrait;

    public function collectedPublishersList(
        ApiAccessorInterface $accessor,
        array $options
    ): MemberCollection {
        $listId   = $options[self::OPTION_publishers_list_ID];
        $listName = $options[self::OPTION_publishers_list_NAME];

        $event                = $this->startCollectOfPublishersList(
            (int) $listId,
            $listName
        );
        $membershipCollection = $accessor->getListMembers($listId);
        $this->finishCollectOfPublishersList(
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

    private function finishCollectOfPublishersList(
        PublishersListCollectedEvent $event,
        string $payload
    ): PublishersListCollectedEvent {
        $event->finishCollect($payload);

        return $this->save($event);
    }

    private function save(PublishersListCollectedEvent $event): PublishersListCollectedEvent
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

    private function startCollectOfPublishersList(
        int $listId,
        string $listName
    ): PublishersListCollectedEvent {
        $now = new \DateTimeImmutable();

        $event = new PublishersListCollectedEvent(
            $listId,
            $listName,
            $now,
            $now
        );

        return $this->save($event);
    }
}