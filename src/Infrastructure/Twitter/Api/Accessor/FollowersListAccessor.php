<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Infrastructure\Twitter\Api\Resource\FollowersList;
use App\Infrastructure\Twitter\Api\Resource\ResourceList;
use App\Twitter\Api\ApiAccessorInterface;
use Closure;
use Psr\Log\LoggerInterface;
use Throwable;

class FollowersListAccessor implements ListAccessorInterface
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
     * @param Closure|null $onFinishCollection
     * @return ResourceList
     * @throws Throwable
     */
    public function getListAtDefaultCursor(
        string $screenName,
        Closure $onFinishCollection = null
    ): ResourceList {
        return $this->getListAtCursor($screenName, '-1', $onFinishCollection);
    }

    /**
     * @param string $screenName
     * @param string $cursor
     * @param Closure|null $onFinishCollection
     * @return ResourceList
     * @throws Throwable
     */
    public function getListAtCursor(
        string $screenName,
        string $cursor,
        Closure $onFinishCollection = null
    ): ResourceList {
        try {
            $followersListEndpoint = $this->getFollowersListEndpoint();

            $endpoint = strtr(
                $followersListEndpoint,
                [
                    '{{ screen_name }}' => $screenName,
                    '{{ cursor }}' => $cursor,
                ]
            );

            $followersList = (array) $this->accessor->contactEndpoint($endpoint);

            if (is_callable($onFinishCollection)) {
                $onFinishCollection($followersList);
            }

            return FollowersList::fromResponse($followersList);
        } catch (Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['screen_name' => $screenName]
            );

            throw $exception;
        }
    }

    private function getFollowersListEndpoint(): string {
        return implode([
            $this->accessor->getApiBaseUrl(),
            '/followers/list.json?',
            'count=200',
            '&skip_status=false',
            '&cursor={{ cursor }}',
            '&screen_name={{ screen_name }}'
        ]);
    }
}