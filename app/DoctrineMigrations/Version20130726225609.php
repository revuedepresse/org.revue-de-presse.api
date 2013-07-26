<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130726225609 extends AbstractMigration
{
    public function up(Schema $schema)
    {
//        // this up() migration is auto-generated, please modify it to your needs
//        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
//
//        $this->addSql("CREATE TABLE weaving_facebook_link (id INT AUTO_INCREMENT NOT NULL, nativeId VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, createdTime VARCHAR(255) DEFAULT NULL, updatedTime VARCHAR(255) DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, caption VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_facebook_photo (id INT AUTO_INCREMENT NOT NULL, nativeId VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, objectId VARCHAR(255) DEFAULT NULL, createdTime VARCHAR(255) DEFAULT NULL, updatedTime VARCHAR(255) DEFAULT NULL, story VARCHAR(255) DEFAULT NULL, caption VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_facebook_status (id INT AUTO_INCREMENT NOT NULL, nativeId VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, createdTime VARCHAR(255) DEFAULT NULL, updatedTime VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_facebook_video (id INT AUTO_INCREMENT NOT NULL, nativeId VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME DEFAULT NULL, story VARCHAR(255) DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, createdTime VARCHAR(255) DEFAULT NULL, updatedTime VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_perspective (per_id INT AUTO_INCREMENT NOT NULL, per_status INT DEFAULT NULL, per_type INT DEFAULT NULL, per_name VARCHAR(255) DEFAULT NULL, per_description LONGTEXT DEFAULT NULL, per_value LONGTEXT DEFAULT NULL, per_date_creation DATETIME DEFAULT NULL, per_date_update DATETIME DEFAULT NULL, PRIMARY KEY(per_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_arc (arc_id INT AUTO_INCREMENT NOT NULL, arc_status TINYINT(1) NOT NULL, arc_type INT NOT NULL, arc_source INT NOT NULL, arc_destination INT NOT NULL, INDEX etp_id (arc_type), UNIQUE INDEX arc_type (arc_type, arc_source, arc_destination), PRIMARY KEY(arc_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_author (author_id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, middle_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birthday DATE NOT NULL, birth_place_id INT NOT NULL, biography LONGTEXT NOT NULL, PRIMARY KEY(author_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_contact (cnt_id INT AUTO_INCREMENT NOT NULL, usr_id INT NOT NULL, cnt_status TINYINT(1) NOT NULL, cnt_index TINYINT(1) DEFAULT NULL, cnt_type TINYINT(1) NOT NULL, cnt_value VARCHAR(255) NOT NULL, PRIMARY KEY(cnt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_content (ctt_id INT AUTO_INCREMENT NOT NULL, rte_id INT NOT NULL, ctt_status TINYINT(1) NOT NULL, ctt_type TINYINT(1) NOT NULL, ctt_title LONGTEXT NOT NULL, ctt_subtitle VARCHAR(255) DEFAULT NULL, ctt_body LONGTEXT DEFAULT NULL, ctt_date_creation DATETIME NOT NULL, ctt_date_modification DATETIME NOT NULL, ctt_date_deletion DATETIME NOT NULL, PRIMARY KEY(ctt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_content_type (cty_id INT AUTO_INCREMENT NOT NULL, cty_name VARCHAR(255) NOT NULL, PRIMARY KEY(cty_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_edge (edg_id INT AUTO_INCREMENT NOT NULL, ety_id INT NOT NULL, edg_status TINYINT(1) NOT NULL, edg_key INT NOT NULL, UNIQUE INDEX edge_type (ety_id, edg_key), PRIMARY KEY(edg_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_entity (ety_id INT AUTO_INCREMENT NOT NULL, ety_name VARCHAR(255) NOT NULL, ety_date_creation DATETIME NOT NULL, ety_date_modification DATETIME NOT NULL, UNIQUE INDEX ety_name (ety_name), PRIMARY KEY(ety_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_entity_table (ett_id INT AUTO_INCREMENT NOT NULL, ety_id INT NOT NULL, ett_column_prefix VARCHAR(3) DEFAULT NULL, ett_table_name VARCHAR(50) NOT NULL, ett_table_alias VARCHAR(3) DEFAULT NULL, PRIMARY KEY(ett_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_entity_type (etp_id INT AUTO_INCREMENT NOT NULL, ety_id INT NOT NULL, etp_status TINYINT(1) NOT NULL, etp_default TINYINT(1) DEFAULT NULL, etp_index TINYINT(1) NOT NULL, etp_name VARCHAR(100) NOT NULL, etp_value VARCHAR(100) NOT NULL, etp_description LONGTEXT NOT NULL, UNIQUE INDEX unique_type (ety_id, etp_name, etp_value), PRIMARY KEY(etp_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_event (evt_id INT AUTO_INCREMENT NOT NULL, ety_id INT NOT NULL, etp_id INT NOT NULL, evt_occurrence INT DEFAULT NULL, evt_source INT NOT NULL, evt_target INT NOT NULL, evt_success TINYINT(1) DEFAULT NULL, evt_date DATETIME NOT NULL, evt_date_last_occurrence DATETIME DEFAULT NULL, INDEX evt_source (evt_source), PRIMARY KEY(evt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_feed (fd_id INT AUTO_INCREMENT NOT NULL, sn_uri VARCHAR(255) NOT NULL, fd_index INT NOT NULL, fd_parent_id VARCHAR(255) NOT NULL, fd_hash VARCHAR(255) NOT NULL, fd_status TINYINT(1) NOT NULL, fd_type TINYINT(1) NOT NULL, fd_title VARCHAR(255) NOT NULL, fd_contents LONGBLOB NOT NULL, fd_source LONGTEXT NOT NULL, fd_date_creation DATETIME NOT NULL, fd_date_publication DATETIME NOT NULL, PRIMARY KEY(fd_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_feedback (fdb_id INT AUTO_INCREMENT NOT NULL, fdb_type TINYINT(1) NOT NULL, fdb_status TINYINT(1) NOT NULL, fdb_hash VARCHAR(32) DEFAULT NULL, fdb_title VARCHAR(255) NOT NULL, fdb_body LONGTEXT NOT NULL, PRIMARY KEY(fdb_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_file (fil_id INT AUTO_INCREMENT NOT NULL, fld_id INT NOT NULL, fil_status TINYINT(1) NOT NULL, fil_size INT NOT NULL, fil_type TINYINT(1) NOT NULL, fil_name VARCHAR(255) NOT NULL, fil_path VARCHAR(255) NOT NULL, PRIMARY KEY(fil_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_flag (flg_id INT AUTO_INCREMENT NOT NULL, usr_id INT NOT NULL, flg_status TINYINT(1) NOT NULL, flg_type TINYINT(1) NOT NULL, flg_target INT DEFAULT NULL, flg_date_creation DATETIME NOT NULL, PRIMARY KEY(flg_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_form (frm_id INT AUTO_INCREMENT NOT NULL, str_id INT DEFAULT NULL, prv_id INT DEFAULT NULL, rte_id INT DEFAULT NULL, frm_status TINYINT(1) NOT NULL, frm_type INT NOT NULL, frm_identifier VARCHAR(150) NOT NULL, frm_title VARCHAR(150) NOT NULL, frm_config LONGTEXT DEFAULT NULL, PRIMARY KEY(frm_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_github (id INT AUTO_INCREMENT NOT NULL, rep_github_id INT NOT NULL, rep_forks INT NOT NULL, rep_watchers INT NOT NULL, rep_status INT NOT NULL, rep_owner_id INT NOT NULL, rep_owner VARCHAR(255) NOT NULL, rep_language VARCHAR(255) NOT NULL, rep_name VARCHAR(255) NOT NULL, rep_avatar_url VARCHAR(255) NOT NULL, rep_clone_url VARCHAR(255) NOT NULL, rep_description LONGTEXT NOT NULL, rep_created_at DATETIME NOT NULL, rep_updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_header (hdr_id INT AUTO_INCREMENT NOT NULL, cnt_id INT NOT NULL, rcl_id INT NOT NULL, hdr_hash VARCHAR(255) DEFAULT NULL, hdr_imap_uid INT NOT NULL, hdr_sender VARCHAR(255) DEFAULT NULL, hdr_subject VARCHAR(100) DEFAULT NULL, hdr_value LONGTEXT DEFAULT NULL, hdr_keywords VARCHAR(100) DEFAULT NULL, hdr_date_creation DATETIME NOT NULL, hdr_date_update DATETIME NOT NULL, PRIMARY KEY(hdr_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_insight (isg_id INT AUTO_INCREMENT NOT NULL, isg_status TINYINT(1) NOT NULL, ety_id TINYINT(1) NOT NULL, isg_target INT NOT NULL, isg_date_creation DATETIME NOT NULL, isg_date_modification DATETIME NOT NULL, PRIMARY KEY(isg_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_insight_moderation (imo_id INT AUTO_INCREMENT NOT NULL, isn_id INT NOT NULL, imo_status TINYINT(1) NOT NULL, imo_date_creation DATETIME NOT NULL, imo_date_modification DATETIME NOT NULL, PRIMARY KEY(imo_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_insight_node (isn_id INT AUTO_INCREMENT NOT NULL, isg_id INT NOT NULL, isn_status TINYINT(1) NOT NULL, isn_type TINYINT(1) NOT NULL, isn_parent INT NOT NULL, isn_body LONGTEXT NOT NULL, isn_date_creation DATETIME NOT NULL, isn_date_modification DATETIME NOT NULL, PRIMARY KEY(isn_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_insight_sharing (ish_id INT AUTO_INCREMENT NOT NULL, isn_id INT NOT NULL, ish_status TINYINT(1) NOT NULL, ish_type TINYINT(1) NOT NULL, ish_target INT NOT NULL, ish_date_creation DATETIME NOT NULL, ish_date_modification DATETIME NOT NULL, PRIMARY KEY(ish_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_language (lang_id INT AUTO_INCREMENT NOT NULL, lang_status TINYINT(1) NOT NULL, lang_code VARCHAR(50) NOT NULL, lang_label VARCHAR(255) NOT NULL, PRIMARY KEY(lang_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_language_item (lgi_id INT AUTO_INCREMENT NOT NULL, lang_id INT NOT NULL, nsp_id INT NOT NULL, lgi_status TINYINT(1) NOT NULL, lgi_name VARCHAR(255) NOT NULL, lgi_value VARCHAR(255) NOT NULL, PRIMARY KEY(lgi_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_link (lnk_id INT AUTO_INCREMENT NOT NULL, qry_id INT DEFAULT NULL, usr_id INT NOT NULL, lnk_status INT NOT NULL, lnk_type TINYINT(1) NOT NULL, lnk_value VARCHAR(255) NOT NULL, lnk_date_creation DATETIME NOT NULL, lnk_date_update DATETIME NOT NULL, PRIMARY KEY(lnk_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_location (location_id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, post_code VARCHAR(200) NOT NULL, country_code VARCHAR(255) NOT NULL, latitude NUMERIC(10, 0) NOT NULL, longitude NUMERIC(10, 0) NOT NULL, PRIMARY KEY(location_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_log (log_id INT AUTO_INCREMENT NOT NULL, ent_id INT NOT NULL, log_type TINYINT(1) NOT NULL, log_status TINYINT(1) NOT NULL, log_occurrence INT NOT NULL, log_message LONGTEXT DEFAULT NULL, log_context LONGBLOB DEFAULT NULL, log_exception LONGBLOB DEFAULT NULL, log_creation_date DATETIME NOT NULL, log_update_date DATETIME NOT NULL, PRIMARY KEY(log_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_message (msg_id INT AUTO_INCREMENT NOT NULL, hdr_id INT NOT NULL, msg_type INT NOT NULL, msg_hash VARCHAR(255) DEFAULT NULL, msg_body_text LONGTEXT DEFAULT NULL, msg_body_html LONGTEXT DEFAULT NULL, PRIMARY KEY(msg_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_namespace (nsp_id INT AUTO_INCREMENT NOT NULL, nsp_status TINYINT(1) NOT NULL, nsp_name VARCHAR(150) NOT NULL, PRIMARY KEY(nsp_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_outgoing (out_id INT AUTO_INCREMENT NOT NULL, out_status INT NOT NULL, out_value LONGBLOB NOT NULL, out_error_message VARCHAR(255) DEFAULT NULL, out_date_creation DATETIME NOT NULL, out_date_update DATETIME DEFAULT NULL, PRIMARY KEY(out_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_photograph (photo_id INT AUTO_INCREMENT NOT NULL, pht_status TINYINT(1) NOT NULL, author_id INT NOT NULL, licence_id INT NOT NULL, location_id INT NOT NULL, bytes LONGBLOB NOT NULL, pht_rdf LONGTEXT DEFAULT NULL, size INT NOT NULL, height INT NOT NULL, width INT NOT NULL, original_file_name VARCHAR(255) NOT NULL, title LONGTEXT DEFAULT NULL, keywords LONGTEXT DEFAULT NULL, mime_type VARCHAR(10) NOT NULL, hash VARCHAR(255) NOT NULL, pht_date_last_modification DATETIME NOT NULL, pht_date_creation DATETIME NOT NULL, PRIMARY KEY(photo_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_placeholder (plh_id INT AUTO_INCREMENT NOT NULL, plh_type INT NOT NULL, plh_status INT NOT NULL, plh_name VARCHAR(255) NOT NULL, plh_value VARCHAR(255) NOT NULL, PRIMARY KEY(plh_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_privilege (prv_id INT AUTO_INCREMENT NOT NULL, prv_type INT NOT NULL, prv_hash VARCHAR(255) DEFAULT NULL, usr_passwd VARCHAR(255) DEFAULT NULL, PRIMARY KEY(prv_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_query (qry_id INT AUTO_INCREMENT NOT NULL, qry_status INT NOT NULL, qry_type INT NOT NULL, qry_value LONGTEXT NOT NULL, qry_date_creation DATETIME NOT NULL, qry_date_update DATETIME NOT NULL, PRIMARY KEY(qry_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_recipient (rcp_id INT AUTO_INCREMENT NOT NULL, rcl_id INT NOT NULL, usr_id INT NOT NULL, cnt_id INT NOT NULL, rcp_full_name VARCHAR(255) NOT NULL, rcp_date_creation DATETIME NOT NULL, rcp_date_update DATETIME NOT NULL, PRIMARY KEY(rcp_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_recipient_list (rcl_id INT AUTO_INCREMENT NOT NULL, rcl_status TINYINT(1) NOT NULL, rcl_name VARCHAR(255) NOT NULL, rcl_date_creation DATETIME NOT NULL, rcl_date_update DATETIME NOT NULL, PRIMARY KEY(rcl_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_route (rte_id INT AUTO_INCREMENT NOT NULL, cty_id INT NOT NULL, ety_id INT DEFAULT NULL, rte_parent_hub INT NOT NULL, rte_level TINYINT(1) NOT NULL, rte_index TINYINT(1) DEFAULT NULL, rte_type TINYINT(1) NOT NULL, rte_status TINYINT(1) NOT NULL, rte_uri VARCHAR(255) NOT NULL, PRIMARY KEY(rte_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_serialization (sn_id INT AUTO_INCREMENT NOT NULL, fd_hash VARCHAR(255) NOT NULL, sn_uri VARCHAR(255) NOT NULL, sn_type TINYINT(1) NOT NULL, sn_contents LONGBLOB NOT NULL, sn_date_creation DATETIME NOT NULL, PRIMARY KEY(sn_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_snapshot (snp_id INT AUTO_INCREMENT NOT NULL, snp_type INT NOT NULL, snp_state LONGBLOB NOT NULL, PRIMARY KEY(snp_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_store (str_id INT AUTO_INCREMENT NOT NULL, etp_id INT NOT NULL, ety_id INT DEFAULT NULL, str_type INT NOT NULL, str_status INT NOT NULL, str_name VARCHAR(45) NOT NULL, PRIMARY KEY(str_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_store_item (sti_id INT AUTO_INCREMENT NOT NULL, str_id INT NOT NULL, ety_id INT DEFAULT NULL, etp_id INT DEFAULT NULL, sti_index INT DEFAULT NULL, sti_type INT NOT NULL, sti_status INT NOT NULL, sti_key INT NOT NULL, PRIMARY KEY(sti_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_stylesheet (sts_id INT AUTO_INCREMENT NOT NULL, sts_type INT NOT NULL, sts_status TINYINT(1) NOT NULL, sts_name VARCHAR(255) NOT NULL, PRIMARY KEY(sts_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_template (tpl_id INT AUTO_INCREMENT NOT NULL, lang_id TINYINT(1) NOT NULL, tpl_hash VARCHAR(32) NOT NULL, tpl_status INT NOT NULL, tpl_type TINYINT(1) NOT NULL, rte_id INT DEFAULT NULL, tpl_block VARCHAR(255) NOT NULL, tpl_contents LONGTEXT NOT NULL, tpl_modification_date DATETIME NOT NULL, PRIMARY KEY(tpl_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_token (tkn_id INT AUTO_INCREMENT NOT NULL, ety_id INT DEFAULT NULL, tkn_type INT DEFAULT NULL, tkn_status TINYINT(1) NOT NULL, tkn_value VARCHAR(255) NOT NULL, PRIMARY KEY(tkn_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_user (usr_id INT AUTO_INCREMENT NOT NULL, usr_twitter_id INT DEFAULT NULL, usr_twitter_username VARCHAR(255) DEFAULT NULL, grp_id INT DEFAULT NULL, usr_avatar INT DEFAULT NULL, usr_first_name VARCHAR(255) DEFAULT NULL, usr_full_name VARCHAR(255) DEFAULT NULL, usr_last_name VARCHAR(255) DEFAULT NULL, usr_middle_name VARCHAR(255) DEFAULT NULL, usr_phone VARCHAR(30) DEFAULT NULL, usr_status TINYINT(1) NOT NULL, usr_user_name VARCHAR(255) DEFAULT NULL, usr_username_canonical VARCHAR(255) DEFAULT NULL, usr_email VARCHAR(255) DEFAULT NULL, usr_email_canonical VARCHAR(255) DEFAULT NULL, usr_password VARCHAR(255) DEFAULT NULL, usr_password_requested_at DATETIME DEFAULT NULL, usr_salt VARCHAR(255) DEFAULT NULL, usr_locked TINYINT(1) NOT NULL, usr_credentials_expired TINYINT(1) DEFAULT NULL, usr_credentials_expires_at DATETIME DEFAULT NULL, usr_confirmation_token VARCHAR(255) DEFAULT NULL, usr_expired TINYINT(1) DEFAULT NULL, usr_expires_at DATETIME DEFAULT NULL, usr_last_login DATETIME DEFAULT NULL, PRIMARY KEY(usr_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_github_repositories (id INT AUTO_INCREMENT NOT NULL, rep_github_id INT NOT NULL, rep_forks INT NOT NULL, rep_watchers INT NOT NULL, rep_status INT NOT NULL, rep_owner_id INT NOT NULL, rep_owner VARCHAR(255) NOT NULL, rep_language VARCHAR(255) NOT NULL, rep_name VARCHAR(255) NOT NULL, rep_avatar_url VARCHAR(255) NOT NULL, rep_clone_url VARCHAR(255) NOT NULL, rep_description LONGTEXT NOT NULL, rep_created_at DATETIME NOT NULL, rep_updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_json (jsn_id INT AUTO_INCREMENT NOT NULL, jsn_status TINYINT(1) NOT NULL, jsn_type INT NOT NULL, jsn_hash VARCHAR(32) NOT NULL, jsn_value LONGTEXT NOT NULL, INDEX jsn_hash (jsn_hash), PRIMARY KEY(jsn_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
//        $this->addSql("CREATE TABLE weaving_twitter_user_stream (ust_id INT AUTO_INCREMENT NOT NULL, ust_full_name VARCHAR(32) NOT NULL, ust_name VARCHAR(32) NOT NULL, ust_text VARCHAR(140) NOT NULL, ust_avatar VARCHAR(255) NOT NULL, ust_access_token VARCHAR(255) NOT NULL, ust_created_at DATETIME NOT NULL, ust_updated_at DATETIME DEFAULT NULL, PRIMARY KEY(ust_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    public function down(Schema $schema)
    {
//        // this down() migration is auto-generated, please modify it to your needs
//        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
//
//        $this->addSql("DROP TABLE weaving_facebook_link");
//        $this->addSql("DROP TABLE weaving_facebook_photo");
//        $this->addSql("DROP TABLE weaving_facebook_status");
//        $this->addSql("DROP TABLE weaving_facebook_video");
//        $this->addSql("DROP TABLE weaving_perspective");
//        $this->addSql("DROP TABLE weaving_arc");
//        $this->addSql("DROP TABLE weaving_author");
//        $this->addSql("DROP TABLE weaving_contact");
//        $this->addSql("DROP TABLE weaving_content");
//        $this->addSql("DROP TABLE weaving_content_type");
//        $this->addSql("DROP TABLE weaving_edge");
//        $this->addSql("DROP TABLE weaving_entity");
//        $this->addSql("DROP TABLE weaving_entity_table");
//        $this->addSql("DROP TABLE weaving_entity_type");
//        $this->addSql("DROP TABLE weaving_event");
//        $this->addSql("DROP TABLE weaving_feed");
//        $this->addSql("DROP TABLE weaving_feedback");
//        $this->addSql("DROP TABLE weaving_file");
//        $this->addSql("DROP TABLE weaving_flag");
//        $this->addSql("DROP TABLE weaving_form");
//        $this->addSql("DROP TABLE weaving_github");
//        $this->addSql("DROP TABLE weaving_header");
//        $this->addSql("DROP TABLE weaving_insight");
//        $this->addSql("DROP TABLE weaving_insight_moderation");
//        $this->addSql("DROP TABLE weaving_insight_node");
//        $this->addSql("DROP TABLE weaving_insight_sharing");
//        $this->addSql("DROP TABLE weaving_language");
//        $this->addSql("DROP TABLE weaving_language_item");
//        $this->addSql("DROP TABLE weaving_link");
//        $this->addSql("DROP TABLE weaving_location");
//        $this->addSql("DROP TABLE weaving_log");
//        $this->addSql("DROP TABLE weaving_message");
//        $this->addSql("DROP TABLE weaving_namespace");
//        $this->addSql("DROP TABLE weaving_outgoing");
//        $this->addSql("DROP TABLE weaving_photograph");
//        $this->addSql("DROP TABLE weaving_placeholder");
//        $this->addSql("DROP TABLE weaving_privilege");
//        $this->addSql("DROP TABLE weaving_query");
//        $this->addSql("DROP TABLE weaving_recipient");
//        $this->addSql("DROP TABLE weaving_recipient_list");
//        $this->addSql("DROP TABLE weaving_route");
//        $this->addSql("DROP TABLE weaving_serialization");
//        $this->addSql("DROP TABLE weaving_snapshot");
//        $this->addSql("DROP TABLE weaving_store");
//        $this->addSql("DROP TABLE weaving_store_item");
//        $this->addSql("DROP TABLE weaving_stylesheet");
//        $this->addSql("DROP TABLE weaving_template");
//        $this->addSql("DROP TABLE weaving_token");
//        $this->addSql("DROP TABLE weaving_user");
//        $this->addSql("DROP TABLE weaving_github_repositories");
//        $this->addSql("DROP TABLE weaving_json");
//        $this->addSql("DROP TABLE weaving_twitter_user_stream");
    }
}
