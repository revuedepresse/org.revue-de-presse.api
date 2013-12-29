<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Twitter;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AmqpMessage;
use Psr\Log\LoggerInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException,
    WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;

/**
 * Class UserStatus
 * @package WeavingTheWeb\Bundle\AmqpBundle\Twitter
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserStatus implements ConsumerInterface
{
    /**
     * @var LoggerInterface
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
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Serializer\UserStatus $serializer
     */
    protected $serializer;

    /**
     * @param $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param $tokens
     * @throws \InvalidArgumentException
     */
    protected function setupCredentials($tokens)
    {
        if ((!array_key_exists('token', $tokens) || !array_key_exists('secret', $tokens)) && !array_key_exists('bearer', $tokens)) {
            throw new \InvalidArgumentException('Valid token and secret are required');
        } else {
            $this->serializer->setupAccessor($tokens);
        }
    }

    /**
     * @param AmqpMessage $message
     * @return bool
     */
    public function execute(AmqpMessage $message)
    {
        try {
            $options = $this->parseMessage($message);
        } catch (\Exception $exception) {
            return false;
        }

        $options = [
            'oauth' => $options['token'],
            'count' => 200,
            'screen_name' => $options['screen_name'],
        ];

        try {
            $success = $this->serializer->serialize($options, $greedy = true);
        } catch (UnavailableResourceException $unavailableResource) {
            if ($unavailableResource instanceof ProtectedAccountException) {
                /**
                 * This message should not be processed again for protected accounts
                 */
                $success = true;
                $this->logger->info($unavailableResource->getMessage());
            } else {
                $success = false;
                $this->logger->error($unavailableResource->getMessage());
            }
        }

        return $success;
    }

    /**
     * @param AmqpMessage $message
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function parseMessage(AmqpMessage $message)
    {
        $options = json_decode(unserialize($message->body), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Valid credentials are required');
        }
        $this->setupCredentials($options);

        return $options;
    }
}