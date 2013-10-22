<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Serializer;

/**
 * Class UserStatus
 * @package WeavingTheWeb\Bundle\TwitterBundle\Accessor
 */
class UserStatus
{
    /**
     * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
     */
    protected $feedReader;

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
     * @param \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
     */
    public function setFeedReader($feedReader)
    {
        $this->feedReader = $feedReader;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
     */
    public function setUserStreamRepository($userStreamRepository)
    {
        $this->userStreamRepository = $userStreamRepository;
    }

    /**
     * @param \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepository
     */
    public function setTokenRepository($tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * @param $oauthTokens
     */
    public function setupFeedReader($oauthTokens)
    {
        $this->feedReader->setUserToken($oauthTokens['token']);
        $this->feedReader->setUserSecret($oauthTokens['secret']);
    }

    public function serialize($options, $greedy = false, $discoverPastTweets = true)
    {
        $success = true;

        if ($this->isTwitterApiAvailable() && $this->remainingStatuses($options)) {
            $options = $this->updateExtremum($options, $discoverPastTweets);
            $saveStatuses = $this->saveStatuses($options);

            if (!is_null($saveStatuses) || $discoverPastTweets) {
                $discoverPastTweets = !is_null($saveStatuses) && $discoverPastTweets;

                if ($greedy) {
                    return $this->serialize($options, $greedy, $discoverPastTweets);
                }
            }
        } else {
            $success = false;
        }

        return $success;
    }

    /**
     * @return bool
     */
    protected function isTwitterApiAvailable()
    {
        $availableTwitterApi = false;

        if (!$this->tokenRepository->isTokenFrozen($this->feedReader->userToken, $this->logger)) {
            $apiRateLimitReached = $this->feedReader->isApiRateLimitReached('/statuses/user_timeline');
            if (is_integer($apiRateLimitReached) || $apiRateLimitReached) {
                $remainingStatuses = null;
                $this->tokenRepository->freezeToken($this->feedReader->userToken);
            } else {
                $availableTwitterApi = true;
            }
        }

        return $availableTwitterApi;
    }

    /**
     * @param $options
     * @param $logLevel
     * @param $discoverPastTweets
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
     * @param $count
     * @return bool
     */
    protected function remainingStatuses($options)
    {
        $count = $this->userStreamRepository->countStatuses($options['oauth'], $options['screen_name']);
        $user = $this->feedReader->showUser($options['screen_name']);
        if (!isset($user->statuses_count) || $user->protected) {
            $statusesCount = 0;
        } else {
            $statusesCount = $user->statuses_count;
        }

        // Twitter allows 3200 tweets to be retrieved at most from now for any given user
        return $count < max($statusesCount, 3200);
    }

    /**
     * @param $options
     */
    protected function saveStatuses($options)
    {
        $statuses = $this->feedReader->fetchTimelineStatuses($options);
        $success = null;

        if (is_array($statuses) && count($statuses) > 0) {
            try {
                $statuses = $this->userStreamRepository->saveStatuses($statuses, $options['oauth']);

                $statusesCount = count($statuses);
                if ($statusesCount > 0) {
                    $success = $statusesCount;
                    $this->logger->info('[' . $statusesCount . ' status(es) saved]');
                } else {
                    $this->logger->info('[nothing new for ' . $options['screen_name'] . ']');
                }
            } catch (\Exception $exception) {
                $success = false;
                $this->logger->error('[' . $exception->getMessage() . ']');
            }
        }

        return $success;
    }
}