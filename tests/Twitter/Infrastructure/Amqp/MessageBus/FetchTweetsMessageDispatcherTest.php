<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Amqp\MessageBus;

use App\Tests\Twitter\Infrastructure\Api\Builder\Entity\Token;
use App\Twitter\Infrastructure\Api\Exception\UnavailableTokenException;
use App\Twitter\Infrastructure\Curation\CurationRuleset;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Amqp\MessageBus\FetchTweetsMessageDispatcher;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\Api\Accessor\OwnershipAccessor;
use App\Twitter\Infrastructure\Api\Selector\AuthenticatedSelector;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group fetch_tweets_message_dispatcher
 */
class FetchTweetsMessageDispatcherTest extends KernelTestCase
{
    use ProphecyTrait;

    /**
     * @throws
     *
     * @test
     */
    public function it_moderates_api_calls_on_unavailable_token_exception(): void
    {
        self::$kernel = self::bootKernel();

        /** @var FetchTweetsMessageDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('test.'.FetchTweetsMessageDispatcher::class);

        $calls = 0;

        /** @var OwnershipAccessor $ownershipAccessor */
        $ownershipAccessor = $this->prophesize(OwnershipAccessor::class);
        $ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
            Argument::type(AuthenticatedSelector::class),
            Argument::cetera()
        )->will(function () use (&$calls) {
            if ($calls === 0) {
                $calls++;

                UnavailableTokenException::throws(
                    function () {
                        return (new Token())
                            ->setAccessToken('identifier-122131212')
                            ->freeze();
                    }
                );
            }

            if ($calls > 0) {
                $calls++;
                return MemberOwnerships::from(
                    new Token(),
                    OwnershipCollection::fromArray([]),
                );
            }
        });

        $dispatcher->setOwnershipAccessor($ownershipAccessor->reveal());

        $ruleset = $this->prophesize(
            CurationRuleset::class
        );

        /** @var CurationRulesetInterface|CurationRuleset $ruleset */
        $ruleset->whoseListSubscriptionsAreCurated()->willReturn('test_member');
        $ruleset->isSingleListFilterInactive()->willReturn(true);
        $ruleset->isCurationCursorActive()->willReturn(-1);
        $ruleset->correlationId()->willReturn(CorrelationId::generate());

        $dispatcher->dispatchFetchTweetsMessages(
            $ruleset->reveal(),
            (new Token())->unfreeze(),
            function ($message) {}
        );

        self::assertEquals(
            2,
            $calls,
            'There should be two attempts to get member ownerships.'
        );
    }
}
