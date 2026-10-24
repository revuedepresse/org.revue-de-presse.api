<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lets status_popularity hold two kinds of measurements side by side:
 * `fetch` (every existing row: counts read when the worker fetched a post)
 * and `rebuild` (counts at a cut-off, rebuilt from like and repost timestamps).
 *
 * Written for a large, busy table: the column has a constant default, so
 * PostgreSQL adds it without rewriting the table; the check constraint is added
 * NOT VALID and validated separately, so the validating scan doesn't block
 * writes; indexes are built CONCURRENTLY, which can't run in a transaction,
 * hence a non-transactional migration. Every statement is idempotent, so the
 * migration is a no-op where the same schema change was already applied.
 */
final class Version20261024160000 extends AbstractMigration
{
    private const INDEXES = [
        'idx_status_popularity_publication_checked_at' => 'status_popularity (publication_id, checked_at)',
        'idx_publication_published_at' => 'publication (published_at)',
        'idx_publication_document_id' => 'publication (document_id)',
    ];

    public function getDescription(): string
    {
        return 'Tell fetched from rebuilt popularity measurements (status_popularity.measured_by) and index the end-of-day rebuild lookups';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->isPostgreSql()) {
            // SQLite, for tests: no IF NOT EXISTS on ADD COLUMN, no NOT VALID.
            $this->addSql("ALTER TABLE status_popularity ADD COLUMN measured_by VARCHAR(16) NOT NULL DEFAULT 'fetch' CONSTRAINT chk_status_popularity_measured_by CHECK (measured_by IN ('fetch', 'rebuild'))");
            foreach (self::INDEXES as $name => $on) {
                $this->addSql(sprintf('CREATE INDEX IF NOT EXISTS %s ON %s', $name, $on));
            }

            return;
        }

        $this->addSql("ALTER TABLE status_popularity ADD COLUMN IF NOT EXISTS measured_by VARCHAR(16) NOT NULL DEFAULT 'fetch'");
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_status_popularity_measured_by') THEN
        ALTER TABLE status_popularity
            ADD CONSTRAINT chk_status_popularity_measured_by CHECK (measured_by IN ('fetch', 'rebuild')) NOT VALID;
    END IF;
END
$$
SQL);
        $this->addSql('ALTER TABLE status_popularity VALIDATE CONSTRAINT chk_status_popularity_measured_by');
        foreach (self::INDEXES as $name => $on) {
            $this->addSql(sprintf('CREATE INDEX CONCURRENTLY IF NOT EXISTS %s ON %s', $name, $on));
        }
    }

    public function down(Schema $schema): void
    {
        $concurrently = $this->isPostgreSql() ? 'CONCURRENTLY ' : '';
        foreach (array_reverse(array_keys(self::INDEXES)) as $name) {
            $this->addSql(sprintf('DROP INDEX %sIF EXISTS %s', $concurrently, $name));
        }

        if ($this->isPostgreSql()) {
            $this->addSql('ALTER TABLE status_popularity DROP CONSTRAINT IF EXISTS chk_status_popularity_measured_by');
            $this->addSql('ALTER TABLE status_popularity DROP COLUMN IF EXISTS measured_by');

            return;
        }

        // SQLite drops the column together with its column-level constraint.
        $this->addSql('ALTER TABLE status_popularity DROP COLUMN measured_by');
    }

    private function isPostgreSql(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform;
    }
}
