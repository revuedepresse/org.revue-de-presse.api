<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Amqp\MessageBus;

use App\Tests\Twitter\Infrastructure\Http\Builder\Entity\Token;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Infrastructure\Amqp\MessageBus\FetchTweetsAmqpMessagesDispatcher;
use App\Twitter\Infrastructure\Http\Client\ListAwareHttpClient;
use App\Twitter\Infrastructure\Http\Exception\UnavailableTokenException;
use App\Twitter\Infrastructure\Http\Selector\AuthenticatedSelector;
use App\Twitter\Infrastructure\Curation\CurationRuleset;
use App\Twitter\Infrastructure\Http\Resource\MemberOwnerships;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group fetch_tweets_message_dispatcher
 */
class DispatchAmqpMessagesToFetchTweetsTest extends KernelTestCase
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

        /** @var FetchTweetsAmqpMessagesDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('test.'.FetchTweetsAmqpMessagesDispatcher::class);

        $calls = 0;

        /** @var ListAwareHttpClient $ownershipAccessor */
        $ownershipAccessor = $this->prophesize(ListAwareHttpClient::class);

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
        $ruleset->isCurationSearchQueryBased()->willReturn(false);
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
