<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Infrastructure\Amqp\Exception\InvalidListNameException;
use App\Twitter\Infrastructure\Amqp\Exception\UnexpectedOwnershipException;
use App\Twitter\Infrastructure\Amqp\ResourceProcessor\PublishersListProcessorInterface;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\ListBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\RateLimitComplianceTrait;
use App\Twitter\Infrastructure\DependencyInjection\OwnershipAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListProcessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenChangeTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\Exception\EmptyListException;
use App\Twitter\Infrastructure\Http\AccessToken\TokenChangeInterface;
use App\Twitter\Infrastructure\Http\Entity\NullToken;
use App\Twitter\Infrastructure\Http\Exception\UnavailableTokenException;
use App\Twitter\Infrastructure\Http\Resource\MemberOwnerships;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;
use App\Twitter\Infrastructure\Http\Selector\AuthenticatedSelector;
use App\Twitter\Infrastructure\Http\Selector\ListsBatchSelector;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdInterface;
use Closure;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_map;
use function count;
use function implode;
use function in_array;
use function sprintf;

class DispatchAmqpMessagesToFetchTweets implements DispatchAmqpMessagesToFetchTweetsInterface, CorrelationIdAwareInterface
{
    use RateLimitComplianceTrait;
    use ListBatchCollectedEventRepositoryTrait;
    use OwnershipAccessorTrait;
    use PublishersListProcessorTrait;
    use TokenChangeTrait;
    use TranslatorTrait;

    private LoggerInterface $logger;

    private HttpClientInterface $accessor;

    private Closure $writer;

    private CurationRulesetInterface $ruleset;

    public function __construct(
        HttpClientInterface              $accessor,
        PublishersListProcessorInterface $publishersListProcessor,
        TokenChangeInterface             $tokenChange,
        LoggerInterface                  $logger,
        TranslatorInterface              $translator
    ) {
        $this->accessor                 = $accessor;
        $this->publishersListProcessor  = $publishersListProcessor;
        $this->tokenChange              = $tokenChange;
        $this->translator               = $translator;
        $this->logger                   = $logger;
    }

    public function dispatchFetchTweetsMessages(
        CurationRulesetInterface $ruleset,
        TokenInterface           $token,
        Closure                  $writer
    ): void {
        $this->writer   = $writer;
        $this->ruleset = $ruleset;

        $memberOwnerships = $this->fetchMemberOwnerships($token);

        /** @var PublishersList $list */
        foreach ($memberOwnerships->ownershipCollection()->toArray() as $list) {
            try {
                $publishedMessages = $this->guardAgainstTokenFreeze(
                    function (TokenInterface $token) use ($list, $ruleset) {
                        return $this->publishersListProcessor
                            ->processPublishersList(
                                $list,
                                $token,
                                $ruleset
                            );
                    },
                    $memberOwnerships->token()
                );

                if ($publishedMessages) {
                    $writer(
                        $this->translator->trans(
                            'amqp.production.list_members.success',
                            [
                                '{{ count }}' => $publishedMessages,
                                '{{ list }}'  => $list->name(),
                            ]
                        )
                    );
                }
            } catch (EmptyListException $exception) {
                $this->logger->info($exception->getMessage());
            } catch (Exception $exception) {
                $this->logger->critical(
                    $exception->getMessage(),
                    ['stacktrace' => $exception->getTraceAsString()]
                );
                UnexpectedOwnershipException::throws($exception->getMessage());
            }
        }
    }

    public function correlationId(): CorrelationIdInterface
    {
        if ($this->ruleset instanceof CorrelationIdAwareInterface) {
            return $this->ruleset->correlationId();
        }

        return CorrelationId::generate();
    }

    private function fetchMemberOwnerships(TokenInterface $token): MemberOwnerships {
        $cursor = $this->ruleset->isCurationCursorActive();

        $memberOwnership = null;
        $allOwnerships   = [[]];

        while ($this->keepDispatching($memberOwnership)) {
            $nextMemberOwnership = $this->guardAgainstTokenFreeze(
                function (TokenInterface $token) use ($memberOwnership) {
                    return $this->ownershipAccessor
                        ->getOwnershipsForMemberHavingScreenNameAndToken(
                            new AuthenticatedSelector(
                                $token,
                                $this->ruleset->whoseListSubscriptionsAreCurated(),
                                $this->correlationId()
                            ),
                            $memberOwnership
                        );
                },
                $token
            );

            if ($this->shouldSkipDispatch($cursor, $memberOwnership)) {
                $memberOwnership = $nextMemberOwnership;
                continue;
            }

            $memberOwnership = $nextMemberOwnership;

            /** @var MemberOwnerships $memberOwnership */
            $ownerships = $this->guardAgainstInvalidListName(
                $memberOwnership->ownershipCollection(),
                $memberOwnership->token()
            );

            $mergeResult = array_unique(
                array_merge(
                    $allOwnerships[count($allOwnerships) - 1],
                    $ownerships->toArray()
                )
            );

            if ($mergeResult !== $allOwnerships[count($allOwnerships) - 1]) {
                $allOwnerships[] = $mergeResult;
            }
        }

        $allOwnerships = $allOwnerships[count($allOwnerships) - 1];
        usort(
            $allOwnerships,
            static function (PublishersList $leftPublishersList, PublishersList $rightPublishersList) {
                return $leftPublishersList->id() <=> $rightPublishersList->id();
            }
        );

        return MemberOwnerships::from(
            $memberOwnership->token(),
            OwnershipCollection::fromArray($allOwnerships)
        );
    }

