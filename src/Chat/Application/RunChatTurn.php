<?php
declare(strict_types=1);

namespace App\Chat\Application;

use App\Chat\Application\Port\ChatStreamer;
use App\Chat\Application\Port\PublicationRetriever;
use App\Chat\Application\Stream\SseEvent;
use App\Chat\Domain\Entity\ConversationTurn;
use App\Chat\Domain\Query\QueryFilterExtractor;
use App\Chat\Domain\Repository\ConversationRepository;
use App\Chat\Domain\Repository\ConversationTurnRepository;
use App\Chat\Domain\Text\TextCleaner;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * One chat turn end-to-end: rate-limit, persist user turn, retrieve, stream,
 * persist assistant turn. The processor adapts the generator to SSE.
 */
class RunChatTurn
{
    private const HISTORY_TURN_LIMIT = 6;
    private const RETRIEVAL_K = 8;
    private const COSINE_DISTANCE_CUTOFF = 0.6;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly RateLimiterFactoryInterface $chatPerUserLimiter,
        private readonly RateLimiterFactoryInterface $chatGlobalQuotaLimiter,
        private readonly ConversationRepository $conversations,
        private readonly ConversationTurnRepository $turns,
        private readonly TextCleaner $textCleaner,
        private readonly QueryFilterExtractor $filterExtractor,
        private readonly PublicationRetriever $retriever,
        private readonly PromptBuilder $promptBuilder,
        private readonly ChatStreamer $streamer,
        private readonly CitationExtractor $citationExtractor,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @return \Generator<int, SseEvent>
     */
    public function __invoke(
        string $blueskyDid,
        string $userMessage,
        ?string $conversationId = null,
    ): \Generator {
        $perUser = $this->chatPerUserLimiter->create($blueskyDid)->consume();
        if (!$perUser->isAccepted()) {
            yield SseEvent::error('rate_limited_user', ['retryAfter' => 60]);

            return;
        }
        $global = $this->chatGlobalQuotaLimiter->create('global')->consume();
        if (!$global->isAccepted()) {
            yield SseEvent::error('rate_limited_global', ['retryAfter' => 'tomorrow']);

            return;
        }

        $existing = $conversationId !== null ? Uuid::fromString($conversationId) : null;
        $conversation = $this->conversations->openOrCreateFor($blueskyDid, $existing);

        $cleaned = $this->textCleaner->clean($userMessage);

        $userTurn = new ConversationTurn(
            conversation: $conversation,
            role: ConversationTurn::ROLE_USER,
            content: $cleaned,
        );
        $this->turns->append($userTurn);

        $this->logger->info('chat.turn.started', [
            'did' => $blueskyDid,
            'conversation_id' => (string) $conversation->id(),
            'user_msg_len' => strlen($cleaned),
        ]);

        // Filter extraction + retrieval embed the query via the vectorizer
        // (currently mistral-embed, no Gemini failover — see ai.yaml). When
        // Mistral rate-limits or 5xx's, treat it like the streamer failing
        // before the first token: emit providers_exhausted instead of letting
        // the exception bubble up as a 500.
        try {
            $filters = $this->filterExtractor->extract($cleaned);
            $retrieval = $this->retriever->retrieve($cleaned, self::RETRIEVAL_K, $filters);
        } catch (\Throwable $e) {
            $this->logger->error('chat.retrieval.failed', [
                'error' => $e::class,
                'message' => $e->getMessage(),
            ]);
            yield SseEvent::error('providers_exhausted');

            return;
        }
        $notice = $retrieval->notice;
        $hits = array_values(array_filter(
            $retrieval->hits,
            static fn ($h): bool => $h->distance <= self::COSINE_DISTANCE_CUTOFF,
        ));

        $this->logger->info('chat.turn.retrieved', [
            'hits' => count($hits),
            'distance_min' => $hits[0]->distance ?? null,
            'distance_max' => $hits !== [] ? $hits[count($hits) - 1]->distance : null,
        ]);

        $messages = [['role' => 'system', 'content' => $this->promptBuilder->systemPrompt()]];
        foreach ($this->turns->lastTurns($conversation, self::HISTORY_TURN_LIMIT) as $past) {
            if ($past->id()->equals($userTurn->id())) {
                continue;
            }
            // Skip empty-content turns. The Symfony AI AssistantMessageNormalizer
            // serializes empty text as `content: null` for OpenAI tool-call
            // compatibility — Ollama (and stricter OpenAI-compat servers)
            // reject those with `invalid message content type: <nil>`. Empty
            // turns can appear in the DB after partially-failed earlier runs.
            if (trim($past->content()) === '') {
                continue;
            }
            $messages[] = ['role' => $past->role(), 'content' => $past->content()];
        }
        $messages[] = ['role' => 'user', 'content' => $this->promptBuilder->buildUserMessage($cleaned, $hits, $notice)];

        $accumulator = '';
        $firstTokenSeen = false;
        $truncated = false;

        try {
            foreach ($this->streamer->stream($messages) as $delta) {
                $accumulator .= $delta;
                $firstTokenSeen = true;
                yield SseEvent::token($delta);
            }
        } catch (\Throwable $e) {
            if ($firstTokenSeen) {
                $truncated = true;
                $this->logger->warning('chat.stream.truncated', [
                    'provider' => $this->streamer->lastProvider(),
                    'error' => $e::class,
                ]);
            } else {
                $this->logger->error('chat.stream.failed', [
                    'provider' => $this->streamer->lastProvider(),
                    'error' => $e::class,
                    'message' => $e->getMessage(),
                ]);
                yield SseEvent::error('providers_exhausted');

                return;
            }
        }

        // The Symfony AI generic bridge swallows non-2xx upstream responses
        // (e.g. Ollama OOMing on model load returns 500 with no body) and
        // yields zero deltas instead of raising. Without this guard, we'd
        // persist an empty assistant turn — which then breaks the *next*
        // chat turn because the bridge serializes empty assistant content
        // as `content: null` and Ollama rejects it with
        // `invalid message content type: <nil>`.
        if (!$firstTokenSeen) {
            $this->logger->error('chat.stream.empty', [
                'provider' => $this->streamer->lastProvider(),
            ]);
            yield SseEvent::error('providers_exhausted');

            return;
        }

        $citedIds = $this->citationExtractor->extract($accumulator, $hits);

        $assistantTurn = new ConversationTurn(
            conversation: $conversation,
            role: ConversationTurn::ROLE_ASSISTANT,
            content: $accumulator,
            citedPublicationIds: $citedIds === [] ? null : $citedIds,
            provider: $this->streamer->lastProvider(),
            promptTokens: $this->streamer->lastPromptTokens(),
            completionTokens: $this->streamer->lastCompletionTokens(),
            truncated: $truncated,
        );
        $this->turns->append($assistantTurn);

        $this->logger->info('chat.turn.streamed', [
            'provider' => $assistantTurn->provider(),
            'prompt_tokens' => $assistantTurn->promptTokens(),
            'completion_tokens' => $assistantTurn->completionTokens(),
            'truncated' => $truncated,
        ]);

        $citationsView = [];
        if ($citedIds !== []) {
            $byId = [];
            foreach ($hits as $i => $hit) {
                $byId[$hit->publicationId] = ['n' => $i + 1, 'hit' => $hit];
            }
            foreach ($citedIds as $id) {
                if (!isset($byId[$id])) {
                    continue;
                }
                $hit = $byId[$id]['hit'];
                $citationsView[] = [
                    'n' => $byId[$id]['n'],
                    'publicationId' => $hit->publicationId,
                    'screenName' => $hit->screenName,
                    'snapshotDate' => $hit->snapshotDate,
                    'url' => $hit->url,
                    // Defensive re-clean: catches \n / NBSP / BOM in legacy
                    // rows that pre-date the embedder's TextCleaner pass.
                    'text' => $this->textCleaner->clean($hit->text),
                    'avatarUrl' => $hit->avatarUrl,
                    'reposts' => $hit->reposts,
                    'likes' => $hit->likes,
                    'replies' => $hit->replies,
                ];
            }
        }

        yield SseEvent::done((string) $conversation->id(), $citationsView);
    }
}
