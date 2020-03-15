<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\MessageBus;

use App\Amqp\Exception\InvalidListNameException;
use App\Amqp\Exception\UnexpectedOwnershipException;
use App\Api\AccessToken\TokenChangeInterface;
use App\Api\Entity\TokenInterface;
use App\Domain\Collection\PublicationStrategyInterface;
use App\Domain\Resource\MemberOwnerships;
use App\Domain\Resource\OwnershipCollection;
use App\Domain\Resource\PublicationList;
use App\Infrastructure\Amqp\ResourceProcessor\PublicationListProcessorInterface;
use App\Infrastructure\DependencyInjection\Collection\OwnershipBatchCollectedEventRepositoryTrait;
use App\Infrastructure\DependencyInjection\Publication\PublicationListProcessorTrait;
use App\Infrastructure\DependencyInjection\TokenChangeTrait;
use App\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Api\ApiAccessorInterface;
use App\Infrastructure\Twitter\Api\Accessor\OwnershipAccessorInterface;
use App\Twitter\Exception\EmptyListException;
use Closure;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_map;
use function implode;
use function in_array;
use function sprintf;

class PublicationMessageDispatcher implements PublicationMessageDispatcherInterface
{
    use OwnershipBatchCollectedEventRepositoryTrait;
    use PublicationListProcessorTrait;
    use TokenChangeTrait;
    use TranslatorTrait;

    private LoggerInterface $logger;

    private OwnershipAccessorInterface $ownershipAccessor;

    private ApiAccessorInterface $accessor;

    private Closure $writer;

    private PublicationStrategyInterface $strategy;

    public function __construct(
        ApiAccessorInterface $accessor,
        OwnershipAccessorInterface $ownershipAccessor,
        PublicationListProcessorInterface $publicationListProcessor,
        TokenChangeInterface $tokenChange,
        LoggerInterface $logger,
        TranslatorInterface $translator
    )
    {
        $this->accessor = $accessor;
        $this->ownershipAccessor = $ownershipAccessor;
        $this->publicationListProcessor = $publicationListProcessor;
        $this->tokenChange = $tokenChange;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param Closure                      $writer
     *
     * @throws InvalidListNameException
     */
    public function dispatchPublicationMessages(
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        Closure $writer
    ): void {
        $this->writer = $writer;
        $this->strategy = $strategy;

        $memberOwnership = null;

        while ($memberOwnership === null ||
            ($memberOwnership instanceof MemberOwnerships &&
            $memberOwnership->ownershipCollection()->isNotEmpty())
        ) {
            $memberOwnership = $this->ownershipAccessor
                ->getOwnershipsForMemberHavingScreenNameAndToken(
                    $strategy->onBehalfOfWhom(),
                    $token,
                    $memberOwnership
                );

            $ownerships = $this->guardAgainstInvalidListName(
                $memberOwnership->ownershipCollection(),
                $token
            );

            foreach ($ownerships->toArray() as $list) {
                try {
                    $publishedMessages = $this->publicationListProcessor
                        ->processPublicationList(
                            $list,
                            $memberOwnership->token(),
                            $strategy
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
    }

    /**
     * @param OwnershipCollection          $ownerships
     * @param TokenInterface               $token
     *
     * @return OwnershipCollection
     * @throws InvalidListNameException
     */
    private function guardAgainstInvalidListName(
        OwnershipCollection $ownerships,
        TokenInterface $token
    ): OwnershipCollection {
        if ($this->strategy->noListRestriction()) {
            return $ownerships;
        }

        $listRestriction = $this->strategy->forWhichList();

        // Try to find publication list by following the next cursor
        if ($this->targetListHasNotBeenFound($ownerships, $listRestriction) &&
            $ownerships->nextPage() !== -1) {
            return $this->findNextBatchOfListOwnerships($ownerships);
        }

        // Change tokens
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

    /**
     * @param OwnershipCollection $ownerships
     *
     * @param TokenInterface      $token
     *
     * @return OwnershipCollection
     */
    private function guardAgainstInvalidToken(
        OwnershipCollection $ownerships,
        TokenInterface $token
    ): OwnershipCollection
    {
        $this->tokenChange->replaceAccessToken(
            $token,
            $this->accessor
        );
        $ownerships->goBackToFirstPage();

        return $this->findNextBatchOfListOwnerships($ownerships);
    }

    /**
     * @param OwnershipCollection          $ownerships
     *
     * @return OwnershipCollection
     */
    private function findNextBatchOfListOwnerships(
        OwnershipCollection $ownerships
    ): OwnershipCollection {
        $previousCursor = -1;

        $eventRepository = $this->ownershipBatchCollectedEventRepository;

        if ($this->strategy->listRestriction()) {
            return $eventRepository->collectedOwnershipBatch(
                $this->accessor,
                [
                    $eventRepository::OPTION_SCREEN_NAME => $this->strategy->onBehalfOfWhom(),
                    $eventRepository::OPTION_NEXT_PAGE => $ownerships->nextPage()
                ]
            );
        }

        while ($this->targetListHasNotBeenFound(
            $ownerships,
            $this->strategy->forWhichList()
        )) {
            $ownerships = $eventRepository->collectedOwnershipBatch(
                $this->accessor,
                [
                    $eventRepository::OPTION_SCREEN_NAME => $this->strategy->onBehalfOfWhom(),
                    $eventRepository::OPTION_NEXT_PAGE => $ownerships->nextPage()
                ]
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
     * @param $ownerships
     *
     * @return array
     */
    private function mapOwnershipsLists(OwnershipCollection $ownerships): array
    {
        return array_map(
            fn(PublicationList $list) => $list->name(),
            $ownerships->toArray()
        );
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