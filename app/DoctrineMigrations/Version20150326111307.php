<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150326111307 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE mailbox (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, kept_in_sync TINYINT(1) DEFAULT '1' NOT NULL, mail_count INT DEFAULT 0 NOT NULL, non_existent TINYINT(1) DEFAULT '0' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE mailbox_mail_uid (mailbox_id INT NOT NULL, mail_uid_id INT NOT NULL, INDEX IDX_F42524EB66EC35CC (mailbox_id), INDEX IDX_F42524EB8E72EBF0 (mail_uid_id), PRIMARY KEY(mailbox_id, mail_uid_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE mail_uid (id INT AUTO_INCREMENT NOT NULL, header_id INT DEFAULT NULL, imap_uid INT NOT NULL, fetched TINYINT(1) DEFAULT '0' NOT NULL, UNIQUE INDEX UNIQ_4F80296C2EF91FD8 (header_id), INDEX imapUid (imap_uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_interest (id INT AUTO_INCREMENT NOT NULL, native_id VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE mailbox_mail_uid ADD CONSTRAINT FK_F42524EB66EC35CC FOREIGN KEY (mailbox_id) REFERENCES mailbox (id)");
        $this->addSql("ALTER TABLE mailbox_mail_uid ADD CONSTRAINT FK_F42524EB8E72EBF0 FOREIGN KEY (mail_uid_id) REFERENCES mail_uid (id)");


        $this->addSql("ALTER TABLE message DROP FOREIGN KEY FK_21806EA261E1892C");
        $this->addSql("ALTER TABLE header CHANGE hdr_id id INT AUTO_INCREMENT NOT NULL");

        $this->addSql("CREATE INDEX cntId ON header (cnt_id)");
        $this->addSql("CREATE INDEX hdrImapUid ON header (hdr_imap_uid)");

        $this->addSql("ALTER TABLE message CHANGE hdr_id id INT NOT NULL");
        $this->addSql("ALTER TABLE message ADD CONSTRAINT FK_B6BD307FBF396750 FOREIGN KEY (id) REFERENCES header (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_B6BD307FBF396750 ON message (id)");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE mailbox_mail_uid DROP FOREIGN KEY FK_F42524EB66EC35CC");
        $this->addSql("ALTER TABLE mailbox_mail_uid DROP FOREIGN KEY FK_F42524EB8E72EBF0");
        $this->addSql("DROP TABLE mailbox");
        $this->addSql("DROP TABLE mailbox_mail_uid");
        $this->addSql("DROP TABLE mail_uid");
        $this->addSql("DROP TABLE weaving_facebook_interest");
        $this->addSql("DROP INDEX cntId ON header");
        $this->addSql("DROP INDEX hdrImapUid ON header");
        $this->addSql("ALTER TABLE header CHANGE id hdr_id INT AUTO_INCREMENT NOT NULL");
        $this->addSql("ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FBF396750");
        $this->addSql("DROP INDEX UNIQ_B6BD307FBF396750 ON message");
        $this->addSql("ALTER TABLE message CHANGE id hdr_id INT NOT NULL");
        $this->addSql("ALTER TABLE message ADD CONSTRAINT FK_B6BD307F61E1892C FOREIGN KEY (hdr_id) REFERENCES header (hdr_id)");
        $this->addSql("ALTER TABLE weaving_github_repositories DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE weaving_json DROP jsn_geolocated, CHANGE jsn_status jsn_status TINYINT(1) DEFAULT '0' NOT NULL, CHANGE jsn_type jsn_type TINYINT(1) NOT NULL");
        $this->addSql("CREATE FULLTEXT INDEX jsn_value ON weaving_json (jsn_value)");
    }
}
