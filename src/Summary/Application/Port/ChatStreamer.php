<?php
declare(strict_types=1);

namespace App\Summary\Application\Port;

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
     * @param array{max_tokens?: int} $options per-call overrides; recognised keys:
     *   - max_tokens: cap on generated tokens. Use a higher value (~600+)
     *     for synthesis-style replies. Default platform behaviour applies
     *     when unset.
     * @return iterable<string> token deltas
     */
    public function stream(array $messages, array $options = []): iterable;

    /**
     * Provider that handled the last `stream()` call. Useful for the
     * persisted assistant turn. Returns null before any stream runs.
     */
    public function lastProvider(): ?string;

    public function lastPromptTokens(): ?int;

    public function lastCompletionTokens(): ?int;
}
