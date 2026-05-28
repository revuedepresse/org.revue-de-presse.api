<?php
declare(strict_types=1);

namespace App\Summary\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops the chat product schema: chat_turn, chat_conversation, and the
 * pgvector embedding store chat_publication_embedding (created on demand
 * by `ai:store:setup` against the symfony/ai-bundle pgvector store).
 *
 * The pgvector extension itself is preserved — other features may grow
 * vector columns later, and dropping the extension is a one-way decision
 * that doesn't belong in a feature-removal migration.
 */
final class Version20260528150001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Summary: drop chat product schema (chat_turn, chat_conversation, chat_publication_embedding)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS chat_turn');
        $this->addSql('DROP TABLE IF EXISTS chat_conversation');
        $this->addSql('DROP TABLE IF EXISTS chat_publication_embedding');
    }

    public function down(Schema $schema): void
    {
        // No reverse path: re-creating these tables would also require
        // re-introducing the App\Chat code that owned them. If you need
        // chat back, revert this migration manually and restore the
        // App\Chat sources from git history.
        $this->throwIrreversibleMigrationException();
    }
}
