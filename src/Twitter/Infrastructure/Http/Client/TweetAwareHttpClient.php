<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Twitter\Domain\Http\Client\TweetAwareHttpClientInterface;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Publication\Repository\ExtremumAwareInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchTweetInterface;
use App\Twitter\Infrastructure\Curation\Entity\NullStatus;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\TweetBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\Exception\BadAuthenticationDataException;
use App\Twitter\Infrastructure\Exception\InconsistentTokenRepository;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException;
use App\Twitter\Infrastructure\Http\Client\Exception\NotFoundStatusException;
use App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException;
use App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException;
use App\Twitter\Infrastructure\Http\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Http\Entity\Status;
use App\Twitter\Infrastructure\Http\Repository\ArchivedStatusRepository;
use App\Twitter\Infrastructure\Publication\Dto\TaggedStatus;
use App\Twitter\Infrastructure\Publication\Repository\NotFoundStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use ReflectionException;
use function array_key_exists;
use function count;
use function sprintf;

class TweetAwareHttpClient implements TweetAwareHttpClientInterface
{
    use HttpClientTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use PublicationPersistenceTrait;
    use LoggerTrait;
    use StatusRepositoryTrait;
    use MemberRepositoryTrait;
    use TweetBatchCollectedEventRepositoryTrait;

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

        $this->httpClient->shouldRaiseExceptionOnApiLimit = true;
        $status = $this->httpClient->showStatus($statusId);

        $this->entityManager->clear();

        try {
            $this->publicationPersistence->persistStatusPublications(
                [$status],
                new AccessToken($this->httpClient->userToken)
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
            $this->ensureMemberHasBio($member, $member->twitterScreenName());

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
            $this->httpClient,
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
     * @param CurationSelectorsInterface $selectors
     * @param array                      $options
     * @param bool                       $discoverPublicationWithMaxId
     *
     * @return array
     * @throws ApiAccessRateLimitException
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
        CurationSelectorsInterface $selectors,
                                   $options,
        bool                       $discoverPublicationWithMaxId = true
    ): array {
        $options = $this->removeCollectOptions($selectors, $options);
        $options = $this->updateExtremum(
            $selectors,
            $options,
            $discoverPublicationWithMaxId
        );

        // When there is an upper bound and a date before which publications
        // are to be collected, pick the date over the upper bound for collection
        if (
            array_key_exists('max_id', $options)
            && $selectors->dateBeforeWhichPublicationsAreToBeCollected() // Looking into the past
        ) {
            unset($options['max_id']);
        }

        $statuses = $this->tweetsBatchCollectedEventRepository
            ->collectedPublicationBatch(
                $selectors,
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
                $selectors,
                $options,
                $discoverPublicationWithMaxId = false
            );
        }

        return $statuses;
    }

    public function updateExtremum(
        CurationSelectorsInterface $selectors,
        array                      $options,
        bool                       $discoverPublicationsWithMaxId = true
    ): array {
        if ($selectors->dateBeforeWhichPublicationsAreToBeCollected()) {
            $discoverPublicationsWithMaxId = true;
        }

        $options = $this->removeMaxIdFromOptions($options, $discoverPublicationsWithMaxId);

        $findingDirection = $this->getExtremumUpdateMethod($discoverPublicationsWithMaxId);
        $extremum           = $this->findExtremum(
            $selectors,
            $options,
            $findingDirection
        );

        $logPrefix = $this->getLogPrefix($selectors);

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
                    $options[FetchTweetInterface::SCREEN_NAME],
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
                $options[FetchTweetInterface::SCREEN_NAME]
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

    private function findExtremum(
        CurationSelectorsInterface $selectors,
        array                      $options,
                                   $findingDirection
    ): array {
        if ($selectors->dateBeforeWhichPublicationsAreToBeCollected()) {
            return $this->statusRepository->findNextExtremum(
                $options[FetchTweetInterface::SCREEN_NAME],
                $findingDirection,
                $selectors->dateBeforeWhichPublicationsAreToBeCollected()
            );
        }

        return $this->statusRepository->findLocalMaximum(
            $options[FetchTweetInterface::SCREEN_NAME],
            $selectors->dateBeforeWhichPublicationsAreToBeCollected()
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

    private function getLogPrefix(CurationSelectorsInterface $selectors): string
    {
        if (!$selectors->dateBeforeWhichPublicationsAreToBeCollected()) {
            return '';
        }

        return 'local ';
    }

    private function removeCollectOptions(
        CurationSelectorsInterface $selectors,
                                   $options
    ) {
        if ($selectors->dateBeforeWhichPublicationsAreToBeCollected()) {
            unset($options[FetchTweetInterface::BEFORE]);
        }
        if (array_key_exists(FetchTweetInterface::TWITTER_LIST_ID, $options)) {
            unset($options[FetchTweetInterface::TWITTER_LIST_ID]);
        }

        return $options;
    }
}