    private function findNextBatchOfListOwnerships(
        OwnershipCollectionInterface $ownerships
    ): OwnershipCollectionInterface {
        $previousCursor = -1;

        $eventRepository = $this->listsBatchCollectedEventRepository;

        if ($this->ruleset->isSingleListFilterActive()) {
            return $eventRepository->collectedListsBatch(
                $this->ownershipAccessor,
                new ListsBatchSelector(
                    $this->ruleset->whoseListSubscriptionsAreCurated(),
                    (string) $ownerships->nextPage(),
                    $this->correlationId()
                )
            );
        }

        while ($this->targetListHasNotBeenFound(
            $ownerships,
            $this->ruleset->singleListFilter()
        )) {
            $ownerships = $eventRepository->collectedListsBatch(
                $this->ownershipAccessor,
                new ListsBatchSelector(
                    $this->ruleset->whoseListSubscriptionsAreCurated(),
                    (string) $ownerships->nextPage(),
                    $this->correlationId()
                )
            );

            if (!$ownerships->nextPage() || $previousCursor === $ownerships->nextPage()) {
                $this->write(
                    sprintf(
                        implode(
                            [
                                'No more pages of members lists to be processed. ',
                                'Does the Twitter API access token used belong to "%s"?',
                            ]
                        ),
                        $this->ruleset->whoseListSubscriptionsAreCurated()
                    )
                );

                break;
            }

            $previousCursor = $ownerships->nextPage();
        }

        return $ownerships;
    }

    private function guardAgainstInvalidListName(
        OwnershipCollectionInterface $ownerships,
        TokenInterface $token
    ): OwnershipCollectionInterface {
        if ($this->ruleset->isSingleListFilterInactive()) {
            return $ownerships;
        }

        $listRestriction = $this->ruleset->singleListFilter();

        // Try to find Twitter list by following the next cursor
        if (
            $this->targetListHasNotBeenFound($ownerships, $listRestriction)
            && $ownerships->nextPage() !== -1
        ) {
            return $this->findNextBatchOfListOwnerships($ownerships);
        }

        if ($this->targetListHasNotBeenFound($ownerships, $listRestriction)) {
            $ownerships = $this->guardAgainstInvalidToken(
                $ownerships,
                $token
            );
        }

        // Give up on the list
        if ($this->targetListHasNotBeenFound($ownerships, $listRestriction)) {
            $message = sprintf(
                'Invalid list name ("%s"). Could not be found',
                $listRestriction
            );
            $this->write($message);

            throw new InvalidListNameException($message);
        }

        return $ownerships;
    }

    private function guardAgainstInvalidToken(
        OwnershipCollectionInterface $ownerships,
        TokenInterface $token
    ): OwnershipCollectionInterface {
        $this->tokenChange->replaceAccessToken(
            $token,
            $this->accessor
        );
        $ownerships->goBackToFirstPage();

        return $this->findNextBatchOfListOwnerships($ownerships);
    }

    private function guardAgainstTokenFreeze(
        callable $callable,
        TokenInterface $token
    ) {
        try {
            return $callable($token);
        } catch (UnavailableTokenException $exception) {
            $unavailableToken = $token;
            $token = $exception::firstTokenToBeAvailable();

            $now = new DateTimeImmutable(
                'now',
                $unavailableToken->getFrozenUntil()->getTimezone()
            );

            if ($token === null || $token instanceof NullToken) {
                $token = $unavailableToken;
            }

            $this->rateLimitCompliance->waitFor(
                $token->getFrozenUntil()->getTimestamp() - $now->getTimestamp(),
                ['{{ token }}' => $token->firstIdentifierCharacters()]
            );

            return $this->guardAgainstTokenFreeze($callable, $token);
        }
    }

    private function keepDispatching($memberOwnership): bool
    {
        return $memberOwnership === null
            || ($memberOwnership instanceof MemberOwnerships
                && $memberOwnership->ownershipCollection()->isNotEmpty());
    }

    private function mapOwnershipsLists(OwnershipCollectionInterface $ownerships): array
    {
        return array_map(
            fn(PublishersList $list) => $list->name(),
            $ownerships->toArray()
        );
    }

    private function shouldSkipDispatch(?int $cursor, ?MemberOwnerships $memberOwnership): bool
    {
        return $cursor !== -1
            && ($memberOwnership === null
                || ($memberOwnership instanceof MemberOwnerships
                    && $memberOwnership->ownershipCollection()->nextPage() !== $cursor));
    }

    private function targetListHasBeenFound($ownerships, string $listRestriction): bool
    {
        $listNames = $this->mapOwnershipsLists($ownerships);

        return in_array($listRestriction, $listNames, true);
    }

    private function targetListHasNotBeenFound($ownerships, string $listRestriction): bool
    {
        return !$this->targetListHasBeenFound($ownerships, $listRestriction);
    }

    private function write(string $message): void
    {
        $write = $this->writer;
        $write($message);
    }
}