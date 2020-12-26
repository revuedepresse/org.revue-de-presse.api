<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Domain\Resource\OwnershipCollectionInterface;
use App\Twitter\Infrastructure\Amqp\Exception\InvalidListNameException;
use App\Twitter\Infrastructure\Amqp\Exception\UnexpectedOwnershipException;
use App\Twitter\Infrastructure\Api\AccessToken\TokenChangeInterface;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Infrastructure\Api\Entity\NullToken;
use App\Twitter\Infrastructure\Api\Exception\UnavailableTokenException;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Domain\Resource\PublishersList;
use App\Twitter\Infrastructure\Amqp\ResourceProcessor\PublishersListProcessorInterface;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiLimitModeratorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Collection\OwnershipBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\OwnershipAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListProcessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenChangeTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Domain\Api\ApiAccessorInterface;
use App\Twitter\Infrastructure\Exception\EmptyListException;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdInterface;
use App\Twitter\Infrastructure\Twitter\Api\Selector\AuthenticatedSelector;
use App\Twitter\Infrastructure\Twitter\Api\Selector\MemberOwnershipsBatchSelector;
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

class PublicationMessageDispatcher implements PublicationMessageDispatcherInterface, CorrelationIdAwareInterface
{
    use ApiLimitModeratorTrait;
    use OwnershipBatchCollectedEventRepositoryTrait;
    use OwnershipAccessorTrait;
    use PublishersListProcessorTrait;
    use TokenChangeTrait;
    use TranslatorTrait;

    private LoggerInterface $logger;

    private ApiAccessorInterface $accessor;

    private Closure $writer;

    private PublicationStrategyInterface $strategy;

    public function __construct(
        ApiAccessorInterface $accessor,
        PublishersListProcessorInterface $publishersListProcessor,
        TokenChangeInterface $tokenChange,
        LoggerInterface $logger,
        TranslatorInterface $translator
    ) {
        $this->accessor                 = $accessor;
        $this->publishersListProcessor = $publishersListProcessor;
        $this->tokenChange              = $tokenChange;
        $this->translator               = $translator;
        $this->logger                   = $logger;
    }

    /**
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param Closure                      $writer
     */
    public function dispatchPublicationMessages(
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        Closure $writer
    ): void {
        $this->writer   = $writer;
        $this->strategy = $strategy;

        $memberOwnerships = $this->fetchMemberOwnerships($token);

        /** @var PublishersList $list */
        foreach ($memberOwnerships->ownershipCollection()->toArray() as $list) {
            try {
                $publishedMessages = $this->guardAgainstTokenFreeze(
                    function (TokenInterface $token) use ($list, $strategy) {
                        return $this->publishersListProcessor
                            ->processPublishersList(
                                $list,
                                $token,
                                $strategy
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
        if ($this->strategy instanceof CorrelationIdAwareInterface) {
            return $this->strategy->correlationId();
        }

        return CorrelationId::generate();
    }

    private function fetchMemberOwnerships(TokenInterface $token): MemberOwnerships {
        $cursor = $this->strategy->shouldFetchPublicationsFromCursor();

        $memberOwnership = null;
        $allOwnerships   = [[]];

        while ($this->keepDispatching($memberOwnership)) {
            $nextMemberOwnership = $this->guardAgainstTokenFreeze(
                function (TokenInterface $token) use ($memberOwnership) {
                    return $this->ownershipAccessor
                        ->getOwnershipsForMemberHavingScreenNameAndToken(
                            new AuthenticatedSelector(
                                $token,
                                $this->strategy->onBehalfOfWhom(),
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

        $eventRepository = $this->ownershipBatchCollectedEventRepository;

        if ($this->strategy->listRestriction()) {
            return $eventRepository->collectedOwnershipBatch(
                $this->accessor,
                new MemberOwnershipsBatchSelector(
                    $this->strategy->onBehalfOfWhom(),
                    (string) $ownerships->nextPage(),
                    $this->correlationId()
                )
            );
        }

        while ($this->targetListHasNotBeenFound(
            $ownerships,
            $this->strategy->forWhichList()
        )) {
            $ownerships = $eventRepository->collectedOwnershipBatch(
                $this->accessor,
                new MemberOwnershipsBatchSelector(
                    $this->strategy->onBehalfOfWhom(),
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
                        $this->strategy->onBehalfOfWhom()
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
        if ($this->strategy->noListRestriction()) {
            return $ownerships;
        }

        $listRestriction = $this->strategy->forWhichList();

        // Try to find publishers list by following the next cursor
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

            $this->moderator->waitFor(
                $token->getFrozenUntil()->getTimestamp() - $now->getTimestamp(),
                ['{{ token }}' => $token->firstIdentifierCharacters()]
            );

            return $this->guardAgainstTokenFreeze($callable, $token);
        }
    }

    /**
     * @param $memberOwnership
     *
     * @return bool
     */
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

    /**
     * @param int|null              $cursor
     * @param MemberOwnerships|null $memberOwnership
     *
     * @return bool
     */
    private function shouldSkipDispatch(?int $cursor, ?MemberOwnerships $memberOwnership): bool
    {
        return $cursor !== -1
            && ($memberOwnership === null
                || ($memberOwnership instanceof MemberOwnerships
                    && $memberOwnership->ownershipCollection()->nextPage() !== $cursor));
    }

    /**
     * @param $ownerships
     * @param $listRestriction
     *
     * @return bool
     */
    private function targetListHasBeenFound($ownerships, string $listRestriction): bool
    {
        $listNames = $this->mapOwnershipsLists($ownerships);

        return in_array($listRestriction, $listNames, true);
    }

    /**
     * @param        $ownerships
     * @param string $listRestriction
     *
     * @return bool
     */
    private function targetListHasNotBeenFound($ownerships, string $listRestriction): bool
    {
        return !$this->targetListHasBeenFound($ownerships, $listRestriction);
    }

    /**
     * @param string $message
     */
    private function write(string $message): void
    {
        $write = $this->writer;
        $write($message);
    }
}