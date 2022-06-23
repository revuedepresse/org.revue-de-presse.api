<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Accessor;

use App\Twitter\Domain\Api\Accessor\ApiAccessorInterface;
use App\Twitter\Domain\Api\Accessor\MembersListAccessorInterface;
use App\Twitter\Domain\Api\Accessor\TwitterApiEndpointsAwareInterface;
use stdClass;

class MembersListAccessor implements TwitterApiEndpointsAwareInterface, MembersListAccessorInterface
{
    private ApiAccessorInterface $accessor;

    public function __construct(
        ApiAccessorInterface $accessor
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