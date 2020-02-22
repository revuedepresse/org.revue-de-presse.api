<?php

namespace App\Amqp\Command;

use App\Api\Entity\TokenInterface;
use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Api\Accessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package App\Amqp\Command
 */
abstract class AccessorAwareCommand extends Command
{
    private const OPTION_OAUTH_SECRET = 'oauth_secret';
    private const OPTION_OAUTH_TOKEN  = 'oauth_token';

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
     * @var LoggerInterface $logger
     */
    protected LoggerInterface $logger;

    protected TokenRepositoryInterface $tokenRepository;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

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
     * @param TokenRepositoryInterface $tokenRepository
     *
     * @return $this
     */
    public function setTokenRepository(TokenRepositoryInterface $tokenRepository): self
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

    /**
     * @return bool|string|string[]|null
     */
    protected function getOAuthSecret()
    {
        $secret = $this->defaultSecret;
        if ($this->hasOAuthSecretBeenPassedAsOption()) {
            $secret = $this->input->getOption(self::OPTION_OAUTH_SECRET);
        }

        return $secret;
    }

    /**
     * @return bool|string|string[]|null
     */
    protected function getOAuthToken()
    {
        $token = $this->defaultToken;
        if ($this->hasOAuthTokenBeenPassedAsOption()) {
            $token = $this->input->getOption(self::OPTION_OAUTH_TOKEN);
        }

        return $token;
    }

    /**
     * @return array
     */
    protected function getTokensFromInputOrFallback(): array
    {
        return [
            'token'  => $this->getOAuthToken(),
            'secret' => $this->getOAuthSecret(),
        ];
    }

    /**
     * @return bool
     */
    private function hasOAuthSecretBeenPassedAsOption(): bool
    {
        return $this->input->hasOption(self::OPTION_OAUTH_SECRET)
            && $this->input->getOption(self::OPTION_OAUTH_SECRET) !== null;
    }

    protected function setOAuthTokens(TokenInterface $token): void
    {
        $this->accessor->setUserToken($token->getOAuthToken());
        $this->accessor->setUserSecret($token->getOAuthSecret());

        if ($token->hasConsumerKey()) {
            $this->accessor->setConsumerKey($token->getConsumerKey());
            $this->accessor->setConsumerSecret($token->getConsumerKey());
        }
    }

    protected function setUpLogger()
    {
        // noop for backward compatibility
        // TODO remove all 5 calls to this method
    }

    /**
     * @return bool
     */
    private function hasOAuthTokenBeenPassedAsOption(): bool
    {
        return $this->input->hasOption(self::OPTION_OAUTH_TOKEN) &&
            $this->input->getOption(self::OPTION_OAUTH_TOKEN) !== null;
    }
}
