<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150604130834 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE weaving_search (sch_id INT AUTO_INCREMENT NOT NULL, sch_name VARCHAR(255) NOT NULL, PRIMARY KEY(sch_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, address VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message_contact (contact_id INT NOT NULL, message_id INT NOT NULL, type INT NOT NULL, INDEX IDX_DCEADC34537A1329 (message_id), INDEX IDX_DCEADC34E7A1254A (contact_id), PRIMARY KEY(contact_id, message_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message_contact ADD CONSTRAINT FK_DCEADC34537A1329 FOREIGN KEY (message_id) REFERENCES message (msg_id)');
        $this->addSql('ALTER TABLE message_contact ADD CONSTRAINT FK_DCEADC34E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE header ADD reception_date DATETIME NOT NULL, ADD parsed TINYINT(1) DEFAULT \'0\' NOT NULL, ADD cc LONGTEXT DEFAULT NULL, ADD bcc LONGTEXT DEFAULT NULL, CHANGE tofield recipient LONGTEXT DEFAULT NULL, CHANGE fromfield sender VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE message_contact DROP FOREIGN KEY FK_DCEADC34E7A1254A');
        $this->addSql('DROP TABLE weaving_search');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE message_contact');
        $this->addSql('ALTER TABLE header ADD toField LONGTEXT DEFAULT NULL, DROP reception_date, DROP parsed, DROP recipient, DROP cc, DROP bcc, CHANGE sender fromField VARCHAR(255) DEFAULT NULL');
    }
}
