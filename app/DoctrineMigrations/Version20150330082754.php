<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150330082754 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE mailbox_mail_uid_header (id INT AUTO_INCREMENT NOT NULL, mailbox_id INT NOT NULL, imap_uid INT NOT NULL, header_fingerprint VARCHAR(40) DEFAULT NULL, unreachable_header TINYINT(1) DEFAULT '0' NOT NULL, INDEX imapUid (imap_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE INDEX fingerprint ON header (hdr_hash)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE mailbox_mail_uid_header");
        $this->addSql("DROP INDEX fingerprint ON header");
    }
}
