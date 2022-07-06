<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Infrastructure\Http\Resource\FriendsList;
use App\Twitter\Domain\Http\Resource\ResourceList;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use Closure;
use Psr\Log\LoggerInterface;
use Throwable;

class FriendsBatchAwareHttpClient implements CursorAwareHttpClientInterface
{
    private HttpClientInterface $accessor;
    private LoggerInterface     $logger;

    public function __construct(
        HttpClientInterface $accessor,
        LoggerInterface     $logger
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