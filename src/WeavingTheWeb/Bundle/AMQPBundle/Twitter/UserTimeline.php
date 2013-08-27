<?php

namespace WeavingTheWeb\Bundle\AMQPBundle\Twitter;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class UserTimeline
 * @package WeavingTheWeb\Bundle\AMQPBundle\Twitter
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserTimeline implements ConsumerInterface
{
    /**
     * @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
     */
    protected $feedReader;

    /**
     * @param \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Reader\FeedReader $feedReader
     */
    public function setFeedReader($feedReader)
    {
        $this->feedReader = $feedReader;
        $this->feedReader->activatePollingMode()
            ->disableStandardOutput();
    }

    /**
     * Sets credentials
     *
     * @param $input
     *
     * @throws \InvalidArgumentException
     */
    protected function setCredentials($credentials)
    {
        if (!array_key_exists('token', $credentials) || !array_key_exists('secret', $credentials)) {
            throw new \InvalidArgumentException('Valid token and secret are required');
        } else {
            $this->feedReader->setUserToken($credentials['token']);
            $this->feedReader->setUserSecret($credentials['secret']);
        }
    }

    /**
     * @param AMQPMessage $msg
     * @return bool
     */
    public function execute(AMQPMessage $msg)
    {
        try {
            $this->extractCredentials($msg);
        } catch (\Exception $exception) {
            return false;
        }
        $this->feedReader->getUserStream();
    }

    /**
     * @param AMQPMessage $msg
     * @throws \InvalidArgumentException
     */
    public function extractCredentials(AMQPMessage $msg)
    {
        $credentials = json_decode(unserialize($msg->body), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Valid credentials are required');
        }
        $this->setCredentials($credentials);
    }
}