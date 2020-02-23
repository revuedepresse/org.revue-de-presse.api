<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\ResourceProcessor;

use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Aggregate\AggregateAwareTrait;
use App\Amqp\SkippableMemberException;
use App\Api\Entity\TokenInterface;
use App\Domain\Collection\PublicationStrategyInterface;
use App\Domain\Membership\Exception\MembershipException;
use App\Domain\Membership\MemberFacingStrategy;
use App\Domain\Resource\MemberIdentity;
use App\Domain\Resource\PublicationList;
use App\Infrastructure\Amqp\Exception\ContinuePublicationException;
use App\Infrastructure\Amqp\Exception\StopPublicationException;
use App\Infrastructure\Amqp\Message\FetchMemberLikes;
use App\Infrastructure\Amqp\Message\FetchMemberStatuses;
use App\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Infrastructure\Repository\Membership\MemberRepositoryInterface;
use App\Infrastructure\Repository\PublicationList\PublicationListRepositoryInterface;
use App\Membership\Entity\MemberInterface;
use App\Membership\Model\Member;
use App\Twitter\Api\ApiAccessorInterface;
use App\Twitter\Exception\UnavailableResourceException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use function sprintf;

class MemberIdentityProcessor implements MemberIdentityProcessorInterface
{
    use AggregateAwareTrait;
    use MemberRepositoryTrait;

    private const EXCEPTION_MEMBER_NOT_FOUND     = 'User with screen name %s can not be found';
    private const EXCEPTION_SUSPENDED_MEMBER     = 'User with screen name "%s" has been suspended (code: %d, message: "%s")';
    private const EXCEPTION_PROTECTED_MEMBER     = 'User with screen name "%s" has a protected account (code: %d, message: "%s")';
    private const EXCEPTION_UNAVAILABLE_RESOURCE = 'Unavailable resource for user with screen name %s (code: %d, message: "%s")';

    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $dispatcher;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var PublicationListRepositoryInterface
     */
    private PublicationListRepositoryInterface $aggregateRepository;

    /**
     * @var ApiAccessorInterface
     */
    private ApiAccessorInterface $accessor;

    public function __construct(
        MessageBusInterface $dispatcher,
        PublicationListRepositoryInterface $aggregateRepository,
        ApiAccessorInterface $accessor,
        MemberRepositoryInterface $memberRepository,
        LoggerInterface $logger
    ) {
        $this->dispatcher          = $dispatcher;
        $this->aggregateRepository = $aggregateRepository;
        $this->accessor            = $accessor;
        $this->memberRepository    = $memberRepository;
        $this->logger              = $logger;
    }

    /**
     * @param MemberIdentity               $memberIdentity
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param PublicationList              $list
     *
     * @return int
     * @throws ContinuePublicationException
     * @throws MembershipException
     * @throws StopPublicationException
     * @throws UnexpectedApiResponseException
     */
    public function process(
        MemberIdentity $memberIdentity,
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        PublicationList $list
    ): void {
        try {
            $this->dispatchPublications($memberIdentity, $strategy, $token, $list);
        } catch (SkippableMemberException $exception) {
            $this->logger->info($exception->getMessage());
        } catch (MembershipException $exception) {
            if (MemberFacingStrategy::shouldBreakPublication($exception)) {
                $this->logger->info($exception->getMessage());

                StopPublicationException::throws($exception->getMessage());
            }

            if (MemberFacingStrategy::shouldContinuePublication($exception)) {
                ContinuePublicationException::throws($exception);
            }

            throw $exception;
        }
    }

