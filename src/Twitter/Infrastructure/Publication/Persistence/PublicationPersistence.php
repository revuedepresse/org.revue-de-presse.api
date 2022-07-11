<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Persistence;

use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Domain\Publication\Repository\PublicationRepositoryInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Adapter\StatusToArray;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusPersistenceTrait;
use App\Twitter\Infrastructure\Operation\Collection\Collection;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use Doctrine\ORM\EntityManagerInterface;
use function count;

class PublicationPersistence implements PublicationPersistenceInterface
{
    use MemberRepositoryTrait;
    use PublicationRepositoryTrait;
    use StatusPersistenceTrait;

    private EntityManagerInterface $entityManager;

    public function __construct(
        StatusPersistenceInterface $statusPersistence,
        PublicationRepositoryInterface $publicationRepository,
        MemberRepositoryInterface $memberRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->statusPersistence = $statusPersistence;
        $this->publicationRepository = $publicationRepository;
        $this->entityManager = $entityManager;
        $this->memberRepository = $memberRepository;
    }

    public function persistTweets(
        array $statuses,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): CollectionInterface {
        $statusPersistence = $this->statusPersistence;
        $result           = $statusPersistence->persistAllStatuses(
            $statuses,
            $identifier,
            $twitterList
        );
        $normalizedStatus = $result[$statusPersistence::PROPERTY_NORMALIZED_STATUS];
        $screenName       = $result[$statusPersistence::PROPERTY_SCREEN_NAME];

        // Mark status as published
        $statusCollection = new Collection(
            $result[$statusPersistence::PROPERTY_STATUS]->toArray()
        );
        $statusCollection->map(fn(TweetInterface $status) => $status->markAsPublished());

        // Make publications
        $statusCollection = StatusToArray::fromStatusCollection($statusCollection);
        $this->publicationRepository->persistPublications($statusCollection);

        // Commit transaction
        $this->entityManager->flush();

        if (count($normalizedStatus) > 0) {
            $this->memberRepository->incrementTotalStatusesOfMemberWithName(
                count($normalizedStatus),
                $screenName
            );
        }

        return $normalizedStatus;
    }
}
