<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Client\MembersBatchAwareHttpClientInterface;
use App\Twitter\Domain\Http\Client\ApiEndpointsAwareInterface;
use Psr\Log\LoggerInterface;
use stdClass;

readonly class MembersBatchAwareHttpClient implements ApiEndpointsAwareInterface, MembersBatchAwareHttpClientInterface
{
    public function __construct(private HttpClientInterface $httpClient, private LoggerInterface $logger) {
    }

    public function addMembersToListSequentially(array $members, string $listId)
    {
        $endpoint = strtr($this->getAddMembersToListEndpoint(), [':list_id' => $listId]);

        array_walk(
            $members,
            fn ($memberId) => $this->httpClient->contactEndpoint("{$endpoint}?user_id={$memberId}"),
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