<?php
declare(strict_types=1);

namespace App\Chat\Application\Port;

/**
 * Streams a chat completion as a sequence of token deltas.
 *
 * The concrete adapter wraps symfony/ai-platform's FailoverPlatform
 * (Mistral primary, Gemini fallback). Fallback only applies before the
 * first token is yielded — mid-stream failures bubble up.
 */
interface ChatStreamer
{
    /**
     * @param list<array{role: string, content: string}> $messages
     * @return iterable<string> token deltas
     */
    public function stream(array $messages): iterable;

    /**
     * Provider that handled the last `stream()` call. Useful for the
     * persisted assistant turn. Returns null before any stream runs.
     */
    public function lastProvider(): ?string;

    public function lastPromptTokens(): ?int;

    public function lastCompletionTokens(): ?int;
}
