<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Collector;

use App\Twitter\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Throttling\ApiLimitModeratorInterface;
use App\Tests\Twitter\Infrastructure\Api\AccessToken\Builder\Repository\TokenRepositoryBuilder;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group interruptible_collect_decider
 */
class InterruptibleCollectDeciderTest extends KernelTestCase
{
    private InterruptibleCollectDeciderInterface $decider;

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
        $this->decider->setTokenRepository($tokenRepository->reveal());

        self::assertFalse($this->decider->delayingConsumption());
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
                ->setOAuthToken('1232108293-token')
                ->setFrozenUntil(
                    new \DateTime(
                        'now',
                        new \DateTimeZone('UTC')
                    )
                )
        )->build();
        $this->decider->setTokenRepository($tokenRepository);

        $apiLimitModeratorProphecy = $this->prophesize(
            ApiLimitModeratorInterface::class
        );
        $apiLimitModeratorProphecy->waitFor(
                Argument::type('int'),
                Argument::type('array')
            );
        $this->decider->setModerator($apiLimitModeratorProphecy->reveal());

        self::assertTrue($this->decider->delayingConsumption());
    }

    /**
     * @return object|void|null
     */
    protected function setUp(): void
    {
        self::$kernel    = self::bootKernel();
        self::$container = self::$kernel->getContainer();

        $this->decider = self::$container->get(InterruptibleCollectDecider::class);
    }
}
