<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150327080940 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE mail_uid CHANGE fetched body_fetched TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE mail_uid ADD CONSTRAINT FK_4F80296C2EF91FD8 FOREIGN KEY (header_id) REFERENCES header (id)");
        $this->addSql("ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FBF396750");
        $this->addSql("DROP INDEX UNIQ_B6BD307FBF396750 ON message");
        $this->addSql("ALTER TABLE message CHANGE id header_id INT NOT NULL");
        $this->addSql("ALTER TABLE message ADD CONSTRAINT FK_B6BD307F2EF91FD8 FOREIGN KEY (header_id) REFERENCES header (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_B6BD307F2EF91FD8 ON message (header_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE mail_uid DROP FOREIGN KEY FK_4F80296C2EF91FD8");
        $this->addSql("ALTER TABLE mail_uid CHANGE body_fetched fetched TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F2EF91FD8");
        $this->addSql("DROP INDEX UNIQ_B6BD307F2EF91FD8 ON message");
        $this->addSql("ALTER TABLE message CHANGE header_id id INT NOT NULL");
        $this->addSql("ALTER TABLE message ADD CONSTRAINT FK_B6BD307FBF396750 FOREIGN KEY (id) REFERENCES header (id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_B6BD307FBF396750 ON message (id)");
    }
}
