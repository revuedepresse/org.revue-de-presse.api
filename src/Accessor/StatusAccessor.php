<?php
declare(strict_types=1);

namespace App\Accessor;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Api\AccessToken\AccessToken;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Repository\ArchivedStatusRepository;
use App\Domain\Collection\CollectionStrategyInterface;
use App\Domain\Status\StatusInterface;
use App\Domain\Status\TaggedStatus;
use App\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Infrastructure\Twitter\Api\Accessor\StatusAccessorInterface;
use App\Membership\Entity\MemberInterface;
use App\Status\Entity\NullStatus;
use App\Status\LikedStatusCollectionAwareInterface;
use App\Status\Repository\ExtremumAwareInterface;
use App\Status\Repository\NotFoundStatusRepository;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
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
    use PublicationPersistenceTrait;
    use LikedStatusRepositoryTrait;
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

        $fetchedMember = $this->apiAccessor->showUser($memberName);
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

    /**
     * @param string $id
     *
     * @return MemberInterface|null
     */
    public function ensureMemberHavingIdExists(string $id): ?MemberInterface
    {
        $member = $this->memberRepository->findOneBy(['twitterID' => $id]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberHasBio($member, $member->getTwitterUsername());

            return $member;
        }

        $member = $this->apiAccessor->showUser((string) $id);

        return $this->memberRepository->saveMember(
            $this->memberRepository->make(
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
        $shouldTryToUrl = $member->getUrl() === null && $memberBioIsAvailable;

        if ($shouldTryToSaveDescription || $shouldTryToUrl) {
            $fetchedMember = $this->apiAccessor->showUser($memberName);

            if ($shouldTryToSaveDescription) {
                $member->description = $fetchedMember->description;
            }

            if ($shouldTryToUrl) {
                $member->url = $fetchedMember->url;
            }

            $this->memberRepository->saveMember($member);
        }

        return $member;
    }

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     * @param array                       $options
     * @param bool                        $discoverPastTweets
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
    public function fetchLatestStatuses(
        CollectionStrategyInterface $collectionStrategy,
        $options,
        bool $discoverPastTweets = true
    ): array {
        $options[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES] = $collectionStrategy->fetchLikes();
        $options = $this->removeCollectOptions($collectionStrategy, $options);
        $options = $this->updateExtremum(
            $collectionStrategy,
            $options,
            $discoverPastTweets
        );

        if (
            array_key_exists('max_id', $options)
            && $collectionStrategy->dateBeforeWhichStatusAreToBeCollected() // Looking into the past
        ) {
            unset($options['max_id']);
        }

        $statuses = $this->apiAccessor->fetchStatuses($options);

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
            $discoverPastTweets
            && (
                $discoverMoreRecentStatuses
                || (count($statuses) === 0))
        ) {
            if (array_key_exists('max_id', $options)) {
                unset($options['max_id']);
            }

            $statuses = $this->fetchLatestStatuses(
                $collectionStrategy,
                $options,
                $discoverPastTweets = false
            );
        }

        return $statuses;
    }

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     * @param array                       $options
     * @param bool                        $discoverPastTweets
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function updateExtremum(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        bool $discoverPastTweets = true
    ): array {
        if ($collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            $discoverPastTweets = true;
        }

        $options = $this->removeMaxIdFromOptions($options, $discoverPastTweets);

        $findingDirection = $this->getExtremumUpdateMethod($discoverPastTweets);
        $status           = $this->findExtremum(
            $collectionStrategy,
            $options,
            $findingDirection
        );

        $logPrefix = $this->getLogPrefix($collectionStrategy);

        if (array_key_exists('statusId', $status) && (count($status) === 1)) {
            $option = $this->getExtremumOption($discoverPastTweets);
            $shift  = $this->getShiftFromExtremum($discoverPastTweets);

            if ($status['statusId'] === '-INF' && $option === 'max_id') {
                $status['statusId'] = 0;
            }

            $options[$option] = (int) $status['statusId'] + $shift;

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
     * @param $discoverPastTweets
     *
     * @return int
     */
    private function getShiftFromExtremum($discoverPastTweets): int
    {
        if ($discoverPastTweets) {
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

        if (!$collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            return $this->statusRepository->findLocalMaximum(
                $options[FetchPublicationInterface::SCREEN_NAME],
                $collectionStrategy->dateBeforeWhichStatusAreToBeCollected()
            );
        }

        return $this->statusRepository->findNextExtremum(
            $options[FetchPublicationInterface::SCREEN_NAME],
            $findingDirection,
            $collectionStrategy->dateBeforeWhichStatusAreToBeCollected()
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
        if (!$collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            return $this->likedStatusRepository->findLocalMaximum(
                $options[FetchPublicationInterface::SCREEN_NAME],
                $collectionStrategy->dateBeforeWhichStatusAreToBeCollected()
            );
        }

        return $this->likedStatusRepository->findNextExtremum(
            $options[FetchPublicationInterface::SCREEN_NAME],
            $findingDirection,
            $collectionStrategy->dateBeforeWhichStatusAreToBeCollected()
        );
    }

    /**
     * @param $discoverPastTweets
     *
     * @return string
     */
    private function getExtremumUpdateMethod($discoverPastTweets): string
    {
        if ($discoverPastTweets) {
            // next maximum
            return ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER;
        }

        // next minimum
        return ExtremumAwareInterface::FINDING_IN_DESCENDING_ORDER;
    }

    /**
     * @param $discoverPastTweets
     *
     * @return string
     */
    private function getExtremumOption($discoverPastTweets): string
    {
        if ($discoverPastTweets) {
            return 'max_id';
        }

        return 'since_id';
    }

    /**
     * @param $options
     * @param $discoverPastTweets
     *
     * @return array
     */
    private function removeMaxIdFromOptions($options, $discoverPastTweets): array
    {
        if (!$discoverPastTweets && array_key_exists('max_id', $options)) {
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
        if (!$collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
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
        if ($collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            unset($options[FetchPublicationInterface::BEFORE]);
        }
        if (array_key_exists(FetchPublicationInterface::AGGREGATE_ID, $options)) {
            unset($options[FetchPublicationInterface::AGGREGATE_ID]);
        }

        return $options;
    }
}
