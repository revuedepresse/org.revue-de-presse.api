<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130727093601 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE weaving_user_group (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_760325A2A76ED395 (user_id), INDEX IDX_760325A2FE54D947 (group_id), PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_group (rol_id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)', UNIQUE INDEX UNIQ_3F8565865E237E06 (name), PRIMARY KEY(rol_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE weaving_user_group ADD CONSTRAINT FK_760325A2A76ED395 FOREIGN KEY (user_id) REFERENCES weaving_user (usr_id)");
        $this->addSql("ALTER TABLE weaving_user_group ADD CONSTRAINT FK_760325A2FE54D947 FOREIGN KEY (group_id) REFERENCES weaving_group (rol_id)");
        $this->addSql("DROP INDEX jsn_hash ON weaving_json");
        $this->addSql("CREATE UNIQUE INDEX jsn_hash ON weaving_json (jsn_hash)");
        $this->addSql("CREATE INDEX jsn_status ON weaving_json (jsn_status, jsn_type)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_user_group DROP FOREIGN KEY FK_760325A2FE54D947");
        $this->addSql("DROP TABLE weaving_user_group");
        $this->addSql("DROP TABLE weaving_group");
        $this->addSql("DROP INDEX jsn_status ON weaving_json");
        $this->addSql("DROP INDEX jsn_hash ON weaving_json");
        $this->addSql("CREATE INDEX jsn_hash ON weaving_json (jsn_hash)");
    }
}
