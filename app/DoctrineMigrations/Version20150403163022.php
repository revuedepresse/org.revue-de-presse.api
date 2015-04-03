<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150403163022 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mail_uid CHANGE body_fetched fetched_body TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE msg_body_text text_body LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX indexed ON message (indexed)');
        $this->addSql('DROP INDEX nativeId ON weaving_facebook_link');
        $this->addSql('CREATE UNIQUE INDEX native_id ON weaving_facebook_link (native_id, feed_item_id)');
        $this->addSql('DROP INDEX nativeId ON weaving_facebook_photo');
        $this->addSql('CREATE UNIQUE INDEX native_id ON weaving_facebook_photo (native_id, feed_item_id)');
        $this->addSql('DROP INDEX nativeId ON weaving_facebook_status');
        $this->addSql('CREATE UNIQUE INDEX native_id ON weaving_facebook_status (native_id, feed_item_id)');
        $this->addSql('DROP INDEX nativeId ON weaving_facebook_video');
        $this->addSql('CREATE UNIQUE INDEX native_id ON weaving_facebook_video (native_id, feed_item_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX indexed ON message');
        $this->addSql('ALTER TABLE message CHANGE text_body msg_body_text LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX native_id ON weaving_facebook_link');
        $this->addSql('CREATE UNIQUE INDEX nativeId ON weaving_facebook_link (native_id)');
        $this->addSql('DROP INDEX native_id ON weaving_facebook_photo');
        $this->addSql('CREATE UNIQUE INDEX nativeId ON weaving_facebook_photo (native_id)');
        $this->addSql('DROP INDEX native_id ON weaving_facebook_status');
        $this->addSql('CREATE UNIQUE INDEX nativeId ON weaving_facebook_status (native_id)');
        $this->addSql('DROP INDEX native_id ON weaving_facebook_video');
        $this->addSql('CREATE UNIQUE INDEX nativeId ON weaving_facebook_video (native_id)');
    }
}
