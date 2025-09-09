<?php
/**
 * Gerencia o setup e as interações com o banco de dados.
 *
 * @package BlueSendMail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSM_DB {

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'check_database_setup' ), 5 );
	}

	public function check_database_setup() {
		if ( version_compare( get_option( 'bluesendmail_db_version', '0.0.0' ), BLUESENDMAIL_VERSION, '<' ) ) {
			self::create_database_tables();
			update_option( 'bluesendmail_db_version', BLUESENDMAIL_VERSION );
		}
	}

	public static function create_database_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tables_sql = "
            CREATE TABLE {$wpdb->prefix}bluesendmail_campaigns ( campaign_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, title varchar(255) NOT NULL, subject varchar(255) DEFAULT NULL, preheader varchar(255) DEFAULT NULL, content longtext NOT NULL, status varchar(20) NOT NULL DEFAULT 'draft', lists text DEFAULT NULL, created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', scheduled_for datetime DEFAULT NULL, sent_at datetime DEFAULT NULL, PRIMARY KEY  (campaign_id) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_contacts ( contact_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, email varchar(255) NOT NULL, first_name varchar(255) DEFAULT NULL, last_name varchar(255) DEFAULT NULL, company varchar(255) DEFAULT NULL, job_title varchar(255) DEFAULT NULL, segment varchar(255) DEFAULT NULL, status varchar(20) NOT NULL DEFAULT 'subscribed', created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY  (contact_id), UNIQUE KEY email (email) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_lists ( list_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, name varchar(255) NOT NULL, description text DEFAULT NULL, created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY  (list_id) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_contact_lists ( contact_id bigint(20) UNSIGNED NOT NULL, list_id bigint(20) UNSIGNED NOT NULL, PRIMARY KEY  (contact_id, list_id), KEY list_id (list_id) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_queue ( queue_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, campaign_id bigint(20) UNSIGNED NOT NULL, contact_id bigint(20) UNSIGNED NOT NULL, status varchar(20) NOT NULL DEFAULT 'pending', attempts tinyint(1) UNSIGNED NOT NULL DEFAULT 0, added_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY  (queue_id), KEY campaign_id (campaign_id), KEY contact_id (contact_id), KEY status (status) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_logs ( log_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, type varchar(20) NOT NULL, source varchar(50) NOT NULL, message text NOT NULL, details longtext DEFAULT NULL, created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY  (log_id), KEY type (type), KEY source (source) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_email_opens ( open_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, queue_id bigint(20) UNSIGNED NOT NULL, opened_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', ip_address varchar(100) DEFAULT NULL, user_agent varchar(255) DEFAULT NULL, PRIMARY KEY  (open_id), KEY queue_id (queue_id) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_email_clicks ( click_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, queue_id bigint(20) UNSIGNED NOT NULL, campaign_id bigint(20) UNSIGNED NOT NULL, contact_id bigint(20) UNSIGNED NOT NULL, original_url text NOT NULL, clicked_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', ip_address varchar(100) DEFAULT NULL, user_agent varchar(255) DEFAULT NULL, PRIMARY KEY  (click_id), KEY queue_id (queue_id), KEY campaign_id (campaign_id) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_templates ( template_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, name varchar(255) NOT NULL, content longtext NOT NULL, created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY  (template_id) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_automations ( automation_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, name varchar(255) NOT NULL, status varchar(20) NOT NULL DEFAULT 'inactive', trigger_type varchar(50) NOT NULL, trigger_meta bigint(20) UNSIGNED NOT NULL, created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY  (automation_id), KEY trigger_type (trigger_type), KEY status (status) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_automation_queue ( queue_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, automation_id bigint(20) UNSIGNED NOT NULL, contact_id bigint(20) UNSIGNED NOT NULL, current_step_id bigint(20) UNSIGNED NOT NULL, status varchar(20) NOT NULL DEFAULT 'waiting', process_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY  (queue_id), KEY automation_id (automation_id), KEY contact_id (contact_id), KEY status (status) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_automation_steps ( step_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, automation_id bigint(20) UNSIGNED NOT NULL, step_order int(11) NOT NULL DEFAULT 0, delay_value int(11) NOT NULL DEFAULT 0, delay_unit varchar(20) NOT NULL DEFAULT 'minutes', action_type varchar(50) NOT NULL, action_meta bigint(20) UNSIGNED NOT NULL, PRIMARY KEY (step_id), KEY automation_id (automation_id) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_tags ( tag_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, name varchar(255) NOT NULL, created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY (tag_id), UNIQUE KEY name (name) ) $charset_collate;
            CREATE TABLE {$wpdb->prefix}bluesendmail_contact_tags ( contact_id bigint(20) UNSIGNED NOT NULL, tag_id bigint(20) UNSIGNED NOT NULL, PRIMARY KEY (contact_id, tag_id), KEY tag_id (tag_id) ) $charset_collate;
        ";

        dbDelta( $tables_sql );
	}
}

