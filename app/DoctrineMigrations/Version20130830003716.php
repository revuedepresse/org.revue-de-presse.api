<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130830003716 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE weaving_user_token (user_id INT NOT NULL, token_id INT NOT NULL, INDEX IDX_44F4C05CA76ED395 (user_id), INDEX IDX_44F4C05C41DEE7B9 (token_id), PRIMARY KEY(user_id, token_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_access_token (id INT AUTO_INCREMENT NOT NULL, oauth_token VARCHAR(255) NOT NULL, oauth_token_secret VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE weaving_user_token ADD CONSTRAINT FK_44F4C05CA76ED395 FOREIGN KEY (user_id) REFERENCES weaving_user (usr_id)");
        $this->addSql("ALTER TABLE weaving_user_token ADD CONSTRAINT FK_44F4C05C41DEE7B9 FOREIGN KEY (token_id) REFERENCES weaving_access_token (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_user_token DROP FOREIGN KEY FK_44F4C05C41DEE7B9");
        $this->addSql("DROP TABLE weaving_user_token");
        $this->addSql("DROP TABLE weaving_access_token");
    }
}
