<?php
declare(strict_types=1);

namespace App\Chat\Application\Stream;

/**
 * One frame on the SSE wire. The processor maps these to
 * `event: <type>\ndata: <json>\n\n`.
 */
final readonly class SseEvent
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        public string $type,
        public array $data,
    ) {
    }

    public static function token(string $delta): self
    {
        return new self('token', ['delta' => $delta]);
    }

    /**
     * @param list<array<string, mixed>> $citations
     */
    public static function done(string $conversationId, array $citations): self
    {
        return new self('done', [
            'conversationId' => $conversationId,
            'citations' => $citations,
        ]);
    }

    public static function error(string $code, mixed $extra = null): self
    {
        $data = ['code' => $code];
        if ($extra !== null) {
            $data['details'] = $extra;
        }

        return new self('error', $data);
    }
}
