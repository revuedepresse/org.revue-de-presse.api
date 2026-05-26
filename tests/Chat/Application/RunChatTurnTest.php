<?php
declare(strict_types=1);

namespace App\Tests\Chat\Application;

use App\Chat\Application\CitationExtractor;
use App\Chat\Application\Port\ChatStreamer;
use App\Chat\Application\Port\PublicationRetriever;
use App\Chat\Application\PromptBuilder;
use App\Chat\Application\RunChatTurn;
use App\Chat\Application\Stream\SseEvent;
use App\Chat\Domain\Entity\Conversation;
use App\Chat\Domain\Entity\ConversationTurn;
use App\Chat\Domain\Query\QueryFilterExtractor;
use App\Chat\Domain\Query\QueryFilters;
use App\Chat\Domain\Repository\ConversationRepository;
use App\Chat\Domain\Repository\ConversationTurnRepository;
use App\Chat\Domain\Retrieval\RetrievedHit;
use App\Chat\Domain\Text\TextCleaner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\Policy\NoLimiter;
use Symfony\Component\RateLimiter\Policy\Rate;
use Symfony\Component\RateLimiter\Policy\TokenBucketLimiter;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Uid\Ulid;

final class RunChatTurnTest extends TestCase
{
    public function testHappyPathPersistsTwoTurnsAndEmitsTokensThenDone(): void
    {
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $hit = new RetrievedHit(
            publicationId: 'at://pub-1',
            screenName: 'lemonde.fr',
            snapshotDate: '2025-03-04',
            url: 'https://bsky.app/profile/lemonde.fr/post/abc',
            text: 'Donald Trump gèle l’aide',
            reposts: 73,
            likes: 169,
            distance: 0.12,
        );
        $retriever = new ArrayRetriever([$hit]);
        $streamer = new ScriptedStreamer(['L’aide militaire ', 'est gelée [1].'], provider: 'mistral');

        $use_case = $this->makeUseCase($conversations, $turns, $retriever, $streamer);
        $events = iterator_to_array($use_case('did:plc:user', 'Que dit Le Monde ?'));

        self::assertCount(3, $events);
        self::assertSame('token', $events[0]->type);
        self::assertSame('L’aide militaire ', $events[0]->data['delta']);
        self::assertSame('token', $events[1]->type);
        self::assertSame('done', $events[2]->type);
        self::assertCount(1, $events[2]->data['citations']);
        self::assertSame('at://pub-1', $events[2]->data['citations'][0]['publicationId']);

        // Persisted: 1 user + 1 assistant turn
        self::assertCount(2, $turns->appended);
        self::assertSame(ConversationTurn::ROLE_USER, $turns->appended[0]->role());
        self::assertSame(ConversationTurn::ROLE_ASSISTANT, $turns->appended[1]->role());
        self::assertSame('L’aide militaire est gelée [1].', $turns->appended[1]->content());
        self::assertSame(['at://pub-1'], $turns->appended[1]->citedPublicationIds());
        self::assertSame('mistral', $turns->appended[1]->provider());
        self::assertFalse($turns->appended[1]->truncated());
    }

    public function testPerUserRateLimitYieldsErrorAndPersistsNothing(): void
    {
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $use_case = $this->makeUseCase(
            $conversations,
            $turns,
            new ArrayRetriever([]),
            new ScriptedStreamer(['unused']),
            perUserLimiter: new AlwaysDenyLimiterFactory(),
        );

        $events = iterator_to_array($use_case('did:plc:user', 'Hello'));

        self::assertCount(1, $events);
        self::assertSame('error', $events[0]->type);
        self::assertSame('rate_limited_user', $events[0]->data['code']);
        self::assertSame([], $turns->appended);
    }

