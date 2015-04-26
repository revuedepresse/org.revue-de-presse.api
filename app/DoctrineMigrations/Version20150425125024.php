<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150425125024 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE oauth_access_token (id INT AUTO_INCREMENT NOT NULL, client INT NOT NULL, user INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_F7FA86A45F37A13B (token), INDEX IDX_F7FA86A4C7440455 (client), INDEX IDX_F7FA86A48D93D649 (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth_auth_code (id INT AUTO_INCREMENT NOT NULL, client INT NOT NULL, user INT DEFAULT NULL, token VARCHAR(255) NOT NULL, redirect_uri LONGTEXT NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_4D12F0E05F37A13B (token), INDEX IDX_4D12F0E0C7440455 (client), INDEX IDX_4D12F0E08D93D649 (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth_client (id INT AUTO_INCREMENT NOT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', secret VARCHAR(255) NOT NULL, allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth_refresh_token (id INT AUTO_INCREMENT NOT NULL, client INT NOT NULL, user INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_55DCF7555F37A13B (token), INDEX IDX_55DCF755C7440455 (client), INDEX IDX_55DCF7558D93D649 (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE oauth_access_token ADD CONSTRAINT FK_F7FA86A4C7440455 FOREIGN KEY (client) REFERENCES oauth_client (id)');
        $this->addSql('ALTER TABLE oauth_access_token ADD CONSTRAINT FK_F7FA86A48D93D649 FOREIGN KEY (user) REFERENCES weaving_user (usr_id)');
        $this->addSql('ALTER TABLE oauth_auth_code ADD CONSTRAINT FK_4D12F0E0C7440455 FOREIGN KEY (client) REFERENCES oauth_client (id)');
        $this->addSql('ALTER TABLE oauth_auth_code ADD CONSTRAINT FK_4D12F0E08D93D649 FOREIGN KEY (user) REFERENCES weaving_user (usr_id)');
        $this->addSql('ALTER TABLE oauth_refresh_token ADD CONSTRAINT FK_55DCF755C7440455 FOREIGN KEY (client) REFERENCES oauth_client (id)');
        $this->addSql('ALTER TABLE oauth_refresh_token ADD CONSTRAINT FK_55DCF7558D93D649 FOREIGN KEY (user) REFERENCES weaving_user (usr_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE oauth_access_token DROP FOREIGN KEY FK_F7FA86A4C7440455');
        $this->addSql('ALTER TABLE oauth_auth_code DROP FOREIGN KEY FK_4D12F0E0C7440455');
        $this->addSql('ALTER TABLE oauth_refresh_token DROP FOREIGN KEY FK_55DCF755C7440455');
        $this->addSql('DROP TABLE oauth_access_token');
        $this->addSql('DROP TABLE oauth_auth_code');
        $this->addSql('DROP TABLE oauth_client');
        $this->addSql('DROP TABLE oauth_refresh_token');
    }
}
