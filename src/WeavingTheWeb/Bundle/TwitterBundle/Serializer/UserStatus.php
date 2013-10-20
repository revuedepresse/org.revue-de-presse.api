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

    public function serialize($options, $greedyMode) {
        $updateMaxId = true;

        if (!$this->tokenRepository->isTokenFrozen($this->feedReader->userToken, $this->logger)) {
            while (($context = $this->updateContext($options, $updateMaxId)) && $context['granted']) {
                $saveStatuses = $this->persistStatuses($context['options']);

                if (!$greedyMode || (is_null($saveStatuses) && $updateMaxId === false)) {
                    break;
                } else {
                    $updateMaxId = false;
                }
            }

            $success = !array_key_exists('error_code', $context);
        } else {
            $success = false;
        }

        return $success;
    }

    /**
     * @param $options
     * @return array
     */
    protected function updateContext($options, $updateMaxId = true)
    {
        if ($this->tokenRepository->isTokenFrozen($this->feedReader->userToken, $this->logger)) {
            return ['granted' => false];
        }

        $apiRateLimitReached = $this->feedReader->isApiRateLimitReached('/statuses/user_timeline');

        if (is_integer($apiRateLimitReached) || $apiRateLimitReached) {
            $remainingStatuses = null;
            $this->tokenRepository->freezeToken($this->feedReader->userToken);

            return ['granted' => false, 'error_code' => $apiRateLimitReached];
        } else {
            $remainingStatuses = $this->remainingStatuses($options);
            $options = $this->updateExtremum($options, $updateMaxId);

            return ['granted' => $remainingStatuses, 'options' => $options];
        }
    }

    /**
     * @param $options
     * @param $logLevel
     * @param $updateMaxId
     * @return mixed
     */
    protected function updateExtremum($options, $updateMaxId = true)
    {
        if ($updateMaxId) {
            $option = 'max_id';
            $shift = -1;
            $updateMethod = 'findNextMaximum';
        } else {
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
    protected function persistStatuses($options)
    {
        $statuses = $this->feedReader->fetchTimelineStatuses($options);
        if (is_array($statuses) && count($statuses) > 0) {
            $savedStatuses = $this->userStreamRepository->saveStatuses($statuses, $options['oauth']);
            $this->logger->info('[' . count($savedStatuses) . ' status(es) saved]');

            if (count($savedStatuses) === 0) {
                return null;
            } else {
                return $savedStatuses;
            }
        } else {
            return null;
        }
    }
}