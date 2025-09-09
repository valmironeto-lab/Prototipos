<?php
/**
 * Gerencia a lógica principal das automações.
 *
 * @package BlueSendMail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSM_Automations {

    private $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->register_hooks();
    }

    private function register_hooks() {
        add_action( 'bsm_contact_added_to_list', array( $this, 'handle_contact_added_to_list' ), 10, 2 );
    }

    public function handle_contact_added_to_list( $contact_id, $list_id ) {
        global $wpdb;

        $automations = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bluesendmail_automations 
             WHERE status = 'active' 
             AND trigger_type = 'contact_added_to_list' 
             AND trigger_meta = %d",
            $list_id
        ) );

        if ( empty( $automations ) ) {
            return;
        }

        foreach ( $automations as $automation ) {
            // Pega o primeiro passo da automação
            $first_step = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bluesendmail_automation_steps WHERE automation_id = %d ORDER BY step_order ASC LIMIT 1",
                $automation->automation_id
            ));

            if ( $first_step ) {
                $process_at = current_time('mysql', 1); // Passo 1 é imediato

                $wpdb->insert(
                    "{$wpdb->prefix}bluesendmail_automation_queue",
                    array(
                        'automation_id' => $automation->automation_id,
                        'contact_id'    => $contact_id,
                        'current_step_id' => $first_step->step_id,
                        'status'        => 'waiting',
                        'process_at'    => $process_at,
                        'created_at'    => $process_at,
                    )
                );

                $this->plugin->log_event('info', 'automation_trigger', sprintf('Contato #%d iniciou a automação #%d (%s).', $contact_id, $automation->automation_id, $automation->name));
            }
        }
    }
}

