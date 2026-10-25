<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Newsletter editions: one subscription per address and edition (fr, coop-medias)';
    }

    public function up(Schema $schema): void
    {
        // Every existing subscription is to the French media letter, the only one there was.
        $this->addSql("ALTER TABLE newsletter_subscribers ADD edition VARCHAR(16) DEFAULT 'fr' NOT NULL");
        $this->addSql("ALTER TABLE newsletter_subscribers ADD CONSTRAINT chk_newsletter_subscribers_edition CHECK (edition IN ('fr','coop-medias'))");
        $this->addSql('DROP INDEX uq_newsletter_subscribers_email_hash');
        $this->addSql('CREATE UNIQUE INDEX uq_newsletter_subscribers_email_hash_edition ON newsletter_subscribers (email_hash, edition)');
    }

    public function down(Schema $schema): void
    {
        // Refuses to run while an address holds both editions, rather than drop a subscription.
        $this->addSql('DROP INDEX uq_newsletter_subscribers_email_hash_edition');
        $this->addSql('CREATE UNIQUE INDEX uq_newsletter_subscribers_email_hash ON newsletter_subscribers (email_hash)');
        $this->addSql('ALTER TABLE newsletter_subscribers DROP CONSTRAINT chk_newsletter_subscribers_edition');
        $this->addSql('ALTER TABLE newsletter_subscribers DROP edition');
    }
}
