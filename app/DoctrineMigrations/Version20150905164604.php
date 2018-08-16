<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150905164604 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE weaving_aggregate (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weaving_status_aggregate (status_id INT NOT NULL, aggregate_id INT NOT NULL, INDEX IDX_53DF6C4D6BF700BD (status_id), INDEX IDX_53DF6C4DD0BBCCBE (aggregate_id), PRIMARY KEY(status_id, aggregate_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE weaving_status_aggregate ADD CONSTRAINT FK_53DF6C4D6BF700BD FOREIGN KEY (status_id) REFERENCES weaving_twitter_user_stream (ust_id)');
        $this->addSql('ALTER TABLE weaving_status_aggregate ADD CONSTRAINT FK_53DF6C4DD0BBCCBE FOREIGN KEY (aggregate_id) REFERENCES weaving_aggregate (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE weaving_status_aggregate DROP FOREIGN KEY FK_53DF6C4DD0BBCCBE');
        $this->addSql('DROP TABLE weaving_aggregate');
        $this->addSql('DROP TABLE weaving_status_aggregate');
    }
}
