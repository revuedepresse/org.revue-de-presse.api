<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\ResourceProcessor;

use App\Infrastructure\Twitter\Api\Accessor\Exception\UnexpectedApiResponseException;
use App\Aggregate\AggregateAwareTrait;
use App\Amqp\Exception\SkippableMemberException;
use App\Api\Entity\TokenInterface;
use App\Domain\Collection\PublicationStrategyInterface;
use App\Domain\Membership\Exception\MembershipException;
use App\Domain\Membership\MemberFacingStrategy;
use App\Domain\Resource\MemberIdentity;
use App\Domain\Resource\PublicationList;
use App\Infrastructure\Amqp\Exception\ContinuePublicationException;
use App\Infrastructure\Amqp\Exception\StopPublicationException;
use App\Infrastructure\Amqp\Message\FetchMemberLikes;
use App\Infrastructure\Amqp\Message\FetchMemberStatus;
use App\Infrastructure\DependencyInjection\Membership\MemberProfileAccessorTrait;
use App\Infrastructure\Repository\PublicationList\PublicationListRepositoryInterface;
use App\Infrastructure\Twitter\Api\Accessor\MemberProfileAccessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use function sprintf;

class MemberIdentityProcessor implements MemberIdentityProcessorInterface
{
    use AggregateAwareTrait;
    use MemberProfileAccessorTrait;

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

    public function __construct(
        MessageBusInterface $dispatcher,
        MemberProfileAccessorInterface $memberProfileAccessor,
        PublicationListRepositoryInterface $aggregateRepository,
        LoggerInterface $logger
    ) {
        $this->dispatcher                 = $dispatcher;
        $this->aggregateRepository        = $aggregateRepository;
        $this->memberProfileAccessor      = $memberProfileAccessor;
        $this->logger                     = $logger;
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
     */
    public function process(
        MemberIdentity $memberIdentity,
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        PublicationList $list
    ): int {
        try {
            $this->dispatchPublications($memberIdentity, $strategy, $token, $list);

            return 1;
        } catch (SkippableMemberException $exception) {
            $this->logger->info($exception->getMessage());

            return 0;
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
     * @throws SkippableMemberException
     */
    private function dispatchPublications(
        MemberIdentity $memberIdentity,
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        PublicationList $list
    ): void {
        $this->skipUnrestrictedMember($memberIdentity, $strategy);

        $member = $this->memberProfileAccessor->getMemberByIdentity(
            $memberIdentity
        );

        $strategy->guardAgainstWhisperingMember($member, $memberIdentity);

        MemberFacingStrategy::guardAgainstProtectedMember($member, $memberIdentity);
        MemberFacingStrategy::guardAgainstSuspendedMember($member, $memberIdentity);

        $FetchMemberStatus = FetchMemberStatus::makeMemberIdentityCard(
            $this->aggregateRepository->getListAggregateByName(
                $member->getTwitterUsername(),
                $list->name(),
                $list->id()
            ),
            $token,
            $member,
            $strategy->dateBeforeWhichPublicationsAreCollected(),
            $strategy->shouldFetchLikes()
        );

        if ($strategy->shouldFetchLikes()) {
            $this->dispatcher->dispatch(
                FetchMemberLikes::from(
                    $FetchMemberStatus
                )
            );

            return;
        }

        $this->dispatcher->dispatch($FetchMemberStatus);
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