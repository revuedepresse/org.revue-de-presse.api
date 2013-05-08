<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130508145245 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_user ADD usr_username_canonical VARCHAR(255) DEFAULT NULL, ADD usr_email_canonical VARCHAR(255) DEFAULT NULL, ADD usr_salt VARCHAR(255) DEFAULT NULL, ADD usr_locked TINYINT(1) NOT NULL, ADD usr_expired TINYINT(1) DEFAULT NULL, ADD usr_credentials_expired TINYINT(1) DEFAULT NULL, ADD usr_confirmation_token VARCHAR(255) DEFAULT NULL, ADD usr_expires_at DATETIME DEFAULT NULL, ADD usr_last_login DATETIME DEFAULT NULL, ADD usr_password_requested_at DATETIME DEFAULT NULL, ADD usr_credentials_expires_at DATETIME DEFAULT NULL, CHANGE usr_twitter_id usr_twitter_id INT DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_user DROP usr_username_canonical, DROP usr_email_canonical, DROP usr_salt, DROP usr_locked, DROP usr_expired, DROP usr_credentials_expired, DROP usr_confirmation_token, DROP usr_expires_at, DROP usr_last_login, DROP usr_password_requested_at, DROP usr_credentials_expires_at, CHANGE usr_twitter_id usr_twitter_id VARCHAR(255) NOT NULL");
    }
}
