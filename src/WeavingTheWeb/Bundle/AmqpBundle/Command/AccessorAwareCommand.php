<?php

namespace WeavingTheWeb\Bundle\AmqpBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface;

/**
 * Class AccessorAwareCommand
 * @package WeavingTheWeb\Bundle\AmqpBundle\Command
 */
abstract class AccessorAwareCommand extends ContainerAwareCommand
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
     * @param $oauthTokens
     */
    protected function setupAccessor($oauthTokens)
    {
        /**
         * @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessor
         */
        $this->accessor = $this->getContainer()->get('weaving_the_web_twitter.api_accessor');
        $this->accessor->setUserToken($oauthTokens['token']);
        $this->accessor->setUserSecret($oauthTokens['secret']);
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function getTokens(InputInterface $input)
    {
        if ($input->hasOption('oauth_secret') && !is_null($input->getOption('oauth_secret'))) {
            $secret = $input->getOption('oauth_secret');
        } else {
            $secret = $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_secret.default');
        }
        if ($input->hasOption('oauth_token') && !is_null($input->getOption('oauth_token'))) {
            $token = $input->getOption('oauth_token');
        } else {
            $token = $this->getContainer()->getParameter('weaving_the_web_twitter.oauth_token.default');
        }

        return [
            'secret' => $secret,
            'token' => $token,
        ];
    }

    protected function setUpLogger()
    {
        /**
         * @var \Psr\Log\LoggerInterface $logger
         */
        $this->logger = $this->getContainer()->get('logger');
    }
}