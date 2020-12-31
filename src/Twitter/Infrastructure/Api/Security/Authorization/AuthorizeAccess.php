<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Security\Authorization;

use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;
use App\Twitter\Domain\Api\Security\Authorization\AccessTokenInterface;
use App\Twitter\Domain\Api\Security\Authorization\AuthorizeAccessInterface;
use App\Twitter\Domain\Api\Security\Authorization\RequestTokenInterface;
use App\Twitter\Domain\Api\Security\Authorization\VerifierInterface;
use Assert\Assert;

class AuthorizeAccess implements AuthorizeAccessInterface
{
    // @see https://developer.twitter.com/en/docs/authentication/api-reference/request_token
    private const OUT_OF_BAND = 'oob';

    private TwitterOAuth $connection;

    public function __construct(
        string $consumerKey,
        string $consumerSecret
    ) {
        $this->connection = new TwitterOAuth(
            $consumerKey,
            $consumerSecret
        );
    }

    public function requestToken(): RequestTokenInterface
    {
        $response = $this->connection->oauth('oauth/request_token', [
            'oauth_callback' => self::OUT_OF_BAND,
            'x_auth_access_type' => 'write',
        ]);

        Assert::lazy()
            ->that($response)
            ->tryAll()
                ->isArray()
                ->keyExists('oauth_token')
                ->keyExists('oauth_token_secret')
            ->verifyNow();

        return new RequestToken($response['oauth_token'], $response['oauth_token_secret']);
    }

    public function authorizationUrl(RequestTokenInterface $token): string
    {
        return $this->connection->url('oauth/authorize', [
            'oauth_token' => $token->token(),
        ]);
    }

    public function accessToken(RequestTokenInterface $token, VerifierInterface $verifier): AccessTokenInterface
    {
        try {
            $response = $this->connection->oauth('oauth/access_token', [
                'oauth_token' => $token->token(),
                'oauth_verifier' => (string) $verifier->verifier()
            ]);
        } catch (TwitterOAuthException $exception) {
            InvalidPinCodeException::throws();
        }

        Assert::lazy()
            ->that($response)
            ->tryAll()
                ->isArray()
                ->keyExists('oauth_token')
                ->keyExists('oauth_token_secret')
                ->keyExists('user_id')
                ->keyExists('screen_name')
            ->verifyNow();

        return new AccessToken(
            $response['oauth_token'],
            $response['oauth_token_secret'],
            $response['user_id'],
            $response['screen_name']
         );
    }
}