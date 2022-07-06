<?php

declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Security\Authorization\Builder;

use App\Twitter\Domain\Http\Security\Authorization\AuthorizeAccessInterface;
use App\Twitter\Infrastructure\Http\Security\Authorization\AccessToken;
use App\Twitter\Infrastructure\Http\Security\Authorization\RequestToken;
use App\Twitter\Infrastructure\Http\Security\Authorization\Verifier;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

class AuthorizeAccessBuilder extends TestCase
{
    use ProphecyTrait;

    public const REQUEST_TOKEN = 'request_token';
    public const REQUEST_SECRET = 'request_secret';
    public const PIN_CODE = '1234';
    public const AUTHORIZATION_URL = 'https://example.com/authorization';
    public const ACCESS_TOKEN = 'access_token';
    public const ACCESS_TOKEN_SECRET = 'access_token_secret';
    public const USER_ID = '1';
    public const SCREEN_NAME = 'jane';

    public function __construct()
    {
        $this->prophet = $this->getProphet();
    }

    public function prophet(): Prophet
    {
        return $this->prophet;
    }

    public static function build(): AuthorizeAccessInterface
    {
        $testCase = new self();

        /** @var AuthorizeAccessInterface|ObjectProphecy $prophecy */
        $prophecy = $testCase->prophet()->prophesize(AuthorizeAccessInterface::class);

        $requestToken = new RequestToken(self::REQUEST_TOKEN, self::REQUEST_SECRET);
        $prophecy->requestToken()
            ->willReturn($requestToken);

        $prophecy->authorizationUrl($requestToken)
            ->willReturn(self::AUTHORIZATION_URL);

        $prophecy->accessToken($requestToken, new Verifier((int) self::PIN_CODE))
            ->willReturn(
                new AccessToken(
                    self::ACCESS_TOKEN,
                    self::ACCESS_TOKEN_SECRET,
                    self::USER_ID,
                    self::SCREEN_NAME
                )
            );

        return $prophecy->reveal();
    }
}
