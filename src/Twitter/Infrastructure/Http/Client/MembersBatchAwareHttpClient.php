<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Client\MembersBatchAwareHttpClientInterface;
use App\Twitter\Domain\Http\Client\TwitterAPIEndpointsAwareInterface;
use stdClass;

class MembersBatchAwareHttpClient implements TwitterAPIEndpointsAwareInterface, MembersBatchAwareHttpClientInterface
{
    private HttpClientInterface $accessor;

    public function __construct(
        HttpClientInterface $accessor
    ) {
        $this->accessor = $accessor;
    }

    public function addMembersToList(array $members, string $listId)
    {
        $endpoint = strtr($this->getAddMembersToListEndpoint(), [':id' => $listId]);

        array_walk(
            $members,
            fn ($memberId) => $this->accessor->contactEndpoint("${endpoint}?user_id=${memberId}"),
        );
    }

    private function getAddMembersToListEndpoint(): string
    {
        return implode([
            $this->accessor->getApiBaseUrl($this->accessor::TWITTER_API_VERSION_2),
            self::API_ENDPOINT_MEMBERS_LISTS_VERSION_2,
        ]);
    }
}
