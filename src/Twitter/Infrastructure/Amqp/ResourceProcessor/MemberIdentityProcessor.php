<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Membership\Domain\Compliance\Compliance;
use App\Membership\Domain\Exception\MembershipException;
use App\Twitter\Domain\Api\Accessor\MemberProfileAccessorInterface;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Domain\Publication\Repository\PublishersListRepositoryInterface;
use App\Twitter\Infrastructure\Amqp\Exception\ContinuePublicationException;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableMemberException;
use App\Twitter\Infrastructure\Amqp\Exception\StopPublicationException;
use App\Twitter\Infrastructure\Amqp\Message\FetchTweet;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberProfileAccessorTrait;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;
use App\Twitter\Infrastructure\PublishersList\AggregateAwareTrait;
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
     * @var PublishersListRepositoryInterface
     */
    private PublishersListRepositoryInterface $aggregateRepository;

    public function __construct(
        MessageBusInterface $dispatcher,
        MemberProfileAccessorInterface $memberProfileAccessor,
        PublishersListRepositoryInterface $aggregateRepository,
        LoggerInterface $logger
    ) {
        $this->dispatcher                 = $dispatcher;
        $this->aggregateRepository        = $aggregateRepository;
        $this->memberProfileAccessor      = $memberProfileAccessor;
        $this->logger                     = $logger;
    }

    /**
     * @throws ContinuePublicationException
     * @throws \App\Membership\Domain\Exception\MembershipException
     * @throws StopPublicationException
     */
    public function process(
        MemberIdentity           $memberIdentity,
        CurationRulesetInterface $ruleset,
        TokenInterface           $token,
        PublishersList           $list
    ): int {
        try {
            $this->dispatchAmqpMessagesForFetchingMemberPublications(
                $memberIdentity,
                $ruleset,
                $token,
                $list
            );

            return 1;
        } catch (SkippableMemberException $exception) {
            $this->logger->info($exception->getMessage());

            return 0;
        } catch (MembershipException $exception) {
            if (Compliance::shouldBreakPublication($exception)) {
                $this->logger->info($exception->getMessage());

                StopPublicationException::throws($exception->getMessage(), $exception);
            }

            if (Compliance::shouldContinuePublication($exception)) {
                ContinuePublicationException::throws($exception->getMessage(), $exception);
            }

            throw $exception;
        }
    }

    /**
     * @throws SkippableMemberException
     */
    private function dispatchAmqpMessagesForFetchingMemberPublications(
        MemberIdentity           $memberIdentity,
        CurationRulesetInterface $ruleset,
        TokenInterface           $token,
        PublishersList           $list
    ): void {
        if ($ruleset->isSingleMemberCurationActive($memberIdentity)) {
            throw new SkippableMemberException(
                sprintf(
                    'Skipping "%s" as member restriction applies',
                    $memberIdentity->screenName()
                )
            );
        }

        $member = $this->memberProfileAccessor->getMemberByIdentity(
            $memberIdentity
        );

        $ruleset->skipLowVolumeTweetingMember($member, $memberIdentity);

        Compliance::skipProtectedMember($member, $memberIdentity);
        Compliance::skipSuspendedMember($member, $memberIdentity);

        $FetchMemberStatus = FetchTweet::identifyMember(
            $this->aggregateRepository->byName(
                $member->twitterScreenName(),
                $list->name(),
                $list->id()
            ),
            $token,
            $member,
            $ruleset->tweetCreationDateFilter()
        );

        $this->dispatcher->dispatch($FetchMemberStatus);
    }
}