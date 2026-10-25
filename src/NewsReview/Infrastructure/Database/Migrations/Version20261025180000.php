<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lets status_popularity hold a third kind of measurement, `first-sighting`:
 * a post's counts exactly 34 minutes after its publication (the median delay
 * before a post's first fetch), read from like and repost timestamps. The
 * first-sighting ranking reads these rows only, for the nightly snapshots and
 * the rebuilt history alike, so both follow one rule.
 *
 * The constraint is swapped in one statement (dropped and re-added NOT VALID),
 * then validated separately, so the validating scan of this large table doesn't
 * block writes; VALIDATE can't share a transaction with that, hence a
 * non-transactional migration. Every statement is idempotent.
 */
final class Version20261025180000 extends AbstractMigration
{
    private const BEFORE = "('fetch', 'rebuild')";
    private const AFTER = "('fetch', 'rebuild', 'first-sighting')";

    public function getDescription(): string
    {
        return 'Accept first-sighting measurements (counts 34 minutes after publication) in status_popularity.measured_by';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->swap(self::AFTER);
    }

    public function down(Schema $schema): void
    {
        if ($this->isPostgreSql()) {
            $this->addSql("DELETE FROM status_popularity WHERE measured_by = 'first-sighting'");
        }

        $this->swap(self::BEFORE);
    }

    private function swap(string $kinds): void
    {
        if (!$this->isPostgreSql()) {
            // SQLite, for tests: a column-level constraint can't be altered, so
            // the column is rebuilt around a copy of its values.
            $this->addSql('ALTER TABLE status_popularity ADD COLUMN measured_by_copy VARCHAR(16)');
            $this->addSql('UPDATE status_popularity SET measured_by_copy = measured_by');
            $this->addSql('ALTER TABLE status_popularity DROP COLUMN measured_by');
            $this->addSql(sprintf(
                "ALTER TABLE status_popularity ADD COLUMN measured_by VARCHAR(16) NOT NULL DEFAULT 'fetch' CONSTRAINT chk_status_popularity_measured_by CHECK (measured_by IN %s)",
                $kinds,
            ));
            $this->addSql(sprintf('UPDATE status_popularity SET measured_by = measured_by_copy WHERE measured_by_copy IN %s', $kinds));
            $this->addSql('ALTER TABLE status_popularity DROP COLUMN measured_by_copy');

            return;
        }

        $this->addSql(sprintf(<<<'SQL'
DO $$
BEGIN
    -- Already accepting exactly the wanted kinds: nothing to swap.
    IF NOT EXISTS (SELECT 1 FROM pg_constraint
                    WHERE conrelid = 'status_popularity'::regclass
                      AND conname = 'chk_status_popularity_measured_by'
                      AND (pg_get_constraintdef(oid) LIKE '%%first-sighting%%') = %s) THEN
        ALTER TABLE status_popularity
            DROP CONSTRAINT IF EXISTS chk_status_popularity_measured_by,
            ADD CONSTRAINT chk_status_popularity_measured_by CHECK (measured_by IN %s) NOT VALID;
    END IF;
END
$$
SQL, str_contains($kinds, 'first-sighting') ? 'true' : 'false', $kinds));
        $this->addSql('ALTER TABLE status_popularity VALIDATE CONSTRAINT chk_status_popularity_measured_by');
    }

    private function isPostgreSql(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform;
    }
}
