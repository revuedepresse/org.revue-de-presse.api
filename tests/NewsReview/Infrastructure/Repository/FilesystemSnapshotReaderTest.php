<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Snapshot\PublisherList;
use App\NewsReview\Infrastructure\Repository\FilesystemSnapshotReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FilesystemSnapshotReaderTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/snapshot-reader-test-' . bin2hex(random_bytes(6));
        mkdir($this->projectDir . '/src/Bluesky/Resources', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function test_returns_decoded_json_for_existing_date(): void
    {
        $payload = [['id' => 'abc', 'title' => 'first highlight']];
        file_put_contents(
            $this->projectDir . '/src/Bluesky/Resources/2026-05-02.json',
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $reader = new FilesystemSnapshotReader(
            projectDir: $this->projectDir,
            logger: new NullLogger(),
        );

        $result = $reader->read('2026-05-02');

        self::assertSame($payload, $result);
    }

    public function test_returns_empty_array_for_missing_date(): void
    {
        $reader = new FilesystemSnapshotReader(
            projectDir: $this->projectDir,
            logger: new NullLogger(),
        );

        self::assertSame([], $reader->read('1999-01-01'));
    }

    public function test_reads_other_lists_from_files_named_after_them(): void
    {
        file_put_contents(
            $this->projectDir . '/src/Bluesky/Resources/2026-05-02.json',
            json_encode([['screen_name' => 'lemonde.fr']], JSON_THROW_ON_ERROR),
        );
        file_put_contents(
            $this->projectDir . '/src/Bluesky/Resources/2026-05-02.coop-medias.first-sighting.json',
            json_encode([['screen_name' => 'reporterre.net']], JSON_THROW_ON_ERROR),
        );

        $reader = new FilesystemSnapshotReader(
            projectDir: $this->projectDir,
            logger: new NullLogger(),
        );

        self::assertSame([['screen_name' => 'lemonde.fr']], $reader->read('2026-05-02'));
        self::assertSame([['screen_name' => 'lemonde.fr']], $reader->read('2026-05-02', PublisherList::FrenchMedia));
        self::assertSame(
            [['screen_name' => 'reporterre.net']],
            $reader->read('2026-05-02', PublisherList::CoopMedias),
        );
    }

    public function test_returns_empty_array_when_a_list_has_no_snapshot_yet(): void
    {
        $reader = new FilesystemSnapshotReader(
            projectDir: $this->projectDir,
            logger: new NullLogger(),
        );

        self::assertSame([], $reader->read('2026-05-02', PublisherList::CoopMedias));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
