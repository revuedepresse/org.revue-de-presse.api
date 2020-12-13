<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Accessor;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\ApiRateLimitingException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\NotFoundStatusException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\ReadOnlyApplicationException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\UnexpectedApiResponseException;
use App\Twitter\Infrastructure\Api\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Api\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Infrastructure\Api\Repository\ArchivedStatusRepository;
use App\Twitter\Domain\Curation\CollectionStrategyInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Domain\Publication\TaggedStatus;
use App\Twitter\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Collection\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Collection\PublicationBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Domain\Curation\Entity\NullStatus;
use App\Twitter\Domain\Curation\LikedStatusCollectionAwareInterface;
use App\Twitter\Domain\Publication\Repository\ExtremumAwareInterface;
use App\Twitter\Infrastructure\Publication\Repository\NotFoundStatusRepository;
use App\Twitter\Infrastructure\Exception\BadAuthenticationDataException;
use App\Twitter\Infrastructure\Exception\InconsistentTokenRepository;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use ReflectionException;
use function array_key_exists;
use function count;
use function sprintf;

/**
 * @package App\Accessor
 */
class StatusAccessor implements StatusAccessorInterface
{
    use ApiAccessorTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use PublicationPersistenceTrait;
    use LikedStatusRepositoryTrait;
    use LoggerTrait;
    use StatusRepositoryTrait;
    use MemberRepositoryTrait;
    use PublicationBatchCollectedEventRepositoryTrait;

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
     * @param string $statusId
     * @param bool   $skipExistingStatus
     * @param bool   $extractProperties
     *
     * @return StatusInterface|TaggedStatus|NullStatus|array|null
     * @throws Exception
     */
    public function refreshStatusByIdentifier(
        ?string $statusId,
        bool $skipExistingStatus = false,
        bool $extractProperties = true
    ) {
        $this->statusRepository->shouldExtractProperties = $extractProperties;

        $status = null;
        if (!$skipExistingStatus) {
            $status = $this->statusRepository
                ->findStatusIdentifiedBy($statusId);
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
                $protected = false,
                $suspended = false,
                $fetchedMember->description,
                $fetchedMember->friends_count,
                $fetchedMember->followers_count
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
                $protected = false,
                $suspended = false,
                $member->description,
                $member->friends_count,
                $member->followers_count
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
     * @param string $identifier
     *
     * @return StatusInterface|NullStatus|array
     * @throws Exception
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

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     * @param array                       $options
     * @param bool                        $discoverPublicationWithMaxId
     *
     * @return array
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function fetchPublications(
        CollectionStrategyInterface $collectionStrategy,
        $options,
        bool $discoverPublicationWithMaxId = true
    ): array {
        $options[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES] = $collectionStrategy->fetchLikes();
        $options = $this->removeCollectOptions($collectionStrategy, $options);
        $options = $this->updateExtremum(
            $collectionStrategy,
            $options,
            $discoverPublicationWithMaxId
        );

        // When there is an upper bound and a date before which publications
        // are to be collected, pick the date over the upper bound for collection
        if (
            array_key_exists('max_id', $options)
            && $collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected() // Looking into the past
        ) {
            unset($options['max_id']);
        }

        $statuses = $this->publicationBatchCollectedEventRepository
            ->collectedPublicationBatch(
                $collectionStrategy,
                $options
            );

        $discoverMoreRecentStatuses = false;
        if (
            count($statuses) > 0
            && $this->statusRepository->findOneBy(
                ['statusId' => $statuses[0]->id]
            ) instanceof StatusInterface
        ) {
            $discoverMoreRecentStatuses = true;
        }

        if (
            $discoverPublicationWithMaxId
            && (
                $discoverMoreRecentStatuses
                || (count($statuses) === 0))
        ) {
            if (array_key_exists('max_id', $options)) {
                unset($options['max_id']);
            }

            $statuses = $this->fetchPublications(
                $collectionStrategy,
                $options,
                $discoverPublicationWithMaxId = false
            );
        }

        return $statuses;
    }

    public function updateExtremum(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        bool $discoverPublicationsWithMaxId = true
    ): array {
        if ($collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()) {
            $discoverPublicationsWithMaxId = true;
        }

        $options = $this->removeMaxIdFromOptions($options, $discoverPublicationsWithMaxId);

        $findingDirection = $this->getExtremumUpdateMethod($discoverPublicationsWithMaxId);
        $extremum           = $this->findExtremum(
            $collectionStrategy,
            $options,
            $findingDirection
        );

        $logPrefix = $this->getLogPrefix($collectionStrategy);

        if (array_key_exists('statusId', $extremum) && (count($extremum) === 1)) {
            $option = $this->getExtremumOption($discoverPublicationsWithMaxId);
            $shift  = $this->getShiftFromExtremum($discoverPublicationsWithMaxId);

            if ($extremum['statusId'] === '-INF' && $option === 'max_id') {
                $extremum['statusId'] = 0;
            }

            $options[$option] = (int) $extremum['statusId'] + $shift;

            $this->logger->info(
                sprintf(
                    'Extremum (%s%s) retrieved for "%s": #%s',
                    $logPrefix,
                    $option,
                    $options[FetchPublicationInterface::SCREEN_NAME],
                    $options[$option]
                )
            );

            if ($options[$option] < 0 && $option === 'max_id') {
                unset($options[$option]);
            }

            return $options;
        }

        $this->logger->info(
            sprintf(
                '[No %s retrieved for "%s"] ',
                $logPrefix . 'extremum',
                $options[FetchPublicationInterface::SCREEN_NAME]
            )
        );

        return $options;
    }

    /**
     * @param $discoverPublicationsWithMaxId
     *
     * @return int
     */
    private function getShiftFromExtremum($discoverPublicationsWithMaxId): int
    {
        if ($discoverPublicationsWithMaxId) {
            return -1;
        }

        return 1;
    }

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     * @param array                       $options
     * @param string                      $findingDirection
     *
     * @return array|mixed
     */
    private function findExtremum(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        $findingDirection
    ): array {
        if ($collectionStrategy->fetchLikes()) {
            return $this->findLikeExtremum(
                $collectionStrategy,
                $options,
                $findingDirection
            );
        }

        if ($collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()) {
            return $this->statusRepository->findNextExtremum(
                $options[FetchPublicationInterface::SCREEN_NAME],
                $findingDirection,
                $collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()
            );
        }

        return $this->statusRepository->findLocalMaximum(
            $options[FetchPublicationInterface::SCREEN_NAME],
            $collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()
        );
    }

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     * @param                             $options
     * @param                             $findingDirection
     *
     * @return array|mixed
     */
    private function findLikeExtremum(
        CollectionStrategyInterface $collectionStrategy,
        $options,
        $findingDirection
    ): array {
        if (!$collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()) {
            return $this->likedStatusRepository->findLocalMaximum(
                $options[FetchPublicationInterface::SCREEN_NAME],
                $collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()
            );
        }

        return $this->likedStatusRepository->findNextExtremum(
            $options[FetchPublicationInterface::SCREEN_NAME],
            $findingDirection,
            $collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()
        );
    }

    /**
     * @param $discoverPublicationWithMaxId
     *
     * @return string
     */
    private function getExtremumUpdateMethod($discoverPublicationWithMaxId): string
    {
        if ($discoverPublicationWithMaxId) {
            // next maximum
            return ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER;
        }

        // next minimum
        return ExtremumAwareInterface::FINDING_IN_DESCENDING_ORDER;
    }

    /**
     * @param $discoverPublicationWithMaxId
     *
     * @return string
     */
    private function getExtremumOption($discoverPublicationWithMaxId): string
    {
        if ($discoverPublicationWithMaxId) {
            return 'max_id';
        }

        return 'since_id';
    }

    private function removeMaxIdFromOptions(
        array $options,
        bool $discoverPublicationsWithMaxId
    ): array
    {
        if (!$discoverPublicationsWithMaxId && array_key_exists('max_id', $options)) {
            unset($options['max_id']);
        }

        return $options;
    }

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     *
     * @return string
     */
    private function getLogPrefix(CollectionStrategyInterface $collectionStrategy): string
    {
        if (!$collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()) {
            return '';
        }

        return 'local ';
    }

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     * @param                             $options
     *
     * @return mixed
     */
    private function removeCollectOptions(
        CollectionStrategyInterface $collectionStrategy,
        $options
    ) {
        if ($collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()) {
            unset($options[FetchPublicationInterface::BEFORE]);
        }
        if (array_key_exists(FetchPublicationInterface::publishers_list_ID, $options)) {
            unset($options[FetchPublicationInterface::publishers_list_ID]);
        }

        return $options;
    }
}
