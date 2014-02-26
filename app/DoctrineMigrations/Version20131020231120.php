<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131020231120 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP INDEX unique_hash ON weaving_twitter_user_stream");
        $this->addSql("CREATE INDEX hash ON weaving_twitter_user_stream (ust_hash)");
        $this->addSql("CREATE UNIQUE INDEX unique_hash ON weaving_twitter_user_stream (ust_hash, ust_access_token, ust_full_name)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP INDEX hash ON weaving_twitter_user_stream");
        $this->addSql("DROP INDEX unique_hash ON weaving_twitter_user_stream");
        $this->addSql("CREATE UNIQUE INDEX unique_hash ON weaving_twitter_user_stream (ust_hash)");
    }
}
