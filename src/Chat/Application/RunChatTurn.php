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
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Uid\Ulid;

/**
 * One chat turn end-to-end: rate-limit, persist user turn, retrieve, stream,
 * persist assistant turn. The processor adapts the generator to SSE.
 */
final class RunChatTurn
{
    private const HISTORY_TURN_LIMIT = 6;
    private const RETRIEVAL_K = 8;
    private const COSINE_DISTANCE_CUTOFF = 0.6;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly RateLimiterFactory $chatPerUserLimiter,
        private readonly RateLimiterFactory $chatGlobalQuotaLimiter,
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

        $existing = $conversationId !== null ? Ulid::fromString($conversationId) : null;
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

        $filters = $this->filterExtractor->extract($cleaned);
        $hits = $this->retriever->retrieve($cleaned, self::RETRIEVAL_K, $filters);
        $hits = array_values(array_filter(
            $hits,
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
            $messages[] = ['role' => $past->role(), 'content' => $past->content()];
        }
        $messages[] = ['role' => 'user', 'content' => $this->promptBuilder->buildUserMessage($cleaned, $hits)];

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
                    'text' => $hit->text,
                ];
            }
        }

        yield SseEvent::done((string) $conversation->id(), $citationsView);
    }
}
