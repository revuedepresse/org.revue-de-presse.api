<?php

namespace App\Amqp\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package WeavingTheWeb\Bundle\AmqpBundle\Command
 */
abstract class AccessorAwareCommand extends Command
{
    /**
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessor
     */
    protected $accessor;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param $oauthTokens
     */
    protected function setupAccessor($oauthTokens)
    {
        /** @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessor */
        $this->accessor = $this->getContainer()->get('weaving_the_web_twitter.api_accessor');
        $this->accessor->setUserToken($oauthTokens['token']);
        $this->accessor->setUserSecret($oauthTokens['secret']);

        if (array_key_exists('consumer_token', $oauthTokens)) {
            $this->accessor->setConsumerKey($oauthTokens['consumer_token']);
            $this->accessor->setConsumerSecret($oauthTokens['consumer_secret']);
        }
    }

    /**
     * @return array
     */
    protected function getTokensFromInput()
    {
        if ($this->input->hasOption('oauth_secret') && !is_null($this->input->getOption('oauth_secret'))) {
            $secret = $this->input->getOption('oauth_secret');
        } else {
            $secret = $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_secret.default');
        }
        if ($this->input->hasOption('oauth_token') && !is_null($this->input->getOption('oauth_token'))) {
            $token = $this->input->getOption('oauth_token');
        } else {
            $token = $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_token.default');
        }

        return [
            'secret' => $secret,
            'token' => $token,
        ];
    }

    /**
     * @param string $token
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function findTokenOtherThan(string $token)
    {
        $tokenRepository = $this->getContainer()->get('weaving_the_web_twitter.repository.token');

        return $tokenRepository->findTokenOtherThan($token);
    }

    protected function setUpLogger()
    {
        /**
         * @var \Psr\Log\LoggerInterface $logger
         */
        $this->logger = $this->getContainer()->get('monolog.logger.status');
    }
}
