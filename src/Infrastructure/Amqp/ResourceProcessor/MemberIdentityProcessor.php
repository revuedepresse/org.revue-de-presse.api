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
use App\Infrastructure\Twitter\Api\UnavailableResource;
use App\Infrastructure\Twitter\Api\UnavailableResourceHandler;
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

    private UnavailableResourceHandler $unavailableResourceHandler;

    public function __construct(
        MessageBusInterface $dispatcher,
        PublicationListRepositoryInterface $aggregateRepository,
        ApiAccessorInterface $accessor,
        MemberRepositoryInterface $memberRepository,
        UnavailableResourceHandler $unavailableResourceHandler,
        LoggerInterface $logger
    ) {
        $this->dispatcher                 = $dispatcher;
        $this->aggregateRepository        = $aggregateRepository;
        $this->accessor                   = $accessor;
        $this->memberRepository           = $memberRepository;
        $this->unavailableResourceHandler = $unavailableResourceHandler;
        $this->logger                     = $logger;
    }

    /**
     * @param MemberIdentity               $memberIdentity
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param PublicationList              $list
     *
     * @return void
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
            $this->unavailableResourceHandler->handle(
                $memberIdentity,
                UnavailableResource::ofTypeAndRootCause(
                    $exception->getCode(),
                    $exception->getMessage()
                )
            );
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