<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Repository;

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
