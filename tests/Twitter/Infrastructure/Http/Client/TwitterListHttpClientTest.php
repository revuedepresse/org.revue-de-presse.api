<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Client\Client;

use App\Tests\Twitter\Infrastructure\Http\AccessToken\Builder\Entity\TokenChangeBuilder;
use App\Tests\Twitter\Infrastructure\Http\AccessToken\Builder\Repository\TokenRepositoryBuilder;
use App\Tests\Twitter\Infrastructure\Http\Builder\Client\HttpClientBuilder;
use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Curation\Repository\ListsBatchCollectedEventRepositoryInterface;
use App\Twitter\Infrastructure\Http\Client\ListAwareHttpClient;
use App\Twitter\Infrastructure\Http\AccessToken\TokenChangeInterface;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Http\Exception\InvalidSerializedTokenException;
use App\Twitter\Infrastructure\Http\Selector\AuthenticatedSelector;
use App\Twitter\Infrastructure\Curation\Repository\ListsBatchCollectedEventRepository;
use App\Twitter\Infrastructure\Exception\OverCapacityException;
use Exception;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group   ownership
 */
class ListAwareHttpClientTest extends KernelTestCase
{
    private const MEMBER_SCREEN_NAME = 'mcurie';
    private const TOKEN              = 'token';
    private const SECRET             = 'secret';
    private const REPLACEMENT_TOKEN  = 'replacement-token';
    private const REPLACEMENT_SECRET = 'replacement-secret';

    private ListsBatchCollectedEventRepositoryInterface $eventRepository;

    protected function setUp(): void
    {
        self::$kernel = self::bootKernel();

        $this->eventRepository = static::getContainer()->get('test.'.ListsBatchCollectedEventRepository::class);
    }

    /**
     * @throws
     *
     * @test
     */
    public function it_gets_member_ownerships(): void
    {
        // Arrange

        $builder  = new HttpClientBuilder();
        $ownershipCollection = $builder->makeOwnershipCollection();
        $accessor = $builder->willGetOwnershipCollectionForMember($ownershipCollection)
            ->build();

        $ownershipAccessor = new ListAwareHttpClient(
            $accessor,
            $this->makeTokenRepository(1),
            $this->makeTokenChange(),
            new NullLogger()
        );

        $ownershipAccessor->setListsBatchCollectedEventRepository(
            $this->eventRepository
        );

        $activeToken = $this->getActiveToken();

        // Act

        $ownerships = $ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
            new AuthenticatedSelector(
                $activeToken,
                self::MEMBER_SCREEN_NAME
            )
        );

        // Assert

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

        $builder  = new HttpClientBuilder();
        $ownershipCollection = $builder->makeOwnershipCollection();
        $accessor = $builder->willGetOwnershipCollectionAfterThrowingForMember(
            $ownershipCollection,
        )->build();

        $ownershipAccessor = new ListAwareHttpClient(
            $accessor,
            $this->makeTokenRepository(2),
            $this->makeTokenChange(),
            new NullLogger()
        );

        $ownershipAccessor->setListsBatchCollectedEventRepository(
            $this->eventRepository
        );

        $activeToken = $this->getActiveToken();

        // Act

        $ownerships = $ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
            new AuthenticatedSelector(
                $activeToken,
                self::MEMBER_SCREEN_NAME
            )
        );

        // Assert

        $replacementToken = Token::fromArray(
            [
                'token' => self::REPLACEMENT_TOKEN,
                'secret' => self::REPLACEMENT_SECRET,
            ]
        );

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

        $builder  = new HttpClientBuilder();
        $accessor = $builder->willThrowWhenGettingOwnershipCollectionForMember()
            ->build();

        $ownershipAccessor = new ListAwareHttpClient(
            $accessor,
            $this->makeTokenRepository(1),
            $this->makeTokenChange(),
            new NullLogger()
        );

        $ownershipAccessor->setListsBatchCollectedEventRepository(
            $this->eventRepository
        );

        try {
            // Act

            $ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
                new AuthenticatedSelector(
                    $this->getActiveToken(),
                    self::MEMBER_SCREEN_NAME
                )
            );
        } catch (Exception $exception) {
            self::assertInstanceOf(
                OverCapacityException::class,
                $exception
            );

            return;
        }

        self::fail('There should be a exception raised');
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

    private function makeTokenChange(): TokenChangeInterface
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