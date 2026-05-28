<?php
declare(strict_types=1);

namespace App\Tests\Summary\Application;

use App\Summary\Application\Port\ChatStreamer;
use App\Summary\Domain\Text\TextCleaner;
use App\NewsReview\Domain\Snapshot\Filter\HighlightNormalizer;
use App\NewsReview\Domain\Snapshot\SnapshotReader;
use App\Summary\Application\DailySummaryGenerator;
use PHPUnit\Framework\TestCase;

final class DailySummaryGeneratorTest extends TestCase
{
    public function testReturnsNullWhenSnapshotIsMissing(): void
    {
        $gen = $this->makeGenerator(snapshotRows: [], scriptedDeltas: ['ignored']);
        self::assertNull($gen->generate('2025-03-04'));
    }

    public function testBuildsPromptAndStreamsTheMarkdownBack(): void
    {
        $row = $this->snapshotRow(
            publicationId: 'at://pub-1',
            screenName: 'lemonde.fr',
            text: "Donald Trump gèle l'aide militaire\n— et bien d'autres",
            reposts: 73,
            likes: 169,
            date: '2025-03-04',
        );
        $streamer = new RecordingStreamer(deltas: ["## Politique\n", 'Selon lemonde.fr, …']);
        $gen = $this->makeGenerator(snapshotRows: [$row], streamer: $streamer);

        $summary = $gen->generate('2025-03-04');

        self::assertNotNull($summary);
        self::assertSame('2025-03-04', $summary->date);
        self::assertSame("## Politique\nSelon lemonde.fr, …\n", $summary->markdown);

        // System prompt + user message shape.
        self::assertCount(2, $streamer->lastMessages);
        self::assertSame('system', $streamer->lastMessages[0]['role']);
        self::assertStringContainsString('rédacteur en chef', $streamer->lastMessages[0]['content']);

        $user = $streamer->lastMessages[1];
        self::assertSame('user', $user['role']);
        self::assertStringContainsString('mardi 4 mars 2025', $user['content']);
        self::assertStringContainsString('[1] lemonde.fr — 73 reposts, 169 likes', $user['content']);
        // TextCleaner ran on the embedded text — no real \n inside the quoted block.
        self::assertStringContainsString("Donald Trump gèle l'aide militaire — et bien d'autres", $user['content']);
    }

    public function testPassesMaxTokensSoMistralCanWriteAFullSynthesis(): void
    {
        $row = $this->snapshotRow('at://x', 'humanite.fr', 'Texte', 1, 1, '2025-03-04');
        $streamer = new RecordingStreamer(['ok']);
        $gen = $this->makeGenerator(snapshotRows: [$row], streamer: $streamer);

        $gen->generate('2025-03-04');

        self::assertArrayHasKey('max_tokens', $streamer->lastOptions);
        self::assertGreaterThanOrEqual(600, $streamer->lastOptions['max_tokens']);
    }

    public function testTrimsTrailingWhitespaceAndAddsExactlyOneTrailingNewline(): void
    {
        // Mistral output can have multiple trailing \n or spurious whitespace.
        // Normalise so the saved markdown ends with exactly one newline (good
        // for diff hygiene + POSIX text-file convention).
        $row = $this->snapshotRow('at://x', 'humanite.fr', 'T', 1, 1, '2025-03-04');
        $streamer = new RecordingStreamer(["## H\n\nbody  \n\n\n"]);
        $gen = $this->makeGenerator([$row], streamer: $streamer);

        $summary = $gen->generate('2025-03-04');
        self::assertNotNull($summary);
        self::assertSame("## H\n\nbody\n", $summary->markdown);
    }

    /**
     * @param list<array<string, mixed>> $snapshotRows
     * @param list<string>|null $scriptedDeltas
     */
    private function makeGenerator(
        array $snapshotRows,
        ?array $scriptedDeltas = null,
        ?RecordingStreamer $streamer = null,
    ): DailySummaryGenerator {
        $reader = new InMemorySnapshotReader($snapshotRows);
        $normalizer = new HighlightNormalizer();
        $cleaner = new TextCleaner();
        $streamer = $streamer ?? new RecordingStreamer($scriptedDeltas ?? []);

        return new DailySummaryGenerator($reader, $normalizer, $cleaner, $streamer);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotRow(
        string $publicationId,
        string $screenName,
        string $text,
        int $reposts,
        int $likes,
        string $date,
    ): array {
        // Snake_case fields per HighlightNormalizer::toDto's actual keys.
        return [
            'publication_id' => $publicationId,
            'screen_name' => $screenName,
            'avatar_url' => 'https://cdn.bsky.app/img/avatar/x.jpg',
            'text' => $text,
            'reposts' => $reposts,
            'likes' => $likes,
            'replies' => 0,
            'publicationDateTime' => $date . 'T12:00:00Z',
        ];
    }
}

/** @internal */
final class InMemorySnapshotReader implements SnapshotReader
{
    /** @param list<array<string, mixed>> $rows */
    public function __construct(private readonly array $rows)
    {
    }

    public function read(string $date): array
    {
        return $this->rows;
    }
}

/** @internal Records the messages + options passed to stream() for assertions. */
final class RecordingStreamer implements ChatStreamer
{
    /** @var list<array{role: string, content: string}> */
    public array $lastMessages = [];
    /** @var array{max_tokens?: int} */
    public array $lastOptions = [];

    /** @param list<string> $deltas */
    public function __construct(private readonly array $deltas)
    {
    }

    public function stream(array $messages, array $options = []): iterable
    {
        $this->lastMessages = $messages;
        $this->lastOptions = $options;
        foreach ($this->deltas as $d) {
            yield $d;
        }
    }

    public function lastProvider(): ?string { return null; }
    public function lastPromptTokens(): ?int { return null; }
    public function lastCompletionTokens(): ?int { return null; }
}
