<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Exception\UnavailableTokenException;
use App\Twitter\Domain\Curation\PublicationStrategy;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;
use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Amqp\MessageBus\PublicationMessageDispatcher;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\OwnershipAccessor;
use DateInterval;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group publication_message_dispatcher
 */
class PublicationMessageDispatcherTest extends KernelTestCase
{
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

        $ownershipAccessor = $this->prophesize(OwnershipAccessor::class);
        $ownershipAccessor->getOwnershipsForMemberHavingScreenNameAndToken(
            Argument::any(),
            Argument::cetera()
        )->will(function () use (&$calls) {
            if ($calls === 0) {
                $calls++;

                UnavailableTokenException::throws(
                    function () {
                        return (new Token())
                            ->setOAuthToken('identifier-122131212')
                            ->setFrozenUntil(
                            (new \DateTime())->add(new DateInterval('PT1S'))
                        );
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

        /** @var PublicationStrategyInterface $publicationStrategy */
        $publicationStrategy->onBehalfOfWhom()->willReturn('test_member');
        $publicationStrategy->noListRestriction()->willReturn(true);
        $publicationStrategy->shouldFetchPublicationsFromCursor()->willReturn(-1);

        $dispatcher->dispatchPublicationMessages(
            $publicationStrategy->reveal(),
            (new Token()),
            function ($message) {}
        );

        self::assertEquals(
            2,
            $calls,
            'There should be two attempts to get member ownerships.'
        );
    }
}
