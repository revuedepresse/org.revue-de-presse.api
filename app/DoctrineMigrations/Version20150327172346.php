<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150327172346 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE header DROP INDEX hdrImapUid, ADD UNIQUE INDEX hdr_imap_id (hdr_imap_uid)");
        $this->addSql("ALTER TABLE mail_uid ADD unreachable_header TINYINT(1) DEFAULT '0' NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE header DROP INDEX hdr_imap_id, ADD INDEX hdrImapUid (hdr_imap_uid)");
        $this->addSql("ALTER TABLE mail_uid DROP unreachable_header");
    }
}
