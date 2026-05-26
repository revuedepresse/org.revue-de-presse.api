<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Symfony\Ai;

use App\Chat\Application\Port\ChatStreamer;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsage;

/**
 * Adapter over symfony/ai-platform. The injected $platform is expected
 * to be the FailoverPlatform configured in ai.yaml (Mistral primary,
 * Gemini fallback). Provider fallback applies only before the first
 * token is emitted — once the stream starts, mid-stream errors bubble
 * out of `stream()`.
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

        $deferred = $this->platform->invoke($this->model, $bag, ['stream' => true]);

        foreach ($deferred->asTextStream() as $delta) {
            yield $delta->getText();
        }

        // After the stream completes, DeferredResult promotes the StreamResult's
        // metadata up onto itself (see DeferredResult::asStream finally block).
        $metadata = $deferred->getMetadata();
        $platformName = $metadata->get('platform');
        $this->lastProvider = \is_string($platformName) ? $platformName : null;

        $usage = $metadata->get(TokenUsage::class);
        if ($usage instanceof TokenUsage) {
            $this->lastPromptTokens = $usage->getPromptTokens();
            $this->lastCompletionTokens = $usage->getCompletionTokens();
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
}
