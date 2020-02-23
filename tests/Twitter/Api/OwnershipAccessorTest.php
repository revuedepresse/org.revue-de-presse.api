<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Api;

use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Api\Entity\Token;
use App\Api\Exception\InvalidSerializedTokenException;
use App\Tests\Builder\ApiAccessorBuilder;
use App\Tests\Builder\TokenChangeBuilder;
use App\Tests\Builder\TokenRepositoryBuilder;
use App\Twitter\Api\OwnershipAccessor;
use App\Domain\Resource\MemberOwnerships;
use App\Twitter\Exception\OverCapacityException;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @package App\Tests\Twitter\Api
 * @group   ownership
 */
class OwnershipAccessorTest extends TestCase
{
    private const MEMBER_SCREEN_NAME = 'mcurie';
    private const TOKEN              = 'token';
    private const SECRET             = 'secret';
    private const REPLACEMENT_TOKEN  = 'replacement-token';
    private const REPLACEMENT_SECRET = 'replacement-secret';

    /**
     * @throws
     *
     * @test
     */
    public function it_gets_member_ownerships(): void
    {
        // Arrange

        $builder  = new ApiAccessorBuilder();
        $ownershipCollection = $builder->makeOwnershipCollection();
        $accessor = $builder->willGetOwnershipCollectionForMember(
            $ownershipCollection,
            self::MEMBER_SCREEN_NAME
        )->build();

        $ownershipAccessor = new OwnershipAccessor(
            $accessor,
            $this->makeTokenRepository(1),
            $this->makeTokenChange(),
            new NullLogger()
        );

        $activeToken = $this->getActiveToken();

        // Act

        $ownerships = $ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
            self::MEMBER_SCREEN_NAME,
            $activeToken
        );

        // Assert

        self::assertInstanceOf(MemberOwnerships::class, $ownerships);
        self::assertEquals($activeToken, $ownerships->token());
        self::assertEquals($ownershipCollection, $ownerships->ownershipCollection());
    }

    /**
     * @throws
     *
     * @test
     */
    public function it_gets_member_ownerships_from_a_secondary_set_of_tokens(): void
    {
        // Arrange

        $builder  = new ApiAccessorBuilder();
        $ownershipCollection = $builder->makeOwnershipCollection();
        $accessor = $builder->willGetOwnershipCollectionAfterThrowingForMember(
            $ownershipCollection,
            self::MEMBER_SCREEN_NAME
        )->build();

        $ownershipAccessor = new OwnershipAccessor(
            $accessor,
            $this->makeTokenRepository(2),
            $this->makeTokenChange(),
            new NullLogger()
        );

        $activeToken = $this->getActiveToken();

        // Act

        $ownerships = $ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
            self::MEMBER_SCREEN_NAME,
            $activeToken
        );

        // Assert

        $replacementToken = Token::fromArray(
            [
                'token' => self::REPLACEMENT_TOKEN,
                'secret' => self::REPLACEMENT_SECRET,
            ]
        );

        self::assertInstanceOf(MemberOwnerships::class, $ownerships);
        self::assertEquals($replacementToken, $ownerships->token());
        self::assertEquals($ownershipCollection, $ownerships->ownershipCollection());
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_can_not_get_member_ownerships(): void
    {
        // Arrange

        $builder  = new ApiAccessorBuilder();
        $accessor = $builder->willThrowWhenGettingOwnershipCollectionForMember(
            self::MEMBER_SCREEN_NAME
        )->build();

        $ownershipAccessor = new OwnershipAccessor(
            $accessor,
            $this->makeTokenRepository(1),
            $this->makeTokenChange(),
            new NullLogger()
        );

        try {
            // Act

            $ownerships = $ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
                self::MEMBER_SCREEN_NAME,
                $activeToken = $this->getActiveToken()
            );
        } catch (Exception $exception) {
            self::assertInstanceOf(
                OverCapacityException::class,
                $exception
            );

            return;
        }

        $this->fail('There should be a exception raised');
    }

    /**
     * @return Token
     * @throws InvalidSerializedTokenException
     */
    private function getActiveToken(): Token
    {
        return Token::fromArray(
            [
                'token'  => self::TOKEN,
                'secret' => self::SECRET,
            ]
        );
    }

    /**
     * @return object
     * @throws InvalidSerializedTokenException
     */
    private function makeTokenChange(): object
    {
        $tokenChangeBuilder = new TokenChangeBuilder();
        $tokenChangeBuilder = $tokenChangeBuilder->willReplaceAccessToken(
            Token::fromArray(
                [
                    'token'  => self::REPLACEMENT_TOKEN,
                    'secret' => self::REPLACEMENT_SECRET,
                ]
            )
        );

        return $tokenChangeBuilder->build();
    }

    /**
     * @param int $totalUnfrozenTokens
     *
     * @return TokenRepositoryInterface
     */
    private function makeTokenRepository(int $totalUnfrozenTokens): TokenRepositoryInterface
    {
        $tokenRepositoryBuilder = new TokenRepositoryBuilder();
        $tokenRepositoryBuilder->willReturnTheCountOfUnfrozenTokens($totalUnfrozenTokens);

        return $tokenRepositoryBuilder->build();
    }
}