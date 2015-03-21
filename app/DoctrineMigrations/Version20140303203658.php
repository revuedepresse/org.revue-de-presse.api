<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140303203658 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_message ADD CONSTRAINT FK_21806EA261E1892C FOREIGN KEY (hdr_id) REFERENCES weaving_header (hdr_id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_21806EA261E1892C ON weaving_message (hdr_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_message DROP FOREIGN KEY FK_21806EA261E1892C");
        $this->addSql("DROP INDEX UNIQ_21806EA261E1892C ON weaving_message");
    }
}
