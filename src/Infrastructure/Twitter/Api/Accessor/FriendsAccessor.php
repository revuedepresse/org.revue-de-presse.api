<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Infrastructure\Twitter\Api\Resource\FriendsList;
use App\Twitter\Api\ApiAccessorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class FriendsAccessor implements FriendsAccessorInterface
{
    private ApiAccessorInterface $accessor;
    private LoggerInterface $logger;

    public function __construct(
        ApiAccessorInterface $accessor,
        LoggerInterface $logger
    ) {
        $this->accessor = $accessor;
        $this->logger = $logger;
    }

    /**
     * @param string $screenName
     * @return FriendsList
     * @throws Throwable
     */
    public function getMemberFriendsListAtDefaultCursor(string $screenName): FriendsList {
        return $this->getMemberFriendsListAtCursor($screenName, '-1');
    }

    /**
     * @param string $screenName
     * @param string $cursor
     * @return FriendsList
     * @throws Throwable
     */
    public function getMemberFriendsListAtCursor(string $screenName, string $cursor): FriendsList {
        try {
            $friendsListEndpoint = $this->getFriendsListEndpoint();

            $endpoint = strtr(
                $friendsListEndpoint,
                [
                    '{{ screen_name }}' => $screenName,
                    '{{ cursor }}' => $cursor,
                ]
            );

            return FriendsList::fromResponse((array) $this->accessor->contactEndpoint($endpoint));
        } catch (Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['screen_name' => $screenName]
            );

            throw $exception;
        }
    }

    public function getMemberFriendsList(string $screenName): FriendsList
    {
        $friendsList = $this->getMemberFriendsListAtDefaultCursor($screenName);
        $nextFriendsList = $friendsList;

        while ($nextFriendsList->count() === 200 && $nextFriendsList->nextCursor() !== -1) {
            $nextFriendsList = $this->getMemberFriendsListAtCursor($screenName, $friendsList->nextCursor());
            $friendsList = FriendsList::fromResponse(array_merge(
                ['users' => array_merge($friendsList->getFriendsList(), $nextFriendsList->getFriendsList())],
                ['next_cursor_str' => $nextFriendsList->nextCursor()]
            ));
        }

        return $friendsList;
    }

    private function getFriendsListEndpoint(): string {
        return implode([
            $this->accessor->getApiBaseUrl(),
            '/friends/list.json?',
            'count=200',
            '&skip_status=true',
            '&cursor={{ cursor }}',
            '&screen_name={{ screen_name }}'
        ]);
    }
}