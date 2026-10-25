<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Database;

use App\NewsReview\Infrastructure\Database\Migrations\Version20261024160000;
use App\NewsReview\Infrastructure\Database\Migrations\Version20261025180000;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class FirstSightingMeasurementMigrationTest extends TestCase
{
    public function test_postgresql_swaps_the_constraint_then_validates_it_without_blocking_writes(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_pgsql', 'serverVersion' => '14']);
        $migration = new Version20261025180000($connection, new NullLogger());

        self::assertFalse($migration->isTransactional(), 'VALIDATE CONSTRAINT runs on its own, after the swap');

        $migration->up(new Schema());
        $sql = array_map(static fn($query): string => (string) $query->getStatement(), $migration->getSql());

        self::assertCount(2, $sql);
        self::assertStringContainsString("LIKE '%first-sighting%') = true", $sql[0]);
        self::assertStringContainsString("CHECK (measured_by IN ('fetch', 'rebuild', 'first-sighting')) NOT VALID", $sql[0]);
        self::assertSame('ALTER TABLE status_popularity VALIDATE CONSTRAINT chk_status_popularity_measured_by', $sql[1]);
    }

    public function test_postgresql_down_removes_first_sightings_before_restoring_the_constraint(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_pgsql', 'serverVersion' => '14']);
        $migration = new Version20261025180000($connection, new NullLogger());

        $migration->down(new Schema());
        $sql = array_map(static fn($query): string => (string) $query->getStatement(), $migration->getSql());

        self::assertSame("DELETE FROM status_popularity WHERE measured_by = 'first-sighting'", $sql[0]);
        self::assertStringContainsString("LIKE '%first-sighting%') = false", $sql[1]);
        self::assertStringContainsString("CHECK (measured_by IN ('fetch', 'rebuild')) NOT VALID", $sql[1]);
    }

    public function test_first_sightings_are_accepted_and_existing_kinds_kept(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE status_popularity (id CHAR(36) NOT NULL, status_id INTEGER, checked_at DATETIME NOT NULL, total_retweets INTEGER NOT NULL, total_favorites INTEGER NOT NULL, publication_id VARCHAR(255))');
        $connection->executeStatement('CREATE TABLE publication (id INTEGER NOT NULL, document_id VARCHAR(255), published_at DATETIME)');
        $this->run_(new Version20261024160000($connection, new NullLogger()), $connection, 'up');
        $connection->executeStatement("INSERT INTO status_popularity VALUES ('a', 1, '2026-10-23 12:00:00', 3, 4, 'at://p/1', 'fetch')");
        $connection->executeStatement("INSERT INTO status_popularity VALUES ('b', 1, '2026-10-23 20:00:00', 9, 9, 'at://p/1', 'rebuild')");

        $this->run_(new Version20261025180000($connection, new NullLogger()), $connection, 'up');

        $connection->executeStatement("INSERT INTO status_popularity VALUES ('c', 1, '2026-10-23 10:34:00', 2, 3, 'at://p/1', 'first-sighting')");
        self::assertSame(
            ['a' => 'fetch', 'b' => 'rebuild', 'c' => 'first-sighting'],
            $connection->fetchAllKeyValue('SELECT id, measured_by FROM status_popularity ORDER BY id'),
        );

        try {
            $connection->executeStatement("INSERT INTO status_popularity VALUES ('d', 1, '2026-10-23 20:00:00', 9, 9, 'at://p/1', 'guess')");
            self::fail('an unknown measurement kind must be rejected');
        } catch (\Doctrine\DBAL\Exception $expected) {
        }

        $connection->executeStatement("DELETE FROM status_popularity WHERE id = 'c'");
        $this->run_(new Version20261025180000($connection, new NullLogger()), $connection, 'down');

        self::assertSame(
            ['a' => 'fetch', 'b' => 'rebuild'],
            $connection->fetchAllKeyValue('SELECT id, measured_by FROM status_popularity ORDER BY id'),
        );
        try {
            $connection->executeStatement("INSERT INTO status_popularity VALUES ('e', 1, '2026-10-23 10:34:00', 2, 3, 'at://p/1', 'first-sighting')");
            self::fail('first-sighting must be rejected once the migration is reverted');
        } catch (\Doctrine\DBAL\Exception $expected) {
        }
    }

    private function run_(AbstractMigration $migration, Connection $connection, string $direction): void
    {
        $migration->{$direction}(new Schema());
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement((string) $query->getStatement());
        }
    }
}
