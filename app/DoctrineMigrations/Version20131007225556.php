<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131007225556 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_twitter_user_stream ADD ust_hash VARCHAR(40) DEFAULT NULL, CHANGE ust_status_id ust_status_id VARCHAR(255) DEFAULT NULL");
        $this->addSql("CREATE INDEX hash ON weaving_twitter_user_stream (ust_hash)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP INDEX hash ON weaving_twitter_user_stream");
        $this->addSql("ALTER TABLE weaving_twitter_user_stream DROP ust_hash, CHANGE ust_status_id ust_status_id VARCHAR(255) NOT NULL");
    }
}
