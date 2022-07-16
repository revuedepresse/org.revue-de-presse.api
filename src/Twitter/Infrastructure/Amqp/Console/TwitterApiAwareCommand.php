<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Console;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class TwitterApiAwareCommand extends Command
{
    use LoggerTrait;

    private const OPTION_OAUTH_SECRET = 'oauth_secret';
    private const OPTION_OAUTH_TOKEN  = 'oauth_token';

    protected HttpClientInterface $httpClient;

    protected string $defaultSecret;

    protected string $defaultToken;

    protected TokenRepositoryInterface $tokenRepository;

    protected InputInterface $input;

    protected OutputInterface $output;

    public function setHttpClient(HttpClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function setDefaultSecret(string $secret): void
    {
        $this->defaultSecret = $secret;
    }

    public function setDefaultToken(string $token): void
    {
        $this->defaultToken = $token;
    }

    public function setTokenRepository(TokenRepositoryInterface $tokenRepository): self
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

    protected function getAccessTokenSecret(): string
    {
        $secret = $this->defaultSecret;
        if ($this->hasOAuthSecretBeenPassedAsOption()) {
            $secret = $this->input->getOption(self::OPTION_OAUTH_SECRET);
        }

        return $secret;
    }

    protected function getAccessToken(): string
    {
        $token = $this->defaultToken;
        if ($this->hasOAuthTokenBeenPassedAsOption()) {
            $token = $this->input->getOption(self::OPTION_OAUTH_TOKEN);
        }

        return $token;
    }

    protected function getTokensFromInputOrFallback(): array
    {
        return [
            'token'  => $this->getAccessToken(),
            'secret' => $this->getAccessTokenSecret(),
        ];
    }

    private function hasOAuthSecretBeenPassedAsOption(): bool
    {
        return $this->input->hasOption(self::OPTION_OAUTH_SECRET)
            && $this->input->getOption(self::OPTION_OAUTH_SECRET) !== null;
    }

    private function hasOAuthTokenBeenPassedAsOption(): bool
    {
        return $this->input->hasOption(self::OPTION_OAUTH_TOKEN) &&
            $this->input->getOption(self::OPTION_OAUTH_TOKEN) !== null;
    }
}
