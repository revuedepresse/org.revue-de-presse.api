<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526130002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Chat: create chat_conversation and chat_turn tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE chat_conversation (
    id UUID NOT NULL,
    bluesky_did VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    last_turn_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE INDEX idx_chat_conv_did_last ON chat_conversation (bluesky_did, last_turn_at)');

        $this->addSql(<<<'SQL'
CREATE TABLE chat_turn (
    id UUID NOT NULL,
    conversation_id UUID NOT NULL,
    role VARCHAR(16) NOT NULL,
    content TEXT NOT NULL,
    cited_publication_ids JSON DEFAULT NULL,
    provider VARCHAR(32) DEFAULT NULL,
    prompt_tokens INT DEFAULT NULL,
    completion_tokens INT DEFAULT NULL,
    truncated BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT chk_chat_turn_role CHECK (role IN ('user','assistant')),
    CONSTRAINT fk_chat_turn_conversation FOREIGN KEY (conversation_id)
        REFERENCES chat_conversation (id) ON DELETE CASCADE
)
SQL);
        $this->addSql('CREATE INDEX idx_chat_turn_conv_created ON chat_turn (conversation_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS chat_turn');
        $this->addSql('DROP TABLE IF EXISTS chat_conversation');
    }
}
