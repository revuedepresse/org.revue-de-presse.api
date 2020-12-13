<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Accessor;

use App\Twitter\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Infrastructure\Api\AccessToken\TokenChangeInterface;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\DependencyInjection\Collection\OwnershipBatchCollectedEventRepositoryTrait;
use App\Twitter\Domain\Api\ApiAccessorInterface;
use App\Twitter\Infrastructure\Exception\OverCapacityException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
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

    /**
     * @param string                $screenName
     * @param TokenInterface        $token
     *
     * @param MemberOwnerships|null $memberOwnership
     *
     * @return MemberOwnerships
     * @throws OverCapacityException
     */
    public function getOwnershipsForMemberHavingScreenNameAndToken(
        string $screenName,
        TokenInterface $token,
        MemberOwnerships $memberOwnership = null
    ): MemberOwnerships {
        $activeToken = $token;
        $totalUnfrozenToken = $this->tokenRepository->howManyUnfrozenTokenAreThere();
        $ownershipCollection = OwnershipCollection::fromArray([]);

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
                    [
                        $eventRepository::OPTION_SCREEN_NAME => $screenName,
                        $eventRepository::OPTION_NEXT_PAGE => $nextPage
                    ]
                );
            } catch (UnavailableResourceException $exception) {
                $this->logger->info($exception->getMessage());
                if ($ownershipCollection->isEmpty()) {
                    $activeToken = $this->tokenChange->replaceAccessToken(
                        $token,
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