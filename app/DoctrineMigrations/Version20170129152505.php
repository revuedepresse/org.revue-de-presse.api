<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170129152505 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tmp_whisperer SELECT id FROM weaving_whisperer GROUP BY name HAVING count(id) > 1');
        $this->addSql('CREATE INDEX whisperer_id ON tmp_whisperer (id)');

        $this->addSql('DELETE FROM weaving_whisperer WHERE id IN (SELECT id FROM tmp_whisperer)');
        $this->addSql('DROP TABLE tmp_whisperer');

        $this->addSql('ALTER TABLE weaving_whisperer DROP INDEX name, ADD UNIQUE INDEX unique_name (name)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE weaving_whisperer DROP INDEX unique_name, ADD INDEX name (name)');
    }
}
