<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150321225453 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE weaving_facebook_link");
        $this->addSql("DROP TABLE weaving_facebook_photo");
        $this->addSql("DROP TABLE weaving_facebook_status");
        $this->addSql("DROP TABLE weaving_facebook_video");

        $this->addSql("CREATE TABLE weaving_facebook_album (id INT AUTO_INCREMENT NOT NULL, native_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, link VARCHAR(255) NOT NULL, privacy VARCHAR(255) NOT NULL, count INT NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, can_upload TINYINT(1) NOT NULL, UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_feed_item (id INT AUTO_INCREMENT NOT NULL, native_id VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, json LONGTEXT NOT NULL, hash VARCHAR(40) NOT NULL, transformed TINYINT(1) DEFAULT '0' NOT NULL, INDEX feed_item_type (type), UNIQUE INDEX hash (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_friend_list (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, native_id VARCHAR(255) NOT NULL, read_at DATETIME NOT NULL, synced_at DATETIME DEFAULT NULL, UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, native_id VARCHAR(255) NOT NULL, administrator TINYINT(1) NOT NULL, json LONGTEXT NOT NULL, hash VARCHAR(40) NOT NULL, synced_at DATETIME DEFAULT NULL, kept_in_sync TINYINT(1) DEFAULT '1' NOT NULL, UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_group_feed_item (id INT AUTO_INCREMENT NOT NULL, native_id VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, json LONGTEXT NOT NULL, hash VARCHAR(40) NOT NULL, transformed TINYINT(1) DEFAULT '0' NOT NULL, INDEX group_feed_item_type (type), UNIQUE INDEX hash (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_link (id INT AUTO_INCREMENT NOT NULL, feed_item_id INT DEFAULT NULL, group_feed_item_id INT DEFAULT NULL, native_id VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, caption VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_F6CB6A6EA87D462B (feed_item_id), UNIQUE INDEX UNIQ_F6CB6A6E443B0C12 (group_feed_item_id), UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_notification (id INT AUTO_INCREMENT NOT NULL, nativeId VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, title VARCHAR(255) NOT NULL, link LONGTEXT NOT NULL, unread TINYINT(1) NOT NULL, hash VARCHAR(40) NOT NULL, json LONGTEXT NOT NULL, UNIQUE INDEX hash (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_page (id INT AUTO_INCREMENT NOT NULL, node_id VARCHAR(255) DEFAULT NULL, url LONGTEXT DEFAULT NULL, type VARCHAR(30) DEFAULT NULL, hash VARCHAR(40) NOT NULL, visited TINYINT(1) NOT NULL, visited_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX hash (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_photo (id INT AUTO_INCREMENT NOT NULL, feed_item_id INT DEFAULT NULL, group_feed_item_id INT DEFAULT NULL, native_id VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, objectId VARCHAR(255) DEFAULT NULL, story VARCHAR(255) DEFAULT NULL, caption VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_74C76D3EA87D462B (feed_item_id), UNIQUE INDEX UNIQ_74C76D3E443B0C12 (group_feed_item_id), UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_relative (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, relationship VARCHAR(255) NOT NULL, native_id VARCHAR(255) NOT NULL, UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_status (id INT AUTO_INCREMENT NOT NULL, feed_item_id INT DEFAULT NULL, group_feed_item_id INT DEFAULT NULL, native_id VARCHAR(255) NOT NULL, message VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A96D9008A87D462B (feed_item_id), UNIQUE INDEX UNIQ_A96D9008443B0C12 (group_feed_item_id), UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_thread (id INT AUTO_INCREMENT NOT NULL, native_id VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL, unread TINYINT(1) NOT NULL, unseen TINYINT(1) NOT NULL, json LONGTEXT NOT NULL, hash VARCHAR(40) NOT NULL, UNIQUE INDEX hash (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_user (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, full_name VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, birthday VARCHAR(255) DEFAULT NULL, native_id VARCHAR(255) NOT NULL, UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_user_friendlist (friendlist_id INT NOT NULL, member_id INT NOT NULL, INDEX IDX_989BE8DD15860661 (friendlist_id), INDEX IDX_989BE8DD7597D3FE (member_id), PRIMARY KEY(friendlist_id, member_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE weaving_facebook_video (id INT AUTO_INCREMENT NOT NULL, feed_item_id INT DEFAULT NULL, group_feed_item_id INT DEFAULT NULL, native_id VARCHAR(255) NOT NULL, story VARCHAR(255) DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1CB7330AA87D462B (feed_item_id), UNIQUE INDEX UNIQ_1CB7330A443B0C12 (group_feed_item_id), UNIQUE INDEX nativeId (native_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE weaving_facebook_link ADD CONSTRAINT FK_F6CB6A6EA87D462B FOREIGN KEY (feed_item_id) REFERENCES weaving_facebook_feed_item (id)");
        $this->addSql("ALTER TABLE weaving_facebook_link ADD CONSTRAINT FK_F6CB6A6E443B0C12 FOREIGN KEY (group_feed_item_id) REFERENCES weaving_facebook_group_feed_item (id)");
        $this->addSql("ALTER TABLE weaving_facebook_photo ADD CONSTRAINT FK_74C76D3EA87D462B FOREIGN KEY (feed_item_id) REFERENCES weaving_facebook_feed_item (id)");
        $this->addSql("ALTER TABLE weaving_facebook_photo ADD CONSTRAINT FK_74C76D3E443B0C12 FOREIGN KEY (group_feed_item_id) REFERENCES weaving_facebook_group_feed_item (id)");
        $this->addSql("ALTER TABLE weaving_facebook_status ADD CONSTRAINT FK_A96D9008A87D462B FOREIGN KEY (feed_item_id) REFERENCES weaving_facebook_feed_item (id)");
        $this->addSql("ALTER TABLE weaving_facebook_status ADD CONSTRAINT FK_A96D9008443B0C12 FOREIGN KEY (group_feed_item_id) REFERENCES weaving_facebook_group_feed_item (id)");
        $this->addSql("ALTER TABLE weaving_facebook_user_friendlist ADD CONSTRAINT FK_989BE8DD15860661 FOREIGN KEY (friendlist_id) REFERENCES weaving_facebook_user (id)");
        $this->addSql("ALTER TABLE weaving_facebook_user_friendlist ADD CONSTRAINT FK_989BE8DD7597D3FE FOREIGN KEY (member_id) REFERENCES weaving_facebook_friend_list (id)");
        $this->addSql("ALTER TABLE weaving_facebook_video ADD CONSTRAINT FK_1CB7330AA87D462B FOREIGN KEY (feed_item_id) REFERENCES weaving_facebook_feed_item (id)");
        $this->addSql("ALTER TABLE weaving_facebook_video ADD CONSTRAINT FK_1CB7330A443B0C12 FOREIGN KEY (group_feed_item_id) REFERENCES weaving_facebook_group_feed_item (id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE weaving_facebook_link DROP FOREIGN KEY FK_F6CB6A6EA87D462B");
        $this->addSql("ALTER TABLE weaving_facebook_photo DROP FOREIGN KEY FK_74C76D3EA87D462B");
        $this->addSql("ALTER TABLE weaving_facebook_status DROP FOREIGN KEY FK_A96D9008A87D462B");
        $this->addSql("ALTER TABLE weaving_facebook_video DROP FOREIGN KEY FK_1CB7330AA87D462B");
        $this->addSql("ALTER TABLE weaving_facebook_user_friendlist DROP FOREIGN KEY FK_989BE8DD7597D3FE");
        $this->addSql("ALTER TABLE weaving_facebook_link DROP FOREIGN KEY FK_F6CB6A6E443B0C12");
        $this->addSql("ALTER TABLE weaving_facebook_photo DROP FOREIGN KEY FK_74C76D3E443B0C12");
        $this->addSql("ALTER TABLE weaving_facebook_status DROP FOREIGN KEY FK_A96D9008443B0C12");
        $this->addSql("ALTER TABLE weaving_facebook_video DROP FOREIGN KEY FK_1CB7330A443B0C12");
        $this->addSql("ALTER TABLE weaving_facebook_user_friendlist DROP FOREIGN KEY FK_989BE8DD15860661");
        $this->addSql("DROP TABLE weaving_facebook_album");
        $this->addSql("DROP TABLE weaving_facebook_feed_item");
        $this->addSql("DROP TABLE weaving_facebook_friend_list");
        $this->addSql("DROP TABLE weaving_facebook_group");
        $this->addSql("DROP TABLE weaving_facebook_group_feed_item");
        $this->addSql("DROP TABLE weaving_facebook_link");
        $this->addSql("DROP TABLE weaving_facebook_notification");
        $this->addSql("DROP TABLE weaving_facebook_page");
        $this->addSql("DROP TABLE weaving_facebook_photo");
        $this->addSql("DROP TABLE weaving_facebook_relative");
        $this->addSql("DROP TABLE weaving_facebook_status");
        $this->addSql("DROP TABLE weaving_facebook_thread");
        $this->addSql("DROP TABLE weaving_facebook_user");
        $this->addSql("DROP TABLE weaving_facebook_user_friendlist");
        $this->addSql("DROP TABLE weaving_facebook_video");
    }
}
