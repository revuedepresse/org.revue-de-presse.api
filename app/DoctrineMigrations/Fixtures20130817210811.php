<?php


namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Fixtures20130817210811 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $fixtures = file_get_contents(__DIR__ . '/fixtures/fixtures_legacy.sql');
        $this->addSql($fixtures);

        $fixtures = file_get_contents(__DIR__ . '/fixtures/fixtures_default_perspectives.sql');
        $this->addSql($fixtures);

        $fixtures = file_get_contents(__DIR__ . '/fixtures/fixtures_administration.sql');
        $this->addSql($fixtures);
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
    }
}
