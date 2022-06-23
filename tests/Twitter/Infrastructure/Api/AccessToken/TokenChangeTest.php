<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\AccessToken;

use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Infrastructure\Api\AccessToken\TokenChange;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Infrastructure\Api\Exception\CanNotReplaceAccessTokenException;
use App\Twitter\Infrastructure\Api\Exception\InvalidSerializedTokenException;
use App\Twitter\Infrastructure\Api\Exception\UnavailableTokenException;
use App\Tests\Twitter\Infrastructure\Api\AccessToken\Builder\Repository\SimpleTokenRepositoryBuilder;
use App\Tests\Twitter\Infrastructure\Api\AccessToken\Builder\Repository\TokenRepositoryBuilder;
use App\Twitter\Infrastructure\Api\Accessor;
use App\Twitter\Domain\Api\Accessor\ApiAccessorInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @group api_access
 */
class TokenChangeTest extends TestCase
{
    private TokenRepositoryInterface $tokenRepository;

    private TokenInterface $excludedToken;

    private ApiAccessorInterface $accessor;

    /**
     * @test
     * @throws InvalidSerializedTokenException
     */
    public function it_does_not_replace_the_token_of_an_api_accessor(): void
    {
        // Arrange

        $expectedTokenReplacement = null;

        $this->willFindReplacementToken($expectedTokenReplacement);

        $tokenChange = new TokenChange(
            $this->tokenRepository,
            new NullLogger()
        );

        // Act

        try {
            $tokenChange->replaceAccessToken(
                $this->excludedToken,
                $this->accessor
            );
        }

        catch (Exception $exception) {

        // Assert

            self::assertInstanceOf(
                UnavailableTokenException::class,
                $exception
            );
        }
    }

    /**
     * @test
     */
    public function it_does_not_replace_the_token_of_an_api_accessor_when_there_is_no_token_left(): void
    {
        // Arrange

        $expectedTokenReplacement = null;

        $this->willNotFindReplacementToken();

        $tokenChange = new TokenChange(
            $this->tokenRepository,
            new NullLogger()
        );

        // Act

        try {
            $tokenChange->replaceAccessToken(
                $this->excludedToken,
                $this->accessor
            );
        }

        catch (Exception $exception) {

        // Assert

            self::assertInstanceOf(
                CanNotReplaceAccessTokenException::class,
                $exception
            );
        }
    }

    /**
     * @test
     */
    public function it_replaces_the_token_of_an_api_accessor(): void
    {
        // Arrange

        $expectedTokenReplacement = Token::fromArray(
            [
                'token'  => 'token-replacement',
                'secret' => 'secret2'
            ]
        );

        $this->willFindReplacementToken($expectedTokenReplacement);

        $tokenChange = new TokenChange(
            $this->tokenRepository,
            new NullLogger()
        );

        // Act

        $replacementToken = $tokenChange->replaceAccessToken(
            $this->excludedToken,
            $this->accessor
        );

        // Assert

        self::assertInstanceOf(TokenInterface::class, $replacementToken);
        self::assertEquals(
            $expectedTokenReplacement,
            $replacementToken,
            'A token should have been replaced.'
        );
    }

    protected function setUp(): void
    {
        $this->accessor = new Accessor(
            'consumer_key',
            'consumer_secret',
            'access_token',
            'access_token_secret',
            SimpleTokenRepositoryBuilder::build(),
            new NullLogger()
        );
    }

    /**
     * @param Token|null $replacementToken
     * @throws InvalidSerializedTokenException
     */
    private function willFindReplacementToken(
        Token $replacementToken = null
    ): void {
        $this->excludedToken = Token::fromArray(
            [
                'token'  => 'token',
                'secret' => 'secret'
            ]
        );

        $this->tokenRepository = TokenRepositoryBuilder::newTokenRepositoryBuilder()
            ->willReturnTheCountOfUnfrozenTokensExceptFrom(
                $this->excludedToken,
                1
            )
           ->willFindATokenOtherThan(
               $this->excludedToken,
               $replacementToken
           )
           ->build();
    }

    private function willNotFindReplacementToken(): void {
        $this->excludedToken = Token::fromArray(
            [
                'token'  => 'token',
                'secret' => 'secret'
            ]
        );

        $this->tokenRepository = TokenRepositoryBuilder::newTokenRepositoryBuilder()
           ->willReturnTheCountOfUnfrozenTokensExceptFrom(
                $this->excludedToken,
                0
           )
           ->build();
    }
}