    public function testGlobalQuotaExhaustionYieldsErrorAfterUserLimiterAccepts(): void
    {
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $use_case = $this->makeUseCase(
            $conversations,
            $turns,
            new ArrayRetriever([]),
            new ScriptedStreamer(['unused']),
            globalLimiter: new AlwaysDenyLimiterFactory(),
        );

        $events = iterator_to_array($use_case('did:plc:user', 'Hello'));

        self::assertSame('rate_limited_global', $events[0]->data['code']);
        self::assertSame([], $turns->appended);
    }

    public function testProviderFailsBeforeFirstTokenEmitsProvidersExhausted(): void
    {
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $use_case = $this->makeUseCase(
            $conversations,
            $turns,
            new ArrayRetriever([]),
            new ScriptedStreamer([], failsImmediately: true),
        );

        $events = iterator_to_array($use_case('did:plc:user', 'Hello'));

        $kinds = array_map(fn (SseEvent $e): string => $e->type, $events);
        self::assertContains('error', $kinds);
        self::assertSame('providers_exhausted', end($events)->data['code']);
        // Only the user turn was persisted (no assistant turn since stream failed).
        self::assertCount(1, $turns->appended);
        self::assertSame(ConversationTurn::ROLE_USER, $turns->appended[0]->role());
    }

    public function testMidStreamFailureMarksAssistantTurnTruncated(): void
    {
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $use_case = $this->makeUseCase(
            $conversations,
            $turns,
            new ArrayRetriever([]),
            new ScriptedStreamer(['Une réponse '], failsAfterFirstToken: true, provider: 'mistral'),
        );

        $events = iterator_to_array($use_case('did:plc:user', 'Hello'));

        // At least one token was emitted before the failure.
        self::assertSame('token', $events[0]->type);
        // The assistant turn was persisted with truncated=true.
        $assistantTurns = array_values(array_filter(
            $turns->appended,
            fn (ConversationTurn $t): bool => $t->role() === ConversationTurn::ROLE_ASSISTANT,
        ));
        self::assertCount(1, $assistantTurns);
        self::assertTrue($assistantTurns[0]->truncated());
        self::assertSame('Une réponse ', $assistantTurns[0]->content());
    }

    public function testHitsAboveCosineThresholdAreDroppedFromCitations(): void
    {
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $closeHit = new RetrievedHit('at://pub-1', 'lemonde.fr', '2025-03-04', 'https://x', 'text-1', 1, 1, 0.2);
        $farHit = new RetrievedHit('at://pub-2', 'mediapart.fr', '2025-03-04', 'https://y', 'text-2', 1, 1, 0.9);
        $retriever = new ArrayRetriever([$closeHit, $farHit]);
        $streamer = new ScriptedStreamer(['Voir [1] et [2].'], provider: 'mistral');

        $use_case = $this->makeUseCase($conversations, $turns, $retriever, $streamer);
        $events = iterator_to_array($use_case('did:plc:user', 'q'));

        $done = end($events);
        // Only the close hit is referenceable; [2] points at the far hit which was dropped pre-prompt.
        self::assertCount(1, $done->data['citations']);
        self::assertSame('at://pub-1', $done->data['citations'][0]['publicationId']);
    }

    private function makeUseCase(
        ConversationRepository $conversations,
        ConversationTurnRepository $turns,
        PublicationRetriever $retriever,
        ChatStreamer $streamer,
        ?RateLimiterFactoryInterface $perUserLimiter = null,
        ?RateLimiterFactoryInterface $globalLimiter = null,
    ): RunChatTurn {
        return new RunChatTurn(
            chatPerUserLimiter: $perUserLimiter ?? $this->openLimiterFactory('per_user'),
            chatGlobalQuotaLimiter: $globalLimiter ?? $this->openLimiterFactory('global'),
            conversations: $conversations,
            turns: $turns,
            textCleaner: new TextCleaner(),
            filterExtractor: new QueryFilterExtractor(new \DateTimeImmutable('2026-05-26')),
            retriever: $retriever,
            promptBuilder: new PromptBuilder(),
            streamer: $streamer,
            citationExtractor: new CitationExtractor(),
        );
    }

    private function openLimiterFactory(string $id): RateLimiterFactory
    {
        // High limit + ridiculous rate = effectively unlimited in tests.
        return new RateLimiterFactory(
            ['id' => $id, 'policy' => 'token_bucket', 'limit' => 1_000_000, 'rate' => ['interval' => '1 second']],
            new InMemoryStorage(),
        );
    }
}

// ----------------------------------------------------------------------------
// In-memory fakes
// ----------------------------------------------------------------------------

/** @internal */
final class InMemoryConversationRepository implements ConversationRepository
{
    /** @var array<string, Conversation> */
    public array $byId = [];

    public function openOrCreateFor(string $blueskyDid, ?Ulid $existingId = null): Conversation
    {
        if ($existingId !== null && isset($this->byId[(string) $existingId])) {
            return $this->byId[(string) $existingId];
        }
        $conv = new Conversation($blueskyDid);
        $this->byId[(string) $conv->id()] = $conv;

        return $conv;
    }

    public function save(Conversation $conversation): void
    {
        $this->byId[(string) $conversation->id()] = $conversation;
    }
}

/** @internal */
final class InMemoryConversationTurnRepository implements ConversationTurnRepository
{
    /** @var list<ConversationTurn> */
    public array $appended = [];

    public function append(ConversationTurn $turn): void
    {
        $this->appended[] = $turn;
    }

    public function lastTurns(Conversation $conversation, int $limit): array
    {
        $same = array_values(array_filter(
            $this->appended,
            fn (ConversationTurn $t): bool => $t->conversation() === $conversation,
        ));

        return array_slice($same, -$limit);
    }

    public function findById(Ulid $id): ?ConversationTurn
    {
        foreach ($this->appended as $turn) {
            if ($turn->id()->equals($id)) {
                return $turn;
            }
        }

        return null;
    }
}

/** @internal */
final class ArrayRetriever implements PublicationRetriever
{
    /** @param list<RetrievedHit> $hits */
    public function __construct(private readonly array $hits) {}

    public function retrieve(string $cleanedQuery, int $k, QueryFilters $filters): array
    {
        return array_slice($this->hits, 0, $k);
    }
}

/** @internal */
final class ScriptedStreamer implements ChatStreamer
{
    /**
     * @param list<string> $tokens
     */
    public function __construct(
        private readonly array $tokens,
        private readonly bool $failsImmediately = false,
        private readonly bool $failsAfterFirstToken = false,
        private readonly ?string $provider = null,
        private readonly ?int $promptTokens = null,
        private readonly ?int $completionTokens = null,
    ) {}

    public function stream(array $messages): iterable
    {
        if ($this->failsImmediately) {
            throw new \RuntimeException('upstream is down');
        }
        $first = true;
        foreach ($this->tokens as $token) {
            yield $token;
            if ($first && $this->failsAfterFirstToken) {
                throw new \RuntimeException('mid-stream truncation');
            }
            $first = false;
        }
    }

    public function lastProvider(): ?string
    {
        return $this->provider;
    }

    public function lastPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function lastCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }
}

/** @internal Always rejects consume() — for rate-limit branch tests. */
final class AlwaysDenyLimiterFactory implements RateLimiterFactoryInterface
{
    public function create(?string $key = null): \Symfony\Component\RateLimiter\LimiterInterface
    {
        return new class implements \Symfony\Component\RateLimiter\LimiterInterface {
            public function consume(int $tokens = 1): RateLimit
            {
                return new RateLimit(0, new \DateTimeImmutable('+1 hour'), false, 30);
            }

            public function reserve(int $tokens = 1, ?float $maxTime = null): \Symfony\Component\RateLimiter\Reservation
            {
                throw new \BadMethodCallException('not used');
            }

            public function reset(): void
            {
            }
        };
    }
}
