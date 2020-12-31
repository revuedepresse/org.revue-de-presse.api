<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Accessor;

use App\Twitter\Domain\Api\Accessor\OwnershipAccessorInterface;
use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Api\Accessor\ApiAccessorInterface;
use App\Twitter\Domain\Api\Selector\AuthenticatedSelectorInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Api\AccessToken\TokenChangeInterface;
use App\Twitter\Infrastructure\DependencyInjection\Collection\OwnershipBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\Exception\OverCapacityException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdInterface;
use App\Twitter\Infrastructure\Api\Selector\MemberOwnershipsBatchSelector;
use Psr\Log\LoggerInterface;

class OwnershipAccessor implements OwnershipAccessorInterface
{
    use OwnershipBatchCollectedEventRepositoryTrait;

    private ApiAccessorInterface $accessor;

    private TokenRepositoryInterface $tokenRepository;

    private TokenChangeInterface $tokenChange;

    private LoggerInterface $logger;

    public function __construct(
        ApiAccessorInterface $accessor,
        TokenRepositoryInterface $tokenRepository,
        TokenChangeInterface $tokenChange,
        LoggerInterface $logger
    )
    {
        $this->accessor = $accessor;
        $this->tokenRepository = $tokenRepository;
        $this->tokenChange = $tokenChange;
        $this->logger = $logger;
    }

    public function getOwnershipsForMemberHavingScreenNameAndToken(
        AuthenticatedSelectorInterface $selector,
        MemberOwnerships $memberOwnership = null
    ): MemberOwnerships {
        $activeToken = $selector->authenticationToken();
        $totalUnfrozenToken = $this->tokenRepository->howManyUnfrozenTokenAreThere();
        $ownershipCollection = OwnershipCollection::fromArray([]);

        if ($selector instanceof CorrelationIdAwareInterface) {
            $correlationId = $selector->correlationId();
        } else {
            $correlationId = CorrelationIdInterface::generate();
        }

        $eventRepository = $this->ownershipBatchCollectedEventRepository;

        $nextPage = -1;

        // Leave the loop as soon as there are some ownerships to process
        // or there is no token left to access the Twitter API
        while (
            $totalUnfrozenToken > 0 &&
            $ownershipCollection->isEmpty()
            && $nextPage !== 0
        ) {
            try {
                if ($memberOwnership instanceof MemberOwnerships) {
                    $nextPage = $memberOwnership->ownershipCollection()->nextPage();
                }

                $ownershipCollection = $eventRepository->collectedOwnershipBatch(
                    $this->accessor,
                    new MemberOwnershipsBatchSelector(
                        $selector->screenName(),
                        (string) $nextPage,
                        $correlationId
                    )
                );
            } catch (UnavailableResourceException $exception) {
                $this->logger->info($exception->getMessage());
                if ($ownershipCollection->isEmpty()) {
                    $activeToken = $this->tokenChange->replaceAccessToken(
                        $selector->authenticationToken(),
                        $this->accessor
                    );
                }

                $totalUnfrozenToken--;
            }
        }

        if ($nextPage !== 0 && $ownershipCollection->isEmpty()) {
            throw new OverCapacityException(
                'Over capacity usage of all available tokens.'
            );
        }

        return MemberOwnerships::from($activeToken, $ownershipCollection);
    }
}