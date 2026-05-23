<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523130217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create newsletter_subscribers table with encrypted-email column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE newsletter_subscribers (
    id UUID NOT NULL,
    email_hash CHAR(64) NOT NULL,
    email_encrypted BYTEA NOT NULL,
    status VARCHAR(16) NOT NULL,
    confirm_token CHAR(43) DEFAULT NULL,
    confirm_expires_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    unsub_token CHAR(43) NOT NULL,
    enrolled_by VARCHAR(64) NOT NULL,
    enrolled_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    confirmed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    unsubscribed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    last_sent_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT chk_newsletter_subscribers_status CHECK (status IN ('pending','active','unsubscribed'))
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uq_newsletter_subscribers_email_hash ON newsletter_subscribers (email_hash)');
        $this->addSql('CREATE UNIQUE INDEX uq_newsletter_subscribers_confirm_token ON newsletter_subscribers (confirm_token)');
        $this->addSql('CREATE UNIQUE INDEX uq_newsletter_subscribers_unsub_token ON newsletter_subscribers (unsub_token)');
        $this->addSql('CREATE INDEX idx_newsletter_subscribers_status ON newsletter_subscribers (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE newsletter_subscribers');
    }
}
