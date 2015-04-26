<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150406184411 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE weaving_facebook_message (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, thread_id INT DEFAULT NULL, native_id VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_ACB589F7F675F31B (author_id), INDEX IDX_ACB589F7E2904019 (thread_id), UNIQUE INDEX message_native_id (native_id, thread_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE weaving_facebook_message ADD CONSTRAINT FK_ACB589F7F675F31B FOREIGN KEY (author_id) REFERENCES weaving_facebook_user (id)');
        $this->addSql('ALTER TABLE weaving_facebook_message ADD CONSTRAINT FK_ACB589F7E2904019 FOREIGN KEY (thread_id) REFERENCES weaving_facebook_thread (id)');
        $this->addSql('ALTER TABLE weaving_facebook_thread ADD transformed TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE weaving_facebook_message');
        $this->addSql('ALTER TABLE weaving_facebook_thread DROP transformed');
    }
}
