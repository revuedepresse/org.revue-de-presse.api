<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Post;
use App\Chat\Application\RunChatTurn;
use App\Chat\Application\Stream\SseEvent;
use App\Chat\Infrastructure\ApiPlatform\Resource\ChatTurnResource;
use App\Chat\Infrastructure\ApiPlatform\State\RunChatTurnProcessor;
use App\Chat\Infrastructure\Security\BlueskyChatUser;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

final class RunChatTurnProcessorTest extends TestCase
{
    public function testEmptyUserMessageThrowsBadRequest(): void
    {
        $processor = new RunChatTurnProcessor(
            $this->stubRunChatTurn([]),
            $this->securityWithUser(new BlueskyChatUser('did:plc:abc')),
        );

        $this->expectException(BadRequestHttpException::class);
        $processor->process(new ChatTurnResource(userMessage: '   '), new Post());
    }

    public function testMissingChatUserThrowsUnauthorized(): void
    {
        $processor = new RunChatTurnProcessor(
            $this->stubRunChatTurn([]),
            $this->securityWithUser(null),
        );

        $this->expectException(UnauthorizedHttpException::class);
        $processor->process(new ChatTurnResource(userMessage: 'Hello'), new Post());
    }

    public function testNonBlueskyUserThrowsUnauthorized(): void
    {
        // Anonymous user implementing UserInterface but NOT BlueskyChatUser.
        $other = new class implements UserInterface {
            public function getRoles(): array { return ['ROLE_USER']; }
            public function getUserIdentifier(): string { return 'other'; }
            public function eraseCredentials(): void {}
        };

        $processor = new RunChatTurnProcessor(
            $this->stubRunChatTurn([]),
            $this->securityWithUser($other),
        );

        $this->expectException(UnauthorizedHttpException::class);
        $processor->process(new ChatTurnResource(userMessage: 'Hello'), new Post());
    }

    public function testHappyPathReturnsStreamedResponseWithSseHeaders(): void
    {
        $events = [
            SseEvent::token('Bonjour'),
            SseEvent::done('01j-conv', [['n' => 1, 'publicationId' => 'p1']]),
        ];
        $processor = new RunChatTurnProcessor(
            $this->stubRunChatTurn($events),
            $this->securityWithUser(new BlueskyChatUser('did:plc:abc')),
        );

        $response = $processor->process(
            new ChatTurnResource(userMessage: 'Hello'),
            new Post(),
        );

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertStringStartsWith('text/event-stream', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        self::assertStringContainsString('no-transform', (string) $response->headers->get('Cache-Control'));
        self::assertSame('keep-alive', $response->headers->get('Connection'));
        self::assertSame('no', $response->headers->get('X-Accel-Buffering'));

        // The response closure uses echo + ob_flush() + flush() to push tokens
        // out; ob_flush() drains our outer buffer mid-stream, so accumulate
        // chunks via output_callback instead of relying on a single ob_get_clean.
        $body = '';
        ob_start(static function (string $chunk) use (&$body): string {
            $body .= $chunk;

            return '';
        });
        try {
            $response->sendContent();
        } finally {
            ob_end_flush();
        }

        self::assertStringContainsString("event: token\n", $body);
        self::assertStringContainsString('"delta":"Bonjour"', $body);
        self::assertStringContainsString("event: done\n", $body);
        self::assertStringContainsString('"conversationId":"01j-conv"', $body);
    }

    /**
     * @param list<SseEvent> $events
     */
    private function stubRunChatTurn(array $events): RunChatTurn
    {
        // RunChatTurn::__invoke returns a Generator. We can't easily subclass
        // it because it's final, so we wrap it in an anonymous subclass that
        // bypasses the real constructor and overrides __invoke.
        return new class($events) extends RunChatTurn {
            /** @param list<SseEvent> $scriptedEvents */
            public function __construct(private readonly array $scriptedEvents)
            {
                // Intentionally skip parent constructor — this stub bypasses
                // all ports.
            }

            public function __invoke(
                string $blueskyDid,
                string $userMessage,
                ?string $conversationId = null,
            ): \Generator {
                foreach ($this->scriptedEvents as $event) {
                    yield $event;
                }
            }
        };
    }

    private function securityWithUser(?UserInterface $user): Security
    {
        // Security is @final; createStub bypasses that whereas anonymous-class
        // inheritance can't.
        $stub = $this->createStub(Security::class);
        $stub->method('getUser')->willReturn($user);

        return $stub;
    }
}
