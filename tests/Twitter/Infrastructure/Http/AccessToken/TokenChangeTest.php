<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\AccessToken;

use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Infrastructure\Http\AccessToken\TokenChange;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Http\Exception\CanNotReplaceAccessTokenException;
use App\Twitter\Infrastructure\Http\Exception\InvalidSerializedTokenException;
use App\Twitter\Infrastructure\Http\Exception\UnavailableTokenException;
use App\Tests\Twitter\Infrastructure\Http\AccessToken\Builder\Repository\SimpleTokenRepositoryBuilder;
use App\Tests\Twitter\Infrastructure\Http\AccessToken\Builder\Repository\TokenRepositoryBuilder;
use App\Twitter\Infrastructure\Http\Client\HttpClient;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
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

    private HttpClientInterface $accessor;

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
        $this->accessor = new HttpClient(
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