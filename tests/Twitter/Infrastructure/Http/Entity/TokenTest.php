<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Entity;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Http\Exception\UnexpectedAccessTokenProperties;
use PHPUnit\Framework\TestCase;

/**
 * @group builder
 */
class TokenTest extends TestCase
{
    public function getInvalidProps(): array
    {
        return [
            [[], Token::EXPECTED_TOKEN],
            [[TokenInterface::FIELD_TOKEN => null], Token::EXPECTED_TOKEN_TYPE],
            [[TokenInterface::FIELD_TOKEN => ''], Token::EXPECTED_TOKEN_LENGTH],
            [[TokenInterface::FIELD_TOKEN => 'tok'], Token::EXPECTED_SECRET],
            [[TokenInterface::FIELD_TOKEN => 'tok', TokenInterface::FIELD_SECRET => null], Token::EXPECTED_SECRET_TYPE],
            [[TokenInterface::FIELD_TOKEN => 'tok', TokenInterface::FIELD_SECRET => ''], Token::EXPECTED_SECRET_LENGTH],
        ];
    }

    /**
     * @test
     * @dataProvider getInvalidProps
     */
    public function it_throws_exceptions_when_an_access_token_is_built_from_unexpected_props(
        array $props,
        string $expectedExceptionMessage
    ) {
        try {
            Token::fromProps($props);
        } catch (UnexpectedAccessTokenProperties $e) {
            self::assertStringContainsString(
                $expectedExceptionMessage,
                $e->getMessage(),
                sprintf('Unexpected message for exception of type %s.', UnexpectedAccessTokenProperties::class)
            );

            return;
        }

        $this->fail(
            sprintf(<<<MESSAGE
                Unexpected props passed to %s static builder method
                must result in having some exception being thrown.
MESSAGE
                ,
                Token::class
            )
        );
    }

    /**
     * @test
     * @throws \App\Twitter\Infrastructure\Http\Exception\UnexpectedAccessTokenProperties
     */
    public function it_builds_an_access_token_from_expected_props()
    {
        self::assertInstanceOf(
            Token::class,
            Token::fromProps([
                TokenInterface::FIELD_TOKEN => 'tok',
                TokenInterface::FIELD_SECRET => 'secret'
            ])
        );
    }
}
