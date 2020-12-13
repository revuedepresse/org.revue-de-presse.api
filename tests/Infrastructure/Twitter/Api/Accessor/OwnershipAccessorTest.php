<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Twitter\Api\Accessor;

use App\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Infrastructure\Api\Entity\Token;
use App\Infrastructure\Api\Exception\InvalidSerializedTokenException;
use App\Infrastructure\Collection\Repository\OwnershipBatchCollectedEventRepository;
use App\Infrastructure\Collection\Repository\OwnershipBatchCollectedEventRepositoryInterface;
use App\Tests\Builder\ApiAccessorBuilder;
use App\Tests\Builder\TokenChangeBuilder;
use App\Tests\Builder\TokenRepositoryBuilder;
use App\Infrastructure\Twitter\Api\Accessor\OwnershipAccessor;
use App\Domain\Resource\MemberOwnerships;
use App\Twitter\Exception\OverCapacityException;
use Exception;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @package App\Tests\Twitter\Api
 * @group   ownership
 */
class OwnershipAccessorTest extends KernelTestCase
{
    private const MEMBER_SCREEN_NAME = 'mcurie';
    private const TOKEN              = 'token';
    private const SECRET             = 'secret';
    private const REPLACEMENT_TOKEN  = 'replacement-token';
    private const REPLACEMENT_SECRET = 'replacement-secret';

    private OwnershipBatchCollectedEventRepositoryInterface $eventRepository;

    protected function setUp(): void
    {
        self::$kernel = self::bootKernel();
        self::$container = self::$kernel->getContainer();

        $this->eventRepository = self::$container->get('test.event_repository.ownership_batch_collected');
    }

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

        $ownershipAccessor->setOwnershipBatchCollectedEventRepository(
            $this->eventRepository
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

        $ownershipAccessor->setOwnershipBatchCollectedEventRepository(
            $this->eventRepository
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

        $ownershipAccessor->setOwnershipBatchCollectedEventRepository(
            $this->eventRepository
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