<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Symfony\Ai;

use App\Chat\Application\Port\ChatStreamer;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;

/**
 * Adapter over symfony/ai-platform. The injected $platform is expected to be
 * the `FailoverPlatform` configured in ai.yaml (Mistral primary, Gemini
 * fallback). Fallback applies only before the first token is emitted —
 * mid-stream failures bubble out of `stream()`.
 *
 * v0.9 caveat: the exact MessageBag construction and `$result->getContent()`
 * iteration shape are inferred from the public symfony.com/doc samples. Verify
 * against the resolved package source at composer install time.
 */
final class SymfonyAiChatStreamer implements ChatStreamer
{
    private ?string $lastProvider = null;
    private ?int $lastPromptTokens = null;
    private ?int $lastCompletionTokens = null;

    public function __construct(
        private readonly PlatformInterface $platform,
        private readonly string $model = 'mistral-small-latest',
    ) {
    }

    public function stream(array $messages): iterable
    {
        $bag = new MessageBag();
        foreach ($messages as $message) {
            $bag = match ($message['role']) {
                'system' => $bag->with(Message::forSystem($message['content'])),
                'assistant' => $bag->with(Message::ofAssistant($message['content'])),
                default => $bag->with(Message::ofUser($message['content'])),
            };
        }

        $result = $this->platform->invoke($this->model, $bag, ['stream' => true]);

        // TODO(v0.9-API-check): the result metadata API may differ.
        $metadata = method_exists($result, 'getMetadata') ? $result->getMetadata() : null;
        if ($metadata !== null) {
            $this->lastProvider = $this->extractMeta($metadata, 'provider');
            $promptTokens = $this->extractMeta($metadata, 'prompt_tokens');
            $completionTokens = $this->extractMeta($metadata, 'completion_tokens');
            $this->lastPromptTokens = is_numeric($promptTokens) ? (int) $promptTokens : null;
            $this->lastCompletionTokens = is_numeric($completionTokens) ? (int) $completionTokens : null;
        }

        foreach ($result->getContent() as $delta) {
            yield (string) $delta;
        }
    }

    public function lastProvider(): ?string
    {
        return $this->lastProvider;
    }

    public function lastPromptTokens(): ?int
    {
        return $this->lastPromptTokens;
    }

    public function lastCompletionTokens(): ?int
    {
        return $this->lastCompletionTokens;
    }

    private function extractMeta(mixed $metadata, string $key): mixed
    {
        if (is_array($metadata)) {
            return $metadata[$key] ?? null;
        }
        if (is_object($metadata) && method_exists($metadata, 'get')) {
            return $metadata->get($key);
        }

        return null;
    }
}
