<?php
/**
 * User: Thierry Marianne
 * Date: 5/3/13
 * Time: 3:10 AM
 */

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20130503000000
 *
 * @package Application\Migrations
 */
class Version20130503000000 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $parts = explode('\\', __CLASS__);
        $version = $parts[count($parts) - 1];
        $fixtures = file_get_contents(__DIR__ . '/fixtures/fixtures_' . $version . '.sql');
        $this->addSql($fixtures);
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
    }
}