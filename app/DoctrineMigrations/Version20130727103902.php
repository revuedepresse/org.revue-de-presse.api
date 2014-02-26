<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130727103902 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE weaving_user_role (user_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_95AD963FA76ED395 (user_id), INDEX IDX_95AD963FD60322AC (role_id), PRIMARY KEY(user_id, role_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(30) NOT NULL, role VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_7AAAFFD957698A6A (role), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE weaving_user_role ADD CONSTRAINT FK_95AD963FA76ED395 FOREIGN KEY (user_id) REFERENCES weaving_user (usr_id)");
        $this->addSql("ALTER TABLE weaving_user_role ADD CONSTRAINT FK_95AD963FD60322AC FOREIGN KEY (role_id) REFERENCES weaving_role (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_user_role DROP FOREIGN KEY FK_95AD963FD60322AC");
        $this->addSql("DROP TABLE weaving_user_role");
        $this->addSql("DROP TABLE weaving_role");
    }
}
