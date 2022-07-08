<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Accessor;

use App\Twitter\Infrastructure\Curation\DependencyInjection\MemberProfileCollectedEventRepositoryTrait;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Domain\Curation\Entity\NullStatus;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Http\Entity\Status;
use App\Membership\Infrastructure\Repository\ArchivedStatusRepository;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Publication\Repository\NotFoundStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class StatusAccessor implements StatusAccessorInterface
{
    use ApiAccessorTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use PublicationPersistenceTrait;
    use LoggerTrait;
    use StatusRepositoryTrait;
    use MemberRepositoryTrait;

    public ArchivedStatusRepository $archivedStatusRepository;

    public EntityManagerInterface $entityManager;

    public NotFoundStatusRepository $notFoundStatusRepository;

    /**
     * @param string $identifier
     */
    public function declareStatusNotFoundByIdentifier(string $identifier): void
    {
        $status = $this->statusRepository->findOneBy(['statusId' => $identifier]);
        if ($status === null) {
            $status = $this->archivedStatusRepository
                ->findOneBy(['statusId' => $identifier]);
        }

        $existingRecord = false;
        if ($status instanceof Status) {
            $existingRecord = $this->notFoundStatusRepository->findOneBy(['status' => $status]) !== null;
        }

        if ($status instanceof ArchivedStatus) {
            $existingRecord = $this->notFoundStatusRepository->findOneBy(['archivedStatus' => $status]) !== null;
        }

        if ($existingRecord) {
            return;
        }

        if ($status === null) {
            return;
        }

        $notFoundStatus = $this->notFoundStatusRepository->markStatusAsNotFound($status);

        $this->entityManager->persist($notFoundStatus);
        $this->entityManager->flush();
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     */
    public function refreshStatusByIdentifier(
        ?string $statusId,
        bool $skipExistingStatus = false,
        bool $extractProperties = true
    ): ArchivedStatus|array|StatusInterface|NullStatus
    {
        $this->statusRepository->shouldExtractProperties = $extractProperties;

        $status = null;
        if (!$skipExistingStatus) {
            $status = $this->statusRepository->findStatusIdentifiedBy($statusId);
        }

        if ($status !== null && !empty($status)) {
            return $status;
        }

        $this->apiAccessor->shouldRaiseExceptionOnApiLimit = true;
        $status = $this->apiAccessor->showStatus($statusId);

        $this->entityManager->clear();

        try {
            $this->publicationPersistence->persistStatusPublications(
                [$status],
                new AccessToken($this->apiAccessor->userToken)
            );
        } catch (NotFoundMemberException $notFoundMemberException) {
            $this->logger->info($notFoundMemberException->getMessage());

            return $this->findStatusIdentifiedBy($statusId);
        } catch (Exception $exception) {
            $this->logger->info($exception->getMessage());
        }

        return $this->findStatusIdentifiedBy($statusId);
    }

    public function ensureMemberHavingNameExists(string $memberName): MemberInterface
    {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $memberName]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberHasBio($member, $memberName);

            return $member;
        }

        $fetchedMember = $this->collectMemberProfile($memberName);
        $member = $this->memberRepository->findOneBy(['twitterID' => $fetchedMember->id]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberHasBio($member, $memberName);

            return $member;
        }

        return $this->memberRepository->saveMember(
            $this->memberRepository->make(
                (string) $fetchedMember->id,
                $memberName,
                description: $fetchedMember->description,
                totalSubscriptions: $fetchedMember->friends_count,
                totalSubscribees: $fetchedMember->followers_count
            )
        );
    }

    public function ensureMemberHavingIdExists(string $id): ?MemberInterface
    {
        $member = $this->memberRepository->findOneBy(['twitterID' => $id]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberHasBio($member, $member->getTwitterUsername());

            return $member;
        }

        $member = $this->collectMemberProfile($id);

        return $this->memberRepository->saveMember(
            $this->memberRepository->make(
                $id,
                $member->screen_name,
                description: $member->description,
                totalSubscriptions: $member->friends_count,
                totalSubscribees: $member->followers_count
            )
        );
    }

    private function collectMemberProfile(string $screenName): \stdClass
    {
        $eventRepository = $this->memberProfileCollectedEventRepository;

        return $eventRepository->collectedMemberProfile(
            $this->apiAccessor,
            [$eventRepository::OPTION_SCREEN_NAME => $screenName]
        );
    }

    /**
     * @throws \JsonException
     */
    private function findStatusIdentifiedBy(string $identifier)
    {
        $status = $this->statusRepository->findStatusIdentifiedBy(
            $identifier
        );

        if ($status === null) {
            return new NullStatus();
        }

        return $status;
    }

    /**
     * @param MemberInterface $member
     * @param string          $memberName
     *
     * @return MemberInterface
     */
    private function ensureMemberHasBio(
        MemberInterface $member,
        string $memberName
    ): MemberInterface {
        $memberBioIsAvailable = $member->isNotSuspended() &&
            $member->isNotProtected() &&
            $member->hasNotBeenDeclaredAsNotFound()
        ;

        $shouldTryToSaveDescription = $member->getDescription() === null && $memberBioIsAvailable;
        $shouldTryToSaveUrl = $member->getUrl() === null && $memberBioIsAvailable;

        if ($shouldTryToSaveDescription || $shouldTryToSaveUrl) {
            $fetchedMember = $this->collectMemberProfile($memberName);

            if ($shouldTryToSaveDescription) {
                $member->description = $fetchedMember->description ?? '';
            }

            if ($shouldTryToSaveUrl) {
                $member->url = $fetchedMember->url ?? '';
            }

            $this->memberRepository->saveMember($member);
        }

        return $member;
    }
}
