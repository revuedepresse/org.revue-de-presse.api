<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Membership\Domain\Compliance\Compliance;
use App\Membership\Domain\Exception\MembershipException;
use App\Twitter\Domain\Http\Client\MemberProfileAwareHttpClientInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Domain\Publication\Repository\PublishersListRepositoryInterface;
use App\Twitter\Infrastructure\Amqp\Exception\ContinuePublicationException;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableMemberException;
use App\Twitter\Infrastructure\Amqp\Exception\StopPublicationException;
use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweet;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberProfileAccessorTrait;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;
use App\Twitter\Infrastructure\PublishersList\TwitterListAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use function sprintf;

class MemberIdentityProcessor implements MemberIdentityProcessorInterface
{
    use TwitterListAwareTrait;
    use MemberProfileAccessorTrait;

    private MessageBusInterface $dispatcher;

    private LoggerInterface $logger;

    private PublishersListRepositoryInterface $aggregateRepository;

    public function __construct(
        MessageBusInterface                   $dispatcher,
        MemberProfileAwareHttpClientInterface $memberProfileAccessor,
        PublishersListRepositoryInterface     $aggregateRepository,
        LoggerInterface                       $logger
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

        $fetchTweetAmqpMessage = FetchAuthoredTweet::identifyMember(
            $this->aggregateRepository->byName(
                $member->twitterScreenName(),
                $list->name(),
                $list->id()
            ),
            $token,
            $member,
            $ruleset->tweetCreationDateFilter()
        );

        $this->dispatcher->dispatch($fetchTweetAmqpMessage);
    }
}
