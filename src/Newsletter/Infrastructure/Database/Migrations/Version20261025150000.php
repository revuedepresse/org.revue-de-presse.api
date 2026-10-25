<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Newsletter editions: a third one, fr-coop-medias, both lists in one letter';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE newsletter_subscribers DROP CONSTRAINT chk_newsletter_subscribers_edition');
        $this->addSql("ALTER TABLE newsletter_subscribers ADD CONSTRAINT chk_newsletter_subscribers_edition CHECK (edition IN ('fr','coop-medias','fr-coop-medias'))");
    }

    public function down(Schema $schema): void
    {
        // Refuses to run while a fr-coop-medias subscription exists, rather than drop it.
        $this->addSql('ALTER TABLE newsletter_subscribers DROP CONSTRAINT chk_newsletter_subscribers_edition');
        $this->addSql("ALTER TABLE newsletter_subscribers ADD CONSTRAINT chk_newsletter_subscribers_edition CHECK (edition IN ('fr','coop-medias'))");
    }
}
