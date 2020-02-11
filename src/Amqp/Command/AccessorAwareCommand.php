<?php

namespace App\Amqp\Command;

use App\Api\Repository\TokenRepository;
use App\Twitter\Api\Accessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package App\Amqp\Command
 */
abstract class AccessorAwareCommand extends Command
{
    /**
     * @var Accessor $accessor
     */
    protected Accessor $accessor;

    /**
     * @var string
     */
    protected string $defaultSecret;

    /**
     * @var string
     */
    protected string $defaultToken;

    /**
     * @param string $secret
     */
    public function setDefaultSecret(string $secret)
    {
        $this->defaultSecret = $secret;
    }

    /**
     * @param string $token
     */
    public function setDefaultToken(string $token)
    {
        $this->defaultToken = $token;
    }

    /**
     * @param Accessor $accessor
     *
     * @return $this
     */
    public function setAccessor(Accessor $accessor): self
    {
        $this->accessor = $accessor;

        return $this;
    }

    /**
     * @var LoggerInterface $logger
     */
    protected LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @var TokenRepository $logger
     */
    protected TokenRepository $tokenRepository;

    /**
     * @param TokenRepository $tokenRepository
     *
     * @return $this
     */
    public function setTokenRepository(TokenRepository $tokenRepository): self
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

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
    protected function setOAuthTokens($oauthTokens)
    {
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
            $secret = $this->defaultSecret;
        }
        if ($this->input->hasOption('oauth_token') && !is_null($this->input->getOption('oauth_token'))) {
            $token = $this->input->getOption('oauth_token');
        } else {
            $token = $this->defaultToken;
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
        return $this->tokenRepository->findTokenOtherThan($token);
    }

    protected function setUpLogger()
    {
        // noop for backward compatibility
        // TODO remove all 5 calls to this method
    }
}
