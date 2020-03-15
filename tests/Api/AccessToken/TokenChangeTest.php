<?php
declare(strict_types=1);

namespace App\Tests\Api\AccessToken;

use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Api\AccessToken\TokenChange;
use App\Api\Entity\Token;
use App\Api\Entity\TokenInterface;
use App\Api\Exception\InvalidSerializedTokenException;
use App\Api\Exception\UnavailableTokenException;
use App\Tests\Builder\TokenRepositoryBuilder;
use App\Twitter\Api\Accessor;
use App\Twitter\Api\ApiAccessorInterface;
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
    public function it_can_not_change_the_token_of_an_api_accessor(): void
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
     * @throws InvalidSerializedTokenException
     */
    public function it_changes_the_token_of_an_api_accessor(): void
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
            new NullLogger()
        );
    }

    /**
     * @param Token|null $replacementToken
     *
     * @throws InvalidSerializedTokenException
     */
    private function willFindReplacementToken(Token $replacementToken = null): void
    {
        $this->excludedToken = Token::fromArray(
            [
                'token'  => 'token',
                'secret' => 'secret'
            ]
        );

        $this->tokenRepository = TokenRepositoryBuilder::newTokenRepositoryBuilder()
                                                       ->willFindATokenOtherThan(
                                                           $this->excludedToken,
                                                           $replacementToken
                                                       )->build();
    }
}