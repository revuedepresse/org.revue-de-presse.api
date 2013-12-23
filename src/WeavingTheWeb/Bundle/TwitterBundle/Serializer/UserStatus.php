<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Serializer;

/**
 * Class UserStatus
 * @package WeavingTheWeb\Bundle\TwitterBundle\Accessor
 */
class UserStatus
{
    /**
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessor
     */
    protected $accessor;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepository
     */
    protected $tokenRepository;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
     */
    protected $userStreamRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $userStreamRepository
     * @return $this
     */
    public function setUserStreamRepository($userStreamRepository)
    {
        $this->userStreamRepository = $userStreamRepository;

        return $this;
    }

    /**
     * @param $tokenRepository
     * @return $this
     */
    public function setTokenRepository($tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

    /**
     * @param $accessor
     * @return $this
     */
    public function setAccessor($accessor)
    {
        $this->accessor = $accessor;

        return $this;
    }

    /**
     * @param $oauthTokens
     * @return $this
     */
    public function setupAccessor($oauthTokens)
    {
        if (!array_key_exists('authentication_header', $oauthTokens)) {
            $this->accessor->setUserToken($oauthTokens['token']);
            $this->accessor->setUserSecret($oauthTokens['secret']);
        } else {
            $this->accessor->setAuthenticationHeader($oauthTokens['authentication_header']);
        }

        return $this;
    }

    public function serialize($options, $greedy = false, $discoverPastTweets = true)
    {
        if ($this->isTwitterApiAvailable() && ($remainingStatuses = $this->remainingStatuses($options))) {
            $options = $this->updateExtremum($options, $discoverPastTweets);

            try {
                $savedStatuses = $this->saveStatuses($options);
                $success = true;

                if (!is_null($savedStatuses) || $discoverPastTweets) {
                    $discoverPastTweets = !is_null($savedStatuses) && $discoverPastTweets;
                    if ($greedy) {
                        $success = $this->serialize($options, $greedy, $discoverPastTweets);
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error('[' . $exception->getMessage() . ']');
                $success = false;
            }

            return $success;
        } elseif (!isset($remainingStatuses)) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function isTwitterApiAvailable()
    {
        $availableTwitterApi = false;

        if (!$this->tokenRepository->isTokenFrozen($this->accessor->userToken, $this->logger)) {
            $apiRateLimitReached = $this->accessor->isApiRateLimitReached('/statuses/user_timeline');
            if (is_integer($apiRateLimitReached) || $apiRateLimitReached) {
                $remainingStatuses = null;
                $this->tokenRepository->freezeToken($this->accessor->userToken);
            } else {
                $availableTwitterApi = true;
            }
        }

        return $availableTwitterApi;
    }

    /**
     * @param $options
     * @param bool $discoverPastTweets
     * @return mixed
     */
    protected function updateExtremum($options, $discoverPastTweets = true)
    {
        if ($discoverPastTweets) {
            $option = 'max_id';
            $shift = -1;
            $updateMethod = 'findNextMaximum';
        } else {
            unset($options['max_id']);
            $option = 'since_id';
            $shift = 1;
            $updateMethod = 'findNextMininum';
        }

        $status = $this->userStreamRepository->$updateMethod($options['oauth'], $options['screen_name']);

        if ((count($status) === 1) && array_key_exists('statusId', $status)) {
            $options[$option] = $status['statusId'] + $shift;

            $this->logger->info(
                '[extremum (' . $option . ') retrieved for "' . $options['screen_name'] . '"] ' . $options[$option]
            );
        }

        return $options;
    }

    /**
     * @param $options
     * @return bool
     */
    protected function remainingStatuses($options)
    {
        $count = $this->userStreamRepository->countStatuses($options['oauth'], $options['screen_name']);
        $this->logger->info('[count of statuses already retrieved for user "'.$options['screen_name'].'"] '. $count);
        $user = $this->accessor->showUser($options['screen_name']);

        if (!isset($user->statuses_count) || $user->protected) {
            $statusesCount = 0;
        } else {
            $statusesCount = $user->statuses_count;
        }
        $this->logger->info('[total public statuses updated by user "'.$options['screen_name'].'"] '. $statusesCount);

        // Twitter allows 3200 tweets to be retrieved at most from now for any given user
        return $count < max($statusesCount, 3200);
    }

    /**
     * @param $options
     * @return int|null
     */
    protected function saveStatuses($options)
    {
        $statuses = $this->accessor->fetchTimelineStatuses($options);
        $success = null;

        if (is_array($statuses) && count($statuses) > 0) {
            $statuses = $this->userStreamRepository->saveStatuses($statuses, $options['oauth']);

            $statusesCount = count($statuses);
            if ($statusesCount > 0) {
                $success = $statusesCount;
                $this->logger->info('[' . $statusesCount . ' status(es) saved]');
            } else {
                $this->logger->info('[nothing new for ' . $options['screen_name'] . ']');
            }
        }

        return $success;
    }
}