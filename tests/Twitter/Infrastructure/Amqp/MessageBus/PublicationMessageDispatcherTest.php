<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Amqp\MessageBus;

use App\Tests\Twitter\Infrastructure\Api\Builder\Entity\Token;
use App\Twitter\Infrastructure\Api\Exception\UnavailableTokenException;
use App\Twitter\Infrastructure\Curation\PublicationStrategy;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Amqp\MessageBus\PublicationMessageDispatcher;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\Api\Accessor\OwnershipAccessor;
use App\Twitter\Infrastructure\Api\Selector\AuthenticatedSelector;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group publication_message_dispatcher
 */
class PublicationMessageDispatcherTest extends KernelTestCase
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
        self::$container = self::$kernel->getContainer();

        /** @var PublicationMessageDispatcher $dispatcher */
        $dispatcher = self::$container->get('test.'.PublicationMessageDispatcher::class);

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

        $publicationStrategy = $this->prophesize(
            PublicationStrategy::class
        );

        /** @var PublicationStrategyInterface|PublicationStrategy $publicationStrategy */
        $publicationStrategy->onBehalfOfWhom()->willReturn('test_member');
        $publicationStrategy->noListRestriction()->willReturn(true);
        $publicationStrategy->shouldFetchPublicationsFromCursor()->willReturn(-1);
        $publicationStrategy->correlationId()->willReturn(CorrelationId::generate());

        $dispatcher->dispatchPublicationMessages(
            $publicationStrategy->reveal(),
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
