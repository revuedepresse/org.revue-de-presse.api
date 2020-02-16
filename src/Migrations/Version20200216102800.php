<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200216102800 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create the publication table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE publication_frequency DROP FOREIGN KEY FK_3A3CBE841B1FEA20');
        $this->addSql('CREATE TABLE publication (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', legacy_id INT DEFAULT NULL, hash VARCHAR(64) NOT NULL, screen_name VARCHAR(32) NOT NULL, text LONGTEXT NOT NULL, document_id VARCHAR(255) NOT NULL, document LONGTEXT NOT NULL, published_at DATETIME NOT NULL, INDEX idx_publication (hash, screen_name, document_id, published_at), UNIQUE INDEX unique_hash (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE member_identity IF EXISTS');
        $this->addSql('DROP TABLE publication_frequency IF EXISTS');
        $this->addSql('DROP TABLE sample IF EXISTS');
        $this->addSql('DROP TABLE weaving_group IF EXISTS');
        $this->addSql('DROP TABLE weaving_role IF EXISTS');
        $this->addSql('DROP INDEX status_id ON weaving_status');
        $this->addSql('CREATE INDEX status_id ON weaving_status (ust_status_id)');
        $this->addSql('DROP INDEX name ON weaving_aggregate');
        $this->addSql('CREATE INDEX name ON weaving_aggregate (name)');
        $this->addSql('DROP INDEX indexed ON weaving_archived_status');
        $this->addSql('ALTER TABLE weaving_archived_status_aggregate ADD CONSTRAINT FK_6C6940DA6BF700BD FOREIGN KEY (status_id) REFERENCES weaving_archived_status (ust_id)');
        $this->addSql('ALTER TABLE member_subscription DROP has_been_cancelled');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE member_identity (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', member_id INT DEFAULT NULL, twitter_id VARCHAR(190) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, screen_name VARCHAR(190) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_9D484A2E7597D3FE (member_id), UNIQUE INDEX unique_member (member_id, screen_name, twitter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE publication_frequency (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', member_id INT DEFAULT NULL, sample_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', per_day_of_week LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, per_hour_of_day LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, per_day_of_week_percentage LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, per_hour_of_day_percentage LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated_at DATETIME NOT NULL, INDEX frequency_index (member_id, sample_id, updated_at), INDEX IDX_3A3CBE847597D3FE (member_id), INDEX IDX_3A3CBE841B1FEA20 (sample_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sample (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', label LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX sample_idx (id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE weaving_group (rol_id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(rol_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE weaving_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, role VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_7AAAFFD957698A6A (role), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE member_identity ADD CONSTRAINT FK_9D484A2E7597D3FE FOREIGN KEY (member_id) REFERENCES weaving_user (usr_id)');
        $this->addSql('ALTER TABLE publication_frequency ADD CONSTRAINT FK_3A3CBE841B1FEA20 FOREIGN KEY (sample_id) REFERENCES sample (id)');
        $this->addSql('ALTER TABLE publication_frequency ADD CONSTRAINT FK_3A3CBE847597D3FE FOREIGN KEY (member_id) REFERENCES weaving_user (usr_id)');
        $this->addSql('DROP TABLE publication');
        $this->addSql('ALTER TABLE member_subscription ADD has_been_cancelled TINYINT(1) DEFAULT NULL');
        $this->addSql('DROP INDEX name ON weaving_aggregate');
        $this->addSql('CREATE INDEX name ON weaving_aggregate (name(191), screen_name(191))');
        $this->addSql('CREATE INDEX indexed ON weaving_archived_status (ust_indexed)');
        $this->addSql('ALTER TABLE weaving_archived_status_aggregate DROP FOREIGN KEY FK_6C6940DA6BF700BD');
        $this->addSql('DROP INDEX status_id ON weaving_status');
        $this->addSql('CREATE INDEX status_id ON weaving_status (ust_status_id(191))');
    }
}
