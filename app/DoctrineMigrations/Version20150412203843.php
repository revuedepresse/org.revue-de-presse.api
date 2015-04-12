<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150412203843 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE weaving_access_token DROP INDEX UNIQ_FEA6740F8CDE5729, ADD INDEX IDX_FEA6740F8CDE5729 (type)');
        $this->addSql('UPDATE weaving_access_token SET type = 1 WHERE type IS NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE weaving_access_token DROP INDEX IDX_FEA6740F8CDE5729, ADD UNIQUE INDEX UNIQ_FEA6740F8CDE5729 (type)');
    }
}
