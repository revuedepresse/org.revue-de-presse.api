<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Moderator;

use Psr\Log\LoggerInterface;

/**
 * Class ApiLimitModerator
 * @package WeavingTheWeb\Bundle\ApiBundle\Moderator
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ApiLimitModerator
{
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param $seconds
     * @param array $parameters
     */
    public function waitFor($seconds, array $parameters = [])
    {
        if (!is_null($this->logger)) {
            if ($seconds < 60) {
                $humanlyReadableWaitTime = $seconds . ' more seconds';
            } else {
                $humanlyReadableWaitTime = floor($seconds / 60) . ' more minutes';
            }

            $message = 'API limit has been reached for token "{{ token }}...' . '", ' .
                'operations are currently frozen (waiting for {{ wait_time }} )';
            ;
            $parameters['{{ wait_time }}'] = $humanlyReadableWaitTime;
            $this->logger->info(strtr($message, $parameters));
        }

        sleep($seconds);
    }
} 