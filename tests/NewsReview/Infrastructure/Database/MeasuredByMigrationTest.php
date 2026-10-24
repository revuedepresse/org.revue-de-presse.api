<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Database;

use App\NewsReview\Infrastructure\Database\Migrations\Version20261024160000;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class MeasuredByMigrationTest extends TestCase
{
    public function test_postgresql_adds_the_column_and_indexes_without_blocking_writes(): void
    {
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql',
            'serverVersion' => '14',
        ]);
        $migration = new Version20261024160000($connection, new NullLogger());

        self::assertFalse($migration->isTransactional(), 'CREATE INDEX CONCURRENTLY cannot run in a transaction');

        $migration->up(new Schema());
        $sql = array_map(static fn($query): string => (string) $query->getStatement(), $migration->getSql());

        self::assertSame(
            "ALTER TABLE status_popularity ADD COLUMN IF NOT EXISTS measured_by VARCHAR(16) NOT NULL DEFAULT 'fetch'",
            $sql[0],
        );
        self::assertStringContainsString("CHECK (measured_by IN ('fetch', 'rebuild')) NOT VALID", $sql[1]);
        self::assertSame('ALTER TABLE status_popularity VALIDATE CONSTRAINT chk_status_popularity_measured_by', $sql[2]);
        self::assertSame([
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_status_popularity_publication_checked_at ON status_popularity (publication_id, checked_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_publication_published_at ON publication (published_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_publication_document_id ON publication (document_id)',
        ], array_slice($sql, 3));
    }

    public function test_postgresql_down_removes_what_up_added(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_pgsql', 'serverVersion' => '14']);
        $migration = new Version20261024160000($connection, new NullLogger());

        $migration->down(new Schema());
        $sql = array_map(static fn($query): string => (string) $query->getStatement(), $migration->getSql());

        self::assertSame([
            'DROP INDEX CONCURRENTLY IF EXISTS idx_publication_document_id',
            'DROP INDEX CONCURRENTLY IF EXISTS idx_publication_published_at',
            'DROP INDEX CONCURRENTLY IF EXISTS idx_status_popularity_publication_checked_at',
            'ALTER TABLE status_popularity DROP CONSTRAINT IF EXISTS chk_status_popularity_measured_by',
            'ALTER TABLE status_popularity DROP COLUMN IF EXISTS measured_by',
        ], $sql);
    }

    public function test_existing_measurements_become_fetch_and_only_known_kinds_are_accepted(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE status_popularity (id CHAR(36) NOT NULL, status_id INTEGER, checked_at DATETIME NOT NULL, total_retweets INTEGER NOT NULL, total_favorites INTEGER NOT NULL, publication_id VARCHAR(255))');
        $connection->executeStatement('CREATE TABLE publication (id INTEGER NOT NULL, document_id VARCHAR(255), published_at DATETIME)');
        $connection->executeStatement("INSERT INTO status_popularity VALUES ('a', 1, '2026-10-23 12:00:00', 3, 4, 'at://p/1')");

        $this->run_($connection, 'up');

        self::assertSame('fetch', $connection->fetchOne("SELECT measured_by FROM status_popularity WHERE id = 'a'"));
        $connection->executeStatement("INSERT INTO status_popularity VALUES ('b', 1, '2026-10-23 20:00:00', 9, 9, 'at://p/1', 'rebuild')");

        try {
            $connection->executeStatement("INSERT INTO status_popularity VALUES ('c', 1, '2026-10-23 20:00:00', 9, 9, 'at://p/1', 'guess')");
            self::fail('an unknown measurement kind must be rejected');
        } catch (\Doctrine\DBAL\Exception $expected) {
        }

        $indexes = $connection->fetchFirstColumn("SELECT name FROM sqlite_master WHERE type = 'index' ORDER BY name");
        self::assertSame(['idx_publication_document_id', 'idx_publication_published_at', 'idx_status_popularity_publication_checked_at'], $indexes);

        $this->run_($connection, 'down');

        self::assertNotContains('measured_by', array_column($connection->fetchAllAssociative('PRAGMA table_info(status_popularity)'), 'name'));
        self::assertSame([], $connection->fetchFirstColumn("SELECT name FROM sqlite_master WHERE type = 'index'"));
    }

    private function run_(Connection $connection, string $direction): void
    {
        $migration = new Version20261024160000($connection, new NullLogger());
        $migration->{$direction}(new Schema());
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement((string) $query->getStatement());
        }
    }
}
