<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131103203009 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP INDEX unique_twitter_id ON weaving_user");
        $this->addSql("CREATE TABLE weaving_token_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE UNIQUE INDEX unique_twitter_id ON weaving_user (usr_twitter_id)");
        $this->addSql("ALTER TABLE weaving_access_token ADD type INT DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE weaving_access_token ADD CONSTRAINT FK_FEA6740F8CDE5729 FOREIGN KEY (type) REFERENCES weaving_token_type (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_FEA6740F8CDE5729 ON weaving_access_token (type)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_access_token DROP FOREIGN KEY FK_FEA6740F8CDE5729");
        $this->addSql("DROP TABLE weaving_token_type");
        $this->addSql("DROP INDEX UNIQ_FEA6740F8CDE5729 ON weaving_access_token");
        $this->addSql("ALTER TABLE weaving_access_token DROP type, CHANGE updated_at updated_at DATETIME NOT NULL");
        $this->addSql("DROP INDEX unique_twitter_id ON weaving_user");
    }
}
