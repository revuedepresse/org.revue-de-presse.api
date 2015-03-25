<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150325211500 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE header CHANGE gmail_message_id gmail_message_id VARCHAR(255) DEFAULT NULL, CHANGE gmail_thread_id gmail_thread_id VARCHAR(255) DEFAULT NULL, CHANGE imap_message_number imap_message_number INT DEFAULT NULL");
        $this->addSql("ALTER TABLE weaving_facebook_notification ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP createdAt, DROP updatedAt, CHANGE nativeid native_id VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE weaving_facebook_page ADD until DATETIME DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE header CHANGE gmail_message_id gmail_message_id VARCHAR(255) DEFAULT NULL, CHANGE gmail_thread_id gmail_thread_id VARCHAR(255) DEFAULT NULL, CHANGE imap_message_number imap_message_number INT DEFAULT NULL");
        $this->addSql("ALTER TABLE weaving_facebook_notification ADD createdAt DATETIME NOT NULL, ADD updatedAt DATETIME NOT NULL, DROP created_at, DROP updated_at, CHANGE native_id nativeId VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE weaving_facebook_page DROP until");
    }
}
