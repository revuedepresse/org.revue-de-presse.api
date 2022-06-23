<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Accessor;

use App\Twitter\Domain\Api\Accessor\ListAccessorInterface;
use App\Twitter\Infrastructure\Api\Resource\FriendsList;
use App\Twitter\Domain\Api\Resource\ResourceList;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use App\Twitter\Domain\Api\Accessor\ApiAccessorInterface;
use Closure;
use Psr\Log\LoggerInterface;
use Throwable;

class FriendsListAccessor implements ListAccessorInterface
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

    public function getListAtCursor(
        ListSelectorInterface $selector,
        Closure $onFinishCollection = null
    ): ResourceList {
        try {
            $friendsListEndpoint = $this->getFriendsListEndpoint();

            $endpoint = strtr(
                $friendsListEndpoint,
                [
                    '{{ screen_name }}' => $selector->screenName(),
                    '{{ cursor }}' => $selector->cursor(),
                ]
            );

            $friendsList = (array) $this->accessor->contactEndpoint($endpoint);

            if (is_callable($onFinishCollection)) {
                $onFinishCollection($friendsList);
            }

            return FriendsList::fromResponse($friendsList);
        } catch (Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['screen_name' => $selector->screenName()]
            );

            throw $exception;
        }
    }

    private function getFriendsListEndpoint(): string {
        return implode([
            $this->accessor->getApiBaseUrl(),
            '/friends/list.json?',
            'count=200',
            '&skip_status=false',
            '&cursor={{ cursor }}',
            '&screen_name={{ screen_name }}'
        ]);
    }
}