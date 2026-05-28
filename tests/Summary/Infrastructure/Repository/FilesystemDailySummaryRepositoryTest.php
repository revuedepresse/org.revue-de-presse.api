<?php
declare(strict_types=1);

namespace App\Tests\Summary\Infrastructure\Repository;

use App\Summary\Domain\DailySummary;
use App\Summary\Infrastructure\Repository\FilesystemDailySummaryRepository;
use PHPUnit\Framework\TestCase;

final class FilesystemDailySummaryRepositoryTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/rdp-summary-test-' . uniqid();
        mkdir($this->tmp . '/src/Bluesky/Resources', 0o755, true);
    }

    protected function tearDown(): void
    {
        // Clean up — files only, fail safe.
        $glob = glob($this->tmp . '/src/Bluesky/Resources/*');
        foreach ($glob ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmp . '/src/Bluesky/Resources');
        @rmdir($this->tmp . '/src/Bluesky');
        @rmdir($this->tmp . '/src');
        @rmdir($this->tmp);
    }

    public function testExistsReturnsFalseWhenNoFile(): void
    {
        $repo = new FilesystemDailySummaryRepository($this->tmp);
        self::assertFalse($repo->exists('2025-03-04'));
    }

    public function testSaveThenReadRoundTrip(): void
    {
        $repo = new FilesystemDailySummaryRepository($this->tmp);
        $summary = new DailySummary(
            date: '2025-03-04',
            markdown: "## Politique\nUne synthèse.\n",
        );

        $repo->save($summary);

        self::assertTrue($repo->exists('2025-03-04'));
        $loaded = $repo->read('2025-03-04');
        self::assertNotNull($loaded);
        self::assertSame('2025-03-04', $loaded->date);
        self::assertSame("## Politique\nUne synthèse.\n", $loaded->markdown);
    }

    public function testReadReturnsNullWhenAbsent(): void
    {
        $repo = new FilesystemDailySummaryRepository($this->tmp);
        self::assertNull($repo->read('2099-01-01'));
    }

    public function testSaveIsAtomicNoStaleTmpFileRemains(): void
    {
        // Atomic write: file_put_contents to .tmp then rename. After save,
        // there must be exactly one summary file and no .tmp leftover.
        $repo = new FilesystemDailySummaryRepository($this->tmp);
        $repo->save(new DailySummary('2025-03-04', 'x'));

        $files = glob($this->tmp . '/src/Bluesky/Resources/*');
        self::assertNotFalse($files);
        $names = array_map('basename', $files);
        sort($names);
        self::assertSame(['2025-03-04-summary.md'], $names);
    }

    public function testSaveOverwritesExistingFile(): void
    {
        $repo = new FilesystemDailySummaryRepository($this->tmp);
        $repo->save(new DailySummary('2025-03-04', 'old'));
        $repo->save(new DailySummary('2025-03-04', 'new'));

        $loaded = $repo->read('2025-03-04');
        self::assertNotNull($loaded);
        self::assertSame('new', $loaded->markdown);
    }

    public function testRejectsMalformedDateInPathToPreventTraversal(): void
    {
        // Defensive: path traversal via "../" or absolute paths must not
        // escape the resources directory. The repository validates the
        // date format strictly (YYYY-MM-DD).
        $repo = new FilesystemDailySummaryRepository($this->tmp);
        $this->expectException(\InvalidArgumentException::class);
        $repo->read('../../etc/passwd');
    }
}
