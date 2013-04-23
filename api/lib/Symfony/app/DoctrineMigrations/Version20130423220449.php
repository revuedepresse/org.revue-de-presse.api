<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130423220449 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql('CREATE TABLE IF NOT EXISTS weaving_perspective (
            per_id INT AUTO_INCREMENT NOT NULL, per_status
            INT DEFAULT NULL, per_type INT DEFAULT NULL,
            per_value LONGTEXT DEFAULT NULL,
            per_date_creation DATETIME DEFAULT NULL,
            per_date_update DATETIME DEFAULT NULL,
            per_description LONGTEXT DEFAULT NULL,
            PRIMARY KEY(per_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        $this->addSql("DROP TABLE weaving_perspective");
    }
}
