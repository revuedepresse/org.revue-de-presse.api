<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Client\MembersBatchAwareHttpClientInterface;
use App\Twitter\Domain\Http\Client\TwitterAPIEndpointsAwareInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use Psr\Log\LoggerInterface;

readonly class MembersBatchAwareHttpClient implements TwitterAPIEndpointsAwareInterface, MembersBatchAwareHttpClientInterface
{
    public function __construct(private HttpClientInterface $httpClient, private LoggerInterface $logger) {
    }

    public function addMembersToListSequentially(array $members, string $listId): array
    {
        $endpoint = strtr($this->getAddMembersToListEndpoint(), [':list_id' => $listId]);

        return array_filter(
            array_map(
                function ($memberId) use ($endpoint) {
                    $response = $this->httpClient->contactEndpoint("{$endpoint}?user_id={$memberId}");

                    if (
                        !property_exists( $response, 'data') ||
                        !property_exists($response->data, 'is_member') ||
                        $response->data->is_member !== true
                    ) {
                        return new MemberIdentity((string) $memberId, $memberId);
                    }

                    return false;
                },
                $members
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function addUpTo100MembersAtOnceToList(array $members, string $listId)
    {
        if (count($members) > 100) {
            $errorMessage = 'There are too many members to be added all at once';
            $this->logger->error($errorMessage);

            throw new \Exception($errorMessage);
        }

        $commaSeparatedMemberIdsList = implode(',', array_chunk($members, 100, true)[0]);
        $endpoint = $this->getAddMembersBatchToListEndpoint();
        $this->httpClient->contactEndpoint("{$endpoint}.json?list_id={$listId}&user_id={$commaSeparatedMemberIdsList}");
    }

    private function getAddMembersToListEndpoint(): string
    {
        return implode([
            $this->httpClient->getApiBaseUrl($this->httpClient::TWITTER_API_VERSION_2),
            self::API_ENDPOINT_MEMBERS_LISTS_VERSION_2,
        ]);
    }

    private function getAddMembersBatchToListEndpoint(): string
    {
        return implode([
            $this->httpClient->getApiBaseUrl(),
            self::API_ENDPOINT_MEMBERS_LISTS,
        ]);
    }
}