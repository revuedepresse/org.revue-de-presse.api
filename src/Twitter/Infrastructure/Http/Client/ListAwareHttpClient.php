<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Client\ListAwareHttpClientInterface;
use App\Twitter\Domain\Http\Selector\AuthenticatedSelectorInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\ListBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\Exception\OverCapacityException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Http\AccessToken\TokenChangeInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberOwnerships;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;
use App\Twitter\Infrastructure\Http\Selector\ListsBatchSelector;
use Psr\Log\LoggerInterface;

class ListAwareHttpClient implements ListAwareHttpClientInterface
{
    use ListBatchCollectedEventRepositoryTrait;

    private HttpClientInterface $accessor;

    private TokenRepositoryInterface $tokenRepository;

    private TokenChangeInterface $tokenChange;

    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface      $accessor,
        TokenRepositoryInterface $tokenRepository,
        TokenChangeInterface     $tokenChange,
        LoggerInterface          $logger
    )
    {
        $this->accessor = $accessor;
        $this->tokenRepository = $tokenRepository;
        $this->tokenChange = $tokenChange;
        $this->logger = $logger;
    }

    public function getMemberOwnerships(ListSelectorInterface $selector): OwnershipCollectionInterface {
        $endpoint = $this->getOwnershipsEndpoint();
        $this->accessor->guardAgainstApiLimit($endpoint);

        $ownerships = $this->accessor->contactEndpoint(
            strtr(
                $endpoint,
                [
                    '{{ screenName }}' => $selector->screenName(),
                    '{{ reverse }}'    => true,
                    '{{ count }}'      => self::MAX_OWNERSHIPS,
                    '{{ cursor }}'     => $selector->cursor(),
                ]
            )
        );

        return OwnershipCollection::fromArray(
            $ownerships->lists,
            $ownerships->next_cursor
        );
    }

    private function getOwnershipsEndpoint(): string
    {
        return implode([
            $this->accessor->getApiBaseUrl(),
            self::API_ENDPOINT_OWNERSHIPS,
            '.json?reverse={{ reverse }}',
            '&screen_name={{ screenName }}' .
            '&count={{ count }}&cursor={{ cursor }}'
        ]);
    }

    /**
     * @throws OverCapacityException
     */
    public function getOwnershipsForMemberHavingScreenNameAndToken(
        AuthenticatedSelectorInterface $selector,
        MemberOwnerships $memberOwnership = null
    ): MemberOwnerships {
        $activeToken = $selector->authenticationToken();
        $totalUnfrozenToken = $this->tokenRepository->howManyUnfrozenTokenAreThere();
        $ownershipCollection = OwnershipCollection::fromArray([]);

        $correlationId = $selector->correlationId();

        $eventRepository = $this->listsBatchCollectedEventRepository;

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

                $ownershipCollection = $eventRepository->collectedListsBatch(
                    $this,
                    new ListsBatchSelector(
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