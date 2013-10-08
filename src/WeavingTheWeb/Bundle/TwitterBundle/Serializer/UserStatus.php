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
     * @param $oauthTokens
     */
    public function setupFeedReader($oauthTokens)
    {
        $this->feedReader->setUserToken($oauthTokens['token']);
        $this->feedReader->setUserSecret($oauthTokens['secret']);
    }

    public function serialize($options, $logLevel = 'info', $greedyMode) {
        $context = $this->updateContext($options, $logLevel);

        while ($context['condition']) {
            $saveStatuses = $this->persistStatuses($context['options'], $logLevel);

            if (!$greedyMode || is_null($saveStatuses)) {
                break;
            }

            $context = $this->updateContext($context['options']);
        }
    }

    /**
     * @param $options
     * @return array
     */
    protected function updateContext($options, $logLevel = 'info')
    {
        $apiRateLimitReached = $this->isApiRateLimitReached($logLevel);
        $remainingStatuses = $this->remainingStatuses($options);
        $status = $this->userStreamRepository->findNextMaxStatus($options['oauth'], $options['screen_name']);

        if ((count($status) === 1) && array_key_exists('statusId', $status)) {
            $options['max_id'] = $status['statusId'] - 1;

            if ($logLevel === 'info') {
                $this->logger->info('[max id retrieved for "' . $options['screen_name'] . '"] ' . $options['max_id']);
            }
        }

        return [
            'condition' => !$apiRateLimitReached && $remainingStatuses,
            'options' => $options
        ];
    }

    /**
     * @return bool
     */
    protected function isApiRateLimitReached($logLevel = 'info')
    {
        $rateLimitStatus = $this->feedReader->fetchRateLimitStatus();

        if (isset($rateLimitStatus->errors) && is_array($rateLimitStatus->errors) && isset($rateLimitStatus->errors[0])) {
            $leastUpperBound = 1;
            $remainingCall = 0;
            $message = print_r($rateLimitStatus->errors[0], true);
        } else {
            $leastUpperBound = $rateLimitStatus->resources->statuses->{'/statuses/user_timeline'}->limit;
            $remainingCall = $rateLimitStatus->resources->statuses->{'/statuses/user_timeline'}->remaining;
            $message = '[rate limit status] ' . $remainingCall;
        }

        if ($logLevel == 'info') {
            $this->logger->info($message);
        }

        return $remainingCall < floor($leastUpperBound * (1/10));
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
    protected function persistStatuses($options, $logLevel = 'info')
    {
        $statuses = $this->feedReader->fetchTimelineStatuses($options);
        if (is_array($statuses) && count($statuses) > 0) {
            $savedStatuses = $this->userStreamRepository->saveStatuses($statuses, $options['oauth']);

            if ($logLevel === 'info') {
                $this->logger->info('[' . count($savedStatuses) . ' status(es) saved]');
            }

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