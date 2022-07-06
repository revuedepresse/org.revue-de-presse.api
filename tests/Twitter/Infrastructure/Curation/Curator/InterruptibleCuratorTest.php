<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Curation\Curator;

use App\Tests\Twitter\Infrastructure\Http\AccessToken\Builder\Repository\TokenRepositoryBuilder;
use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Curation\Curator\InterruptibleCuratorInterface;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Http\Throttling\RateLimitComplianceInterface;
use App\Twitter\Infrastructure\Collector\InterruptibleCurator;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group interruptible_collect_decider
 */
class InterruptibleCuratorTest extends KernelTestCase
{
    use ProphecyTrait;

    private InterruptibleCuratorInterface $curator;

    /**
     * @test
     *
     * @throws
     */
    public function it_can_not_delay_consumption(): void
    {
        $tokenRepository = $this->prophesize(TokenRepositoryInterface::class);
        $tokenRepository->findFirstFrozenToken()
            ->willReturn(null);
        $this->curator->setTokenRepository($tokenRepository->reveal());

        self::assertFalse($this->curator->delayingConsumption());
    }

    /**
     * @test
     *
     * @throws
     */
    public function it_delays_consumption(): void
    {
        $tokenRepositoryBuilder = TokenRepositoryBuilder::newTokenRepositoryBuilder();
        $tokenRepository = $tokenRepositoryBuilder->willFindFirstFrozenToken(
            (new Token())
                ->setAccessToken('1232108293-token')
                ->freeze()
        )->build();
        $this->curator->setTokenRepository($tokenRepository);

        $apiLimitModeratorProphecy = $this->prophesize(
            RateLimitComplianceInterface::class
        );
        $apiLimitModeratorProphecy->waitFor(
                Argument::type('int'),
                Argument::type('array')
            );
        $this->curator->setRateLimitCompliance($apiLimitModeratorProphecy->reveal());

        self::assertTrue($this->curator->delayingConsumption());
    }

    /**
     * @return object|void|null
     */
    protected function setUp(): void
    {
        self::$kernel    = self::bootKernel();

        $this->curator = static::getContainer()->get(InterruptibleCurator::class);
    }
}