    /**
     * @param MemberIdentity               $memberIdentity
     * @param UnavailableResourceException $exception
     *
     * @throws MembershipException
     */
    protected function handleUnavailableResourceException(
        MemberIdentity $memberIdentity,
        UnavailableResourceException $exception
    ): void {
        if (
            $exception->getCode() === $this->accessor->getMemberNotFoundErrorCode()
            || $exception->getCode() === $this->accessor->getUserNotFoundErrorCode()
        ) {
            $message = sprintf(self::EXCEPTION_MEMBER_NOT_FOUND, $memberIdentity->screenName());
            $this->logger->info($message);

            throw new MembershipException($message, self::NOT_FOUND_MEMBER);
        }

        if ($exception->getCode() === $this->accessor->getSuspendedUserErrorCode()) {
            $message = sprintf(
                self::EXCEPTION_SUSPENDED_MEMBER,
                $memberIdentity->screenName(),
                $exception->getCode(),
                $exception->getMessage()
            );
            $this->logger->error($message);

            $this->memberRepository->saveSuspended(
                $memberIdentity,
                (object) ['screen_name' => $memberIdentity->screenName()],
                );

            MembershipException::throws($message, self::SUSPENDED_USER);
        }

        if ($exception->getCode() === $this->accessor->getProtectedAccountErrorCode()) {
            $message = sprintf(
                self::EXCEPTION_PROTECTED_MEMBER,
                $memberIdentity->screenName(),
                $exception->getCode(),
                $exception->getMessage()
            );
            $this->logger->error($message);

            $this->memberRepository->saveProtectedMember($memberIdentity);

            MembershipException::throws($message, self::PROTECTED_ACCOUNT);
        }

        $message = sprintf(
            self::EXCEPTION_UNAVAILABLE_RESOURCE,
            $memberIdentity->screenName(),
            $exception->getCode(),
            $exception->getMessage()
        );
        $this->logger->error($message);

        MembershipException::throws($message, self::UNAVAILABLE_RESOURCE);
    }

    /**
     * @param MemberIdentity               $memberIdentity
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param PublicationList              $list
     *
     * @throws MembershipException
     * @throws SkippableMemberException
     * @throws UnexpectedApiResponseException
     */
    private function dispatchPublications(
        MemberIdentity $memberIdentity,
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        PublicationList $list
    ): void {
        $this->skipUnrestrictedMember($memberIdentity, $strategy);

        $member = $this->getMessageMember($memberIdentity);

        $strategy->guardAgainstWhisperingMember($member, $memberIdentity);

        MemberFacingStrategy::guardAgainstProtectedMember($member, $memberIdentity);
        MemberFacingStrategy::guardAgainstSuspendedMember($member, $memberIdentity);

        $fetchMemberStatuses = FetchMemberStatuses::makeMemberIdentityCard(
            $this->aggregateRepository->getListAggregateByName(
                $member->getTwitterUsername(),
                $list->name(),
                $list->id()
            ),
            $token,
            $member,
            $strategy->collectPublicationsPrecedingThoseAlreadyCollected()
        );

        $this->dispatcher->dispatch($fetchMemberStatuses);

        $this->dispatcher->dispatch(
            FetchMemberLikes::from(
                $fetchMemberStatuses
            )
        );
    }

    /**
     * @param MemberIdentity $memberIdentity
     *
     * @return MemberInterface
     * @throws MembershipException
     * @throws UnexpectedApiResponseException
     */
    private function getMessageMember(MemberIdentity $memberIdentity): MemberInterface
    {
        /** @var Member $member */
        $member            = $this->memberRepository->findOneBy(
            ['twitterID' => $memberIdentity->id()]
        );
        $preExistingMember = $member instanceof Member;

        if ($preExistingMember && $member->hasNotBeenDeclaredAsNotFound()) {
            return $member;
        }

        try {
            $twitterUser = $this->accessor->getMemberProfile(
                $memberIdentity->screenName()
            );
        } catch (UnavailableResourceException $exception) {
            $this->handleUnavailableResourceException($memberIdentity, $exception);
        }

        if (!isset($twitterUser)) {
            throw new UnexpectedApiResponseException(
                'An unexpected error has occurred.',
                self::UNEXPECTED_ERROR
            );
        }

        if (!$preExistingMember) {
            return $this->memberRepository->saveMemberWithAdditionalProps(
                $memberIdentity,
                $twitterUser
            );
        }

        $member = $member->setTwitterUsername($twitterUser->screenName());

        return $this->memberRepository->declareUserAsFound($member);
    }

    /**
     * @param MemberIdentity               $memberIdentity
     * @param PublicationStrategyInterface $strategy
     *
     * @throws SkippableMemberException
     */
    private function skipUnrestrictedMember(
        MemberIdentity $memberIdentity,
        PublicationStrategyInterface $strategy
    ): void {
        if ($strategy->restrictDispatchToSpecificMember($memberIdentity)) {
            throw new SkippableMemberException(
                sprintf(
                    'Skipping "%s" as member restriction applies',
                    $memberIdentity->screenName()
                )
            );
        }
    }
}