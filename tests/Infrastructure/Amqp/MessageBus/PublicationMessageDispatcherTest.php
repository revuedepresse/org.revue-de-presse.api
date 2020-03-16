<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Amqp\MessageBus;

use App\Api\Entity\Token;
use App\Api\Exception\UnavailableTokenException;
use App\Domain\Collection\PublicationStrategy;
use App\Domain\Resource\MemberOwnerships;
use App\Domain\Resource\OwnershipCollection;
use App\Infrastructure\Amqp\MessageBus\PublicationMessageDispatcher;
use App\Infrastructure\Twitter\Api\Accessor\OwnershipAccessor;
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
