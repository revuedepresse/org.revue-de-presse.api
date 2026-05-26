<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526130001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Chat: enable pgvector extension (no-op in prod, needed for dev/CI parity)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');
    }

    public function down(Schema $schema): void
    {
        // Don't drop the extension on rollback — other tables in this database
        // may grow vector columns later. Rolling back is a no-op.
    }
}
