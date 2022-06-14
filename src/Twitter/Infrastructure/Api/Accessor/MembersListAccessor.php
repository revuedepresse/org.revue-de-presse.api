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

    public function addMembersToList(array $members, string $listId): ?stdClass
    {
        if (count($members) > 100) {
            $partition = array_chunk($members, 100);
            $list = null;

            while (!empty($partition)) {
                $members = array_pop($partition);

                if (array_key_exists(0, $members) && is_numeric($members[0])) {
                    $endpoint = $this->getAddMembersToListEndpoint() .
                        "user_id=" . implode(',', $members) .
                        '&list_id=' . $listId;
                } else {
                    $endpoint = $this->getAddMembersToListEndpoint() .
                        "screen_name=" . implode(',', $members) .
                        '&list_id=' . $listId;
                }

                $list = $this->accessor->contactEndpoint($endpoint);
            }

            return $list;
        }

        if (array_key_exists(0, $members) && is_numeric($members[0])) {
            $endpoint = $this->getAddMembersToListEndpoint() .
                "user_id=" . implode(',', $members) .
                '&list_id=' . $listId;
        } else {
            $endpoint = $this->getAddMembersToListEndpoint() .
                "screen_name=" . implode(',', $members) .
                '&list_id=' . $listId;
        }

        return $this->accessor->contactEndpoint($endpoint);
    }

    private function getAddMembersToListEndpoint(): string
    {
        return implode([
            $this->accessor->getApiBaseUrl(),
            self::API_ENDPOINT_MEMBERS_LISTS,
            '.json',
            '?'
        ]);
    }
}