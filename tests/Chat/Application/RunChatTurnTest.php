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
use App\Chat\Domain\Retrieval\Retrieval;
use App\Chat\Domain\Retrieval\RetrievalNotice;
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
use Symfony\Component\Uid\Uuid;

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

    public function testCitationsExposeAllFieldsRequiredByPostCardAndAreTextCleaned(): void
    {
        // The Nuxt "sources citées" panel renders each citation through
        // BlueskyPostCard (same component as the homepage). The card needs
        // authorName / authorHandle / authorAvatarUrl / metrics — surface
        // them in the SSE `done` payload. Defensively re-clean text so a
        // legacy row with embedded \n or NBSP can't leak into the UI.
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $hit = new RetrievedHit(
            publicationId: 'at://pub-1',
            screenName: 'lemonde.fr',
            snapshotDate: '2025-03-04',
            url: 'https://bsky.app/profile/lemonde.fr/post/abc',
            // Deliberately dirty input: CR, LF, NBSP, BOM, runs of spaces.
            // After cleaning these must NOT appear in citations[0]['text'].
            text: "Donald Trump\u{00A0}gèle\nl'aide  militaire\u{FEFF}",
            reposts: 73,
            likes: 169,
            replies: 12,
            avatarUrl: 'https://cdn.bsky.app/img/avatar/lemonde.jpg',
            distance: 0.12,
        );
        $retriever = new ArrayRetriever([$hit]);
        $streamer = new ScriptedStreamer(['Voir [1].'], provider: 'mistral');

        $use_case = $this->makeUseCase($conversations, $turns, $retriever, $streamer);
        $events = iterator_to_array($use_case('did:plc:user', 'Que dit Le Monde ?'));

        $done = end($events);
        self::assertSame('done', $done->type);
        $citation = $done->data['citations'][0];

        self::assertSame('at://pub-1', $citation['publicationId']);
        self::assertSame('lemonde.fr', $citation['screenName']);
        self::assertSame('2025-03-04', $citation['snapshotDate']);
        self::assertSame('https://bsky.app/profile/lemonde.fr/post/abc', $citation['url']);
        self::assertSame('https://cdn.bsky.app/img/avatar/lemonde.jpg', $citation['avatarUrl']);
        self::assertSame(73, $citation['reposts']);
        self::assertSame(169, $citation['likes']);
        self::assertSame(12, $citation['replies']);

        // Text is fully cleaned: no \n, no NBSP, no BOM, single spaces.
        self::assertSame("Donald Trump gèle l'aide militaire", $citation['text']);
        self::assertStringNotContainsString("\n", $citation['text']);
        self::assertStringNotContainsString("\u{00A0}", $citation['text']);
        self::assertStringNotContainsString("\u{FEFF}", $citation['text']);
    }

    public function testRetrievalNoticeIsPropagatedIntoTheUserMessageGivenToTheStreamer(): void
    {
        // When the retriever returns DATE_FILTER_RELAXED, the streamer must
        // receive a user message containing the French "Note : ..." preface,
        // so Mistral can acknowledge the shortcoming in its reply.
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $hit = new RetrievedHit(
            publicationId: 'at://telerama-old',
            screenName: 'telerama.bsky.social',
            snapshotDate: '2026-01-15',
            url: 'https://bsky.app/profile/telerama.bsky.social/post/x',
            text: 'Vieux extrait',
            reposts: 1,
            likes: 1,
            replies: 0,
            avatarUrl: null,
            distance: 0.4,
        );
        $retriever = new ArrayRetriever([$hit], RetrievalNotice::DATE_FILTER_RELAXED);
        $capture = new MessageCapturingStreamer(['ok']);

        $use_case = $this->makeUseCase($conversations, $turns, $retriever, $capture);
        iterator_to_array($use_case('did:plc:user', 'Que dit Telerama cette semaine ?'));

        $userMessages = array_values(array_filter(
            $capture->lastMessages,
            static fn (array $m): bool => $m['role'] === 'user',
        ));
        self::assertNotSame([], $userMessages, 'streamer must have received a user message');
        $lastUser = end($userMessages);
        self::assertStringContainsString('Instruction prioritaire', $lastUser['content']);
        self::assertStringContainsString('Tu DOIS commencer ta réponse', $lastUser['content']);
    }

    public function testSummaryIntentSwapsSystemPromptAndRaisesMaxTokens(): void
    {
        // When the extractor flags the query as summary-intent, the
        // assistant turn must run with the synthesis system prompt AND
        // a raised max_tokens (so 3-6 paragraphs don't get truncated).
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $capture = new MessageCapturingStreamer(['ok']);

        $use_case = $this->makeUseCase($conversations, $turns, new ArrayRetriever([]), $capture);
        // "Résume la journée d'hier" triggers BOTH summary intent and date filter.
        iterator_to_array($use_case('did:plc:user', 'Résume la journée d hier'));

        $systemMessage = $capture->lastMessages[0] ?? null;
        self::assertNotNull($systemMessage);
        self::assertSame('system', $systemMessage['role']);
        // Pin a string that ONLY appears in SYSTEM_PROMPT_SUMMARY, not the default.
        self::assertStringContainsString('synthèse thématique', $systemMessage['content']);

        // max_tokens bumped for synthesis-length answers.
        self::assertArrayHasKey('max_tokens', $capture->lastOptions);
        self::assertGreaterThanOrEqual(600, $capture->lastOptions['max_tokens']);
    }

    public function testSummaryModeSurfacesAllRetrievedHitsAsCitationsEvenWithoutBracketMarkers(): void
    {
        // In summary mode, Mistral usually writes outlet attribution in
        // parentheses ("(Le Monde)") rather than [N] markers, so the
        // CitationExtractor finds zero matches. But the cards still need
        // to render so the user can verify the synthesis against the
        // actual extracts. Surface ALL retrieved hits as "consulted
        // sources" when in summary mode.
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $hits = [
            new RetrievedHit('at://pub-A', 'lemonde.fr', '2025-03-04', 'https://x', 'Texte A', 1, 1, 0.1),
            new RetrievedHit('at://pub-B', 'liberation.fr', '2025-03-04', 'https://y', 'Texte B', 2, 2, 0.2),
            new RetrievedHit('at://pub-C', 'mediapart.fr', '2025-03-04', 'https://z', 'Texte C', 3, 3, 0.3),
        ];
        // Mistral writes prose with NO [N] markers (synthesis style).
        $streamer = new ScriptedStreamer(['Selon Le Monde et Mediapart, …'], provider: 'mistral');

        $use_case = $this->makeUseCase(
            $conversations,
            $turns,
            new ArrayRetriever($hits),
            $streamer,
        );
        $events = iterator_to_array($use_case('did:plc:user', 'Résume la journée d hier'));

        $done = end($events);
        self::assertSame('done', $done->type);
        $citations = $done->data['citations'];
        // All 3 hits surface as cards, in the same order the retriever returned.
        self::assertCount(3, $citations);
        self::assertSame(['at://pub-A', 'at://pub-B', 'at://pub-C'], array_map(fn ($c) => $c['publicationId'], $citations));
    }

    public function testNonSummaryIntentKeepsClassicPromptAndUnbumpedMaxTokens(): void
    {
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $capture = new MessageCapturingStreamer(['ok']);

        $use_case = $this->makeUseCase($conversations, $turns, new ArrayRetriever([]), $capture);
        iterator_to_array($use_case('did:plc:user', 'Que dit Le Monde sur la réforme ?'));

        $systemMessage = $capture->lastMessages[0] ?? null;
        self::assertNotNull($systemMessage);
        // Classic prompt has "Cite chaque affirmation par son numéro entre crochets".
        self::assertStringContainsString('Cite chaque affirmation', $systemMessage['content']);
        self::assertArrayNotHasKey('max_tokens', $capture->lastOptions);
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

    public function testStreamerCompletingWithoutAnyTokenEmitsProvidersExhausted(): void
    {
        // Regression: the Symfony AI generic bridge yields zero deltas (no
        // exception) when the upstream returns 500 with no body — typical
        // of Ollama OOMing during model load. We must NOT persist an empty
        // assistant turn (it poisons the next turn via the `content: null`
        // serialization quirk) and must surface the failure to the client.
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $use_case = $this->makeUseCase(
            $conversations,
            $turns,
            new ArrayRetriever([]),
            new ScriptedStreamer([]), // ← yields nothing, throws nothing
        );

        $events = iterator_to_array($use_case('did:plc:user', 'Hello'));

        self::assertSame('error', end($events)->type);
        self::assertSame('providers_exhausted', end($events)->data['code']);
        // Only the user turn — no empty assistant turn must be persisted.
        self::assertCount(1, $turns->appended);
        self::assertSame(ConversationTurn::ROLE_USER, $turns->appended[0]->role());
    }

    public function testHistoryWithEmptyContentTurnIsDroppedBeforeStreaming(): void
    {
        // Regression: the Symfony AI AssistantMessageNormalizer serializes
        // empty assistant content as `content: null`, which Ollama rejects
        // with `invalid message content type: <nil>`. RunChatTurn must
        // never forward such turns to the streamer (defensive — empty
        // turns can leak in from partially-failed prior runs).
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $conversation = $conversations->openOrCreateFor('did:plc:user');

        // Pre-existing history: one legit user/assistant pair, then a
        // broken assistant turn with empty content from an earlier failure.
        $turns->append(new ConversationTurn($conversation, ConversationTurn::ROLE_USER, 'Previous question'));
        $turns->append(new ConversationTurn($conversation, ConversationTurn::ROLE_ASSISTANT, 'Previous answer'));
        $turns->append(new ConversationTurn($conversation, ConversationTurn::ROLE_ASSISTANT, '   '));

        $capture = new MessageCapturingStreamer(['ok']);
        $use_case = $this->makeUseCase($conversations, $turns, new ArrayRetriever([]), $capture);

        iterator_to_array($use_case('did:plc:user', 'New question', (string) $conversation->id()));

        $forwardedContents = array_map(static fn (array $m): string => $m['content'], $capture->lastMessages);
        self::assertNotContains('   ', $forwardedContents, 'empty-content turn must be filtered out');
        self::assertNotContains('', $forwardedContents);
    }

    public function testRetrieverFailureEmitsProvidersExhaustedAndDoesNotInvokeStreamer(): void
    {
        // Regression: when the query-embedding call (mistral-embed) rate-limits
        // or 5xx's, the exception used to bubble up through process() as a
        // raw 500. It must now surface as an SSE error and stop the turn.
        $conversations = new InMemoryConversationRepository();
        $turns = new InMemoryConversationTurnRepository();
        $retriever = new ThrowingRetriever(new \RuntimeException('Rate limit exceeded. Service tier capacity exceeded for this model.'));
        // The streamer must NOT be invoked once the retriever failed.
        $streamer = new ScriptedStreamer(['unreachable']);

        $use_case = $this->makeUseCase($conversations, $turns, $retriever, $streamer);
        $events = iterator_to_array($use_case('did:plc:user', 'Hello'));

        self::assertCount(1, $events);
        self::assertSame('error', $events[0]->type);
        self::assertSame('providers_exhausted', $events[0]->data['code']);

        // Only the user turn was persisted — no assistant turn since retrieval
        // failed before the streamer was reached.
        self::assertCount(1, $turns->appended);
        self::assertSame(ConversationTurn::ROLE_USER, $turns->appended[0]->role());
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

    public function openOrCreateFor(string $blueskyDid, ?Uuid $existingId = null): Conversation
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

    public function findById(Uuid $id): ?ConversationTurn
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
    public function __construct(
        private readonly array $hits,
        private readonly ?RetrievalNotice $notice = null,
    ) {}

    public function retrieve(string $cleanedQuery, int $k, QueryFilters $filters): Retrieval
    {
        return new Retrieval(hits: array_slice($this->hits, 0, $k), notice: $this->notice);
    }
}

/** @internal Retriever that always throws — models a Mistral embeddings outage. */
final class ThrowingRetriever implements PublicationRetriever
{
    public function __construct(private readonly \Throwable $error) {}

    public function retrieve(string $cleanedQuery, int $k, QueryFilters $filters): Retrieval
    {
        throw $this->error;
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

    public function stream(array $messages, array $options = []): iterable
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

/** @internal Records the messages array passed to stream() for assertions. */
final class MessageCapturingStreamer implements ChatStreamer
{
    /** @var list<array{role: string, content: string}> */
    public array $lastMessages = [];
    /** @var array{max_tokens?: int} */
    public array $lastOptions = [];

    /** @param list<string> $tokens */
    public function __construct(private readonly array $tokens) {}

    public function stream(array $messages, array $options = []): iterable
    {
        $this->lastMessages = $messages;
        $this->lastOptions = $options;
        foreach ($this->tokens as $token) {
            yield $token;
        }
    }

    public function lastProvider(): string { return 'capture'; }
    public function lastPromptTokens(): ?int { return null; }
    public function lastCompletionTokens(): ?int { return null; }
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
