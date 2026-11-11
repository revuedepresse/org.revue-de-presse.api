<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record the issue date last sent to each subscriber, so a re-run never mails the same issue twice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE newsletter_subscribers ADD last_sent_issue DATE DEFAULT NULL');
        // Issues are sent the morning after their date (NEWSLETTER_DAILY_TZ,
        // Europe/Paris in production), so the backfilled issue is the local
        // send date minus one day.
        $this->addSql("UPDATE newsletter_subscribers SET last_sent_issue = (last_sent_at AT TIME ZONE 'Europe/Paris')::date - 1 WHERE last_sent_at IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE newsletter_subscribers DROP last_sent_issue');
    }
}
