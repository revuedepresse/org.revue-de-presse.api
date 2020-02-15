<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200214052816 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remove group and role tables';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->skipIf(!$schema->hasTable('weaving_group'));
        $this->addSql('DROP TABLE weaving_group');

        $this->skipIf(!$schema->hasTable('weaving_role'));
        $this->addSql('DROP TABLE weaving_role');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->skipIf($schema->hasTable('weaving_role'));
        $this->addSql('CREATE TABLE weaving_group (rol_id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(rol_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->skipIf($schema->hasTable('weaving_group'));
        $this->addSql('CREATE TABLE weaving_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, role VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_7AAAFFD957698A6A (role), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }
}
