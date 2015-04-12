<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Serializer;

use Symfony\Component\Translation\TranslatorInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;

/**
 * @package WeavingTheWeb\Bundle\TwitterBundle\Accessor
 */
class UserStatus
{
    const MAX_AVAILABLE_TWEETS_PER_USER = 3200;

    /**
     * @var \Symfony\Component\Translation\Translator $translator
     */
    public $translator;

    /**
     * @param $translator
     * @return $this
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessor
     */
    protected $accessor;

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
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository $userStreamRepository
     */
    protected $userStreamRepository;

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
     * @var \WeavingTheWeb\Bundle\ApiBundle\Moderator\ApiLimitModerator $moderator
     */
    protected $moderator;

    /**
     * @param $moderator
     * @return $this
     */
    public function setModerator($moderator)
    {
        $this->moderator = $moderator;

        return $this;
    }

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepository
     */
    protected $tokenRepository;

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
        } else {
            /**
             * Marks the serialization as successful if there are no remaining status
             */
            return isset($remainingStatuses) ?: false;
        }
    }

    /**
     * @return bool
     */
    protected function isTwitterApiAvailable()
    {
        $availableTwitterApi = false;

        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token
         */
        $token = $this->tokenRepository->refreshFreezeCondition($this->accessor->userToken, $this->logger);

        if (!$token->isFrozen()) {
            try {
                if (!$this->accessor->isApiRateLimitReached('/statuses/user_timeline')) {
                    $availableTwitterApi = true;
                }
            } catch (\Exception $exception) {
                if ($exception->getCode() === 52) {
                    $availableTwitterApi = true;
                } else {
                    $this->tokenRepository->freezeToken($this->accessor->userToken);
                }
            }
        }

        $token = $this->tokenRepository->findFirstUnfrozenToken();
        if (is_null($token)) {
            $now = new \DateTime;
            $this->moderator->waitFor(
                $token->getFrozenUntil()->getTimestamp() - $now->getTimestamp(),
                [
                    '{{ token }}' => substr($token->getOauthToken(), 0, '8'),
                ]
            );
        } else {
            $this->setupAccessor(['token' => $token->getOauthToken(), 'secret' => $token->getOauthTokenSecret()]);
            $availableTwitterApi = true;
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
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    protected function remainingStatuses($options)
    {
        $existingStatusCount = $this->userStreamRepository->countStatuses($options['oauth'], $options['screen_name']);
        $existingStatus = $this->translator->transChoice(
            'logs.info.status_existing',
            $existingStatusCount,
            [
                '{{ count }}' => $existingStatusCount,
                '{{ user }}' => $options['screen_name'],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $user = $this->accessor->showUser($options['screen_name']);

        if (is_object($user) && !isset($user->errors)) {
            if (!isset($user->statuses_count) || $user->protected) {
                $statusesCount = 0;
                if ($user->protected) {
                    $protectedAccount = $this->translator->trans(
                        'logs.info.account_protected',
                        ['{{ user }}' => $options['screen_name']],
                        'logs'
                    );
                    throw new ProtectedAccountException($protectedAccount);
                }

            } else {
                /**
                 * Twitter allows 3200 past tweets at most to be retrieved for any given user
                 */
                $statusesCount = max($user->statuses_count, self::MAX_AVAILABLE_TWEETS_PER_USER);
                $discoveredStatus = $this->translator->transChoice(
                    'logs.info.status_discovered',
                    $user->statuses_count, [
                        '{{ user }}' => $options['screen_name'],
                        '{{ count }}' => $statusesCount,
                    ],
                    'logs'
                );
                $this->logger->info($discoveredStatus);
            }

            return $existingStatusCount < $statusesCount;
        } else {
            if (!is_object($user)) {
                $errorCode = 0;
                $errorMessage = 'Unavailable user';
                $logLevel = 'info';
            } else {
                $errorCode = $user->errors[0]->code;
                $errorMessage = $user->errors[0]->message;
                $logLevel = 'error';
            }

            $this->logger->$logLevel($user->errors[0]->message);
            throw new UnavailableResourceException($errorMessage, $errorCode);
        }
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
                $savedTweets = $this->translator->transChoice(
                    'logs.info.status_saved',
                    $statusesCount, [
                        '{{ user }}' => $options['screen_name'],
                        '{{ count }}' => $statusesCount,
                    ],
                    'logs'
                );
                $this->logger->info($savedTweets);
            } else {
                $this->logger->info('[nothing new for ' . $options['screen_name'] . ']');
            }
        }

        return $success;
    }
}