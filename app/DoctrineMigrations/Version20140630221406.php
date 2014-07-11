<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140630221406 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE message_property (property_id INT NOT NULL, message_id INT NOT NULL, type INT NOT NULL, INDEX IDX_82D4D8CF537A1329 (message_id), INDEX IDX_82D4D8CF549213EC (property_id), PRIMARY KEY(property_id, message_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE message_property ADD CONSTRAINT FK_82D4D8CF537A1329 FOREIGN KEY (message_id) REFERENCES message (msg_id)");
        $this->addSql("ALTER TABLE message_property ADD CONSTRAINT FK_82D4D8CF549213EC FOREIGN KEY (property_id) REFERENCES property (id)");
        $this->addSql("RENAME TABLE weaving_header to header");
        $this->addSql("RENAME TABLE weaving_message to message");
        $this->addSql("ALTER TABLE header ADD labelled TINYINT(1) DEFAULT '0' NOT NULL, ADD gmail_message_id VARCHAR(255) DEFAULT NULL, ADD gmail_thread_id VARCHAR(255) DEFAULT NULL");
        $this->addSql("SET foreign_key_checks = 0;");
        $this->addSql("ALTER TABLE message CHANGE hdr_id hdr_id INT NOT NULL");
        $this->addSql("SET foreign_key_checks = 1;");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE message_property");
        $this->addSql("ALTER TABLE header DROP labelled, DROP gmail_message_id, DROP gmail_thread_id");
        $this->addSql("ALTER TABLE message CHANGE hdr_id hdr_id INT DEFAULT NULL");
    }
}
