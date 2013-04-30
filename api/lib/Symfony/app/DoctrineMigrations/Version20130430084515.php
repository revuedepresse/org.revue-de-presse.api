<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130430084515 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE weaving_user CHANGE grp_id grp_id INT DEFAULT NULL, CHANGE usr_status usr_status TINYINT(1) DEFAULT NULL, CHANGE usr_avatar usr_avatar INT DEFAULT NULL, CHANGE usr_last_name usr_last_name VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE weaving_user CHANGE grp_id grp_id INT NOT NULL, CHANGE usr_status usr_status TINYINT(1) NOT NULL, CHANGE usr_avatar usr_avatar INT NOT NULL, CHANGE usr_last_name usr_last_name VARCHAR(255) NOT NULL");
    }
}
