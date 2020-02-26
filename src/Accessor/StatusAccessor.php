<?php
declare(strict_types=1);

namespace App\Accessor;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Api\AccessToken\AccessToken;
use App\Domain\Status\StatusInterface;
use App\Membership\Entity\MemberInterface;
use App\Infrastructure\Repository\Membership\MemberRepository;
use App\Membership\Exception\InvalidMemberIdentifier;
use App\Status\Entity\NullStatus;
use App\Status\Repository\NotFoundStatusRepository;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Repository\ArchivedStatusRepository;
use App\Api\Repository\StatusRepository;
use App\Twitter\Api\Accessor;
use App\Twitter\Exception\NotFoundMemberException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ReflectionException;

/**
 * @package App\Accessor
 */
class StatusAccessor
{
    /**
     * @var bool
     */
    public $accessingInternalApi = true;

    /**
     * @var ArchivedStatusRepository
     */
    public $archivedStatusRepository;

    /**
     * @var EntityManager
     */
    public $entityManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var NotFoundStatusRepository
     */
    public $notFoundStatusRepository;

    /**
     * @var StatusRepository
     */
    public StatusRepository $statusRepository;

    /**
     * @var MemberRepository
     */
    public MemberRepository $userManager;

    /**
     * @var Accessor
     */
    public $accessor;

    /**
     * @param string $identifier
     * @throws OptimisticLockException
     */
    public function declareStatusNotFoundByIdentifier(string $identifier): void
    {
        $status = $this->statusRepository->findOneBy(['statusId' => $identifier]);
        if (is_null($status)) {
            $status = $this->archivedStatusRepository
                ->findOneBy(['statusId' => $identifier]);
        }

        $existingRecord = false;
        if ($status instanceof Status) {
            $existingRecord = !is_null($this->notFoundStatusRepository->findOneBy(['status' => $status]));
        }

        if ($status instanceof ArchivedStatus) {
            $existingRecord = !is_null($this->notFoundStatusRepository->findOneBy(['archivedStatus' => $status]));
        }

        if ($existingRecord) {
            return;
        }

        if (is_null($status)) {
            return;
        }

        $notFoundStatus = $this->notFoundStatusRepository->markStatusAsNotFound($status);

        $this->entityManager->persist($notFoundStatus);
        $this->entityManager->flush();
    }

    /**
     * @param string $identifier
     * @param bool   $skipExistingStatus
     * @param bool   $extractProperties
     *
     * @return StatusInterface|NullStatus|array|null
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws MappingException
     */
    public function refreshStatusByIdentifier(
        string $identifier,
        bool $skipExistingStatus = false,
        bool $extractProperties = true
    ) {
        $this->statusRepository->shouldExtractProperties = $extractProperties;

        $status = null;
        if (!$skipExistingStatus) {
            $status = $this->statusRepository->findStatusIdentifiedBy($identifier);
        }

        if ($status !== null && !empty($status)) {
            return $status;
        }

        $this->accessor->shouldRaiseExceptionOnApiLimit = true;
        $status = $this->accessor->showStatus($identifier);

        $this->entityManager->clear();

        try {
            $this->statusRepository->saveStatuses(
                [$status],
                new AccessToken($this->accessor->userToken)
            );
        } catch (NotFoundMemberException $notFoundMemberException) {
            return $this->findStatusIdentifiedBy($identifier);
        } catch (Exception $exception) {
            $this->logger->info($exception->getMessage());
        }

        return $this->findStatusIdentifiedBy($identifier);
    }

    public function ensureMemberHavingNameExists(string $memberName): MemberInterface
    {
        $member = $this->userManager->findOneBy(['twitter_username' => $memberName]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberHasBio($member, $memberName);

            return $member;
        }

        $fetchedMember = $this->accessor->showUser($memberName);
        $member = $this->userManager->findOneBy(['twitterID' => $fetchedMember->id]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberHasBio($member, $memberName);

            return $member;
        }

        return $this->userManager->saveMember(
            $this->userManager->make(
                $fetchedMember->id,
                $memberName,
                $protected = false,
                $suspended = false,
                $fetchedMember->description,
                $fetchedMember->friends_count,
                $fetchedMember->followers_count
            )
        );
    }

    /**
     * @param string $id
     *
     * @return MemberInterface|null
     * @throws BadAuthenticationDataException
     * @throws ApiRateLimitingException
     * @throws ReadOnlyApplicationException
     * @throws UnexpectedApiResponseException
     * @throws InconsistentTokenRepository
     * @throws InvalidMemberIdentifier
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    public function ensureMemberHavingIdExists(string $id)
    {
        $member = $this->userManager->findOneBy(['twitterID' => $id]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberHasBio($member, $member->getTwitterUsername());

            return $member;
        }

        $member = $this->accessor->showUser((string) $id);

        return $this->userManager->saveMember(
            $this->userManager->make(
                (string) $id,
                $member->screen_name,
                $protected = false,
                $suspended = false,
                $member->description,
                $member->friends_count,
                $member->followers_count
            )
        );
    }

    /**
     * @param string $identifier
     *
     * @return StatusInterface|NullStatus|array
     * @throws Exception
     */
    private function findStatusIdentifiedBy(string $identifier)
    {
        $status = $this->statusRepository->findStatusIdentifiedBy($identifier);

        if (is_null($status)) {
            return new NullStatus();
        }

        return $status;
    }

    /**
     * @param MemberInterface $member
     * @param string          $memberName
     *
     * @return MemberInterface
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    private function ensureMemberHasBio(
        MemberInterface $member,
        string $memberName
    ): MemberInterface {
        $memberBioIsAvailable = $member->isNotSuspended() &&
            $member->isNotProtected() &&
            $member->hasNotBeenDeclaredAsNotFound()
        ;

        $shouldTryToSaveDescription = is_null($member->getDescription()) && $memberBioIsAvailable;
        $shouldTryToUrl = is_null($member->getUrl()) && $memberBioIsAvailable;

        if ($shouldTryToSaveDescription || $shouldTryToUrl) {
            $fetchedMember = $this->accessor->showUser($memberName);

            if ($shouldTryToSaveDescription) {
                $member->description = $fetchedMember->description;
            }

            if ($shouldTryToUrl) {
                $member->url = $fetchedMember->url;
            }

            $this->userManager->saveMember($member);
        }

        return $member;
    }
}
