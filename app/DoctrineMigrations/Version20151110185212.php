<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151110185212 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE weaving_oauth_client ADD user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE weaving_oauth_client ADD CONSTRAINT FK_E5778E2A8D93D649 FOREIGN KEY (user) REFERENCES weaving_user (usr_id)');
        $this->addSql('CREATE INDEX IDX_E5778E2A8D93D649 ON weaving_oauth_client (user)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE weaving_oauth_client DROP FOREIGN KEY FK_E5778E2A8D93D649');
        $this->addSql('DROP INDEX IDX_E5778E2A8D93D649 ON weaving_oauth_client');
        $this->addSql('ALTER TABLE weaving_oauth_client DROP user');
    }
}
