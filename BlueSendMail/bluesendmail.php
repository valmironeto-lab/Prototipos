<?php
/**
 * Plugin Name:       BlueSendMail
 * Plugin URI:        https://blueagenciadigital.com.br/bluesendmail
 * Description:       Uma plataforma de e-mail marketing e automação nativa do WordPress para gerenciar contatos, criar campanhas e garantir alta entregabilidade.
 * Version:           1.9.5
 * Author:            Blue Mkt Digital
 * Author URI:        https://blueagenciadigital.com.br/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bluesendmail
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLUESENDMAIL_VERSION', '1.9.5' );
define( 'BLUESENDMAIL_PLUGIN_FILE', __FILE__ );
define( 'BLUESENDMAIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLUESENDMAIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// --- Lógica de Ativação e Desativação ---
function bluesendmail_activate() {
	require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/core/class-bsm-db.php';
	BSM_DB::create_database_tables();
	$administrator = get_role( 'administrator' );
	if ( $administrator ) {
		$caps = array('bsm_manage_campaigns', 'bsm_manage_contacts', 'bsm_manage_lists', 'bsm_view_reports', 'bsm_manage_settings');
		foreach ( $caps as $cap ) { $administrator->add_cap( $cap ); }
	}
	if ( ! wp_next_scheduled( 'bsm_process_sending_queue' ) ) {
		$options = get_option( 'bluesendmail_settings', array() );
		wp_schedule_event( time(), $options['cron_interval'] ?? 'every_five_minutes', 'bsm_process_sending_queue' );
	}
	if ( ! wp_next_scheduled( 'bsm_check_scheduled_campaigns' ) ) {
		wp_schedule_event( time(), 'every_five_minutes', 'bsm_check_scheduled_campaigns' );
	}
    if ( ! wp_next_scheduled( 'bsm_process_automation_queue' ) ) {
        wp_schedule_event( time(), 'every_five_minutes', 'bsm_process_automation_queue' );
    }
	flush_rewrite_rules();
}
function bluesendmail_deactivate() {
	wp_clear_scheduled_hook( 'bsm_process_sending_queue' );
	wp_clear_scheduled_hook( 'bsm_check_scheduled_campaigns' );
    wp_clear_scheduled_hook( 'bsm_process_automation_queue' );
	flush_rewrite_rules();
}
register_activation_hook( BLUESENDMAIL_PLUGIN_FILE, 'bluesendmail_activate' );
register_deactivation_hook( BLUESENDMAIL_PLUGIN_FILE, 'bluesendmail_deactivate' );

// --- Carregamento dos Arquivos do Plugin ---
require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/core/class-bsm-db.php';
require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/core/class-bsm-cron.php';
require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/core/class-bsm-automations.php';
require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/core/class-bsm-admin.php';
require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/interfaces/interface-bsm-mailer.php';
require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/mailers/class-bsm-wp-mail-mailer.php';
require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/mailers/class-bsm-smtp-mailer.php';
require_once BLUESENDMAIL_PLUGIN_DIR . 'includes/mailers/class-bsm-sendgrid-mailer.php';

// --- Classe Principal do Plugin ---
final class BlueSendMail {
	private static $_instance = null;
	public $options = array();
	public $mail_error = '';
	private $current_queue_id_for_tracking = 0;
	private $mailer;
	public $db;
	public $cron;
    public $automations;
	public $admin;

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) { self::$_instance = new self(); }
		return self::$_instance;
	}

	private function __construct() {
		$this->load_options();
		$this->instantiate_classes();
		$this->register_hooks();
	}

	private function load_options() {
		$this->options = get_option( 'bluesendmail_settings', array() );
	}

	private function instantiate_classes() {
		$this->db   = new BSM_DB();
		$this->cron = new BSM_Cron( $this );
        $this->automations = new BSM_Automations( $this );
		if ( is_admin() ) { $this->admin = new BSM_Admin( $this ); }
	}

	private function register_hooks() {
		add_action( 'init', array( $this, 'handle_public_actions' ) );
	}

	public function get_mailer() { /* ...código existente... */ }
	public function send_email( $to_email, $subject, $body, $contact, $queue_id ) { /* ...código existente... */ }
	private function _replace_links_callback( $matches ) { /* ...código existente... */ }
	public function handle_public_actions() { /* ...código existente... */ }
	private function handle_click_tracking() { /* ...código existente... */ }
	private function handle_tracking_pixel() { /* ...código existente... */ }
	private function handle_unsubscribe_request() { /* ...código existente... */ }
	public function log_event( $type, $source, $message, $details = '' ) { /* ...código existente... */ }
	public function bsm_get_timezone() { /* ...código existente... */ }
	public function enqueue_campaign_recipients( $campaign_id ) { /* ...código existente... */ }

	public function load_list_tables() {
		$path = BLUESENDMAIL_PLUGIN_DIR . 'includes/tables/';
		$tables = ['campaigns', 'contacts', 'lists', 'forms', 'logs', 'reports', 'clicks', 'templates', 'automations', 'tags'];
        foreach($tables as $table){
            $file = $path . "class-bluesendmail-{$table}-list-table.php";
            if(file_exists($file)){
                require_once $file;
            }
        }
	}
}

function bluesendmail_init() {
	BlueSendMail::get_instance();
}
add_action( 'plugins_loaded', 'bluesendmail_init' );

