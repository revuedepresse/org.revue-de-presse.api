<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130501120958 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_perspective ADD per_description LONGTEXT DEFAULT NULL, CHANGE per_type per_type INT DEFAULT NULL, CHANGE per_value per_value LONGTEXT DEFAULT NULL, CHANGE per_date_creation per_date_creation DATETIME DEFAULT NULL, CHANGE per_date_update per_date_update DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE weaving_user CHANGE usr_status usr_status TINYINT(1) NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_perspective DROP per_description, CHANGE per_status per_status INT NOT NULL, CHANGE per_type per_type INT NOT NULL, CHANGE per_value per_value LONGTEXT NOT NULL, CHANGE per_date_creation per_date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE per_date_update per_date_update DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
        $this->addSql("ALTER TABLE weaving_user CHANGE usr_status usr_status TINYINT(1) DEFAULT NULL");
    }
}
