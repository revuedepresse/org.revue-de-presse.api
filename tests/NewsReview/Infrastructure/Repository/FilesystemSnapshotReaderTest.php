<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Repository;

use App\NewsReview\Infrastructure\Repository\FilesystemSnapshotReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FilesystemSnapshotReaderTest extends TestCase
{
    public function test_returns_decoded_json_for_existing_date(): void
    {
        $reader = new FilesystemSnapshotReader(
            projectDir: dirname(__DIR__, 4),
            logger: new NullLogger(),
        );

        $result = $reader->read('2026-05-02');

        self::assertIsArray($result);
        self::assertNotEmpty($result, 'Expected at least one entry in the 2026-05-02 snapshot');
    }

    public function test_returns_empty_array_for_missing_date(): void
    {
        $reader = new FilesystemSnapshotReader(
            projectDir: dirname(__DIR__, 4),
            logger: new NullLogger(),
        );

        self::assertSame([], $reader->read('1999-01-01'));
    }
}
