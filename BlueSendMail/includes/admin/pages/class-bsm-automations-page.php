<?php
/**
 * Gerencia a renderização da página de Automações.
 *
 * @package BlueSendMail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BSM_Automations_Page extends BSM_Admin_Page {

	public function render() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';

		if ( 'new' === $action || ( 'edit' === $action && ! empty( $_GET['automation'] ) ) ) {
			$this->render_add_edit_page();
		} else {
			$this->render_list_page();
		}
	}

	private function render_list_page() {
		?>
        <div class="wrap bsm-wrap">
            <?php
            $this->render_header(
                __( 'Automações', 'bluesendmail' ),
                array(
                    'url'   => admin_url( 'admin.php?page=bluesendmail-automations&action=new' ),
                    'label' => __( 'Criar Nova Automação', 'bluesendmail' ),
                    'icon'  => 'dashicons-plus',
                )
            );
            ?>
            <form method="post">
                <?php
                $automations_table = new BlueSendMail_Automations_List_Table();
                $automations_table->prepare_items();
                $automations_table->display();
                ?>
            </form>
        </div>
        <?php
	}

	private function render_add_edit_page() {
		global $wpdb;
		$automation_id = isset( $_GET['automation'] ) ? absint( $_GET['automation'] ) : 0;
		$automation = null;
        $steps = [];

		if ( $automation_id ) {
			$automation = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bluesendmail_automations WHERE automation_id = %d", $automation_id ) );
            $steps = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bluesendmail_automation_steps WHERE automation_id = %d ORDER BY step_order ASC", $automation_id ) );
		}

        $lists = $wpdb->get_results("SELECT list_id, name FROM {$wpdb->prefix}bluesendmail_lists ORDER BY name ASC");
        $campaigns = $wpdb->get_results("SELECT campaign_id, title FROM {$wpdb->prefix}bluesendmail_campaigns WHERE status IN ('sent', 'draft') ORDER BY title ASC");
        $tags = $wpdb->get_results("SELECT tag_id, name FROM {$wpdb->prefix}bluesendmail_tags ORDER BY name ASC");
		?>
		<div class="wrap bsm-wrap">
            <?php $this->render_header( $automation ? esc_html__( 'Editar Automação', 'bluesendmail' ) : esc_html__( 'Criar Nova Automação', 'bluesendmail' ) ); ?>
            
            <form method="post">
                <?php wp_nonce_field( 'bsm_save_automation_nonce_action', 'bsm_save_automation_nonce_field' ); ?>
                <input type="hidden" name="automation_id" value="<?php echo esc_attr( $automation_id ); ?>">

                <div class="bsm-card">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="bsm-name"><?php _e( 'Nome da Automação', 'bluesendmail' ); ?></label></th>
                                <td><input type="text" name="name" id="bsm-name" class="large-text" value="<?php echo esc_attr( $automation->name ?? '' ); ?>" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bsm-status"><?php _e( 'Status', 'bluesendmail' ); ?></label></th>
                                <td>
                                    <select name="status" id="bsm-status">
                                        <option value="active" <?php selected( $automation->status ?? 'inactive', 'active' ); ?>><?php _e( 'Ativo', 'bluesendmail' ); ?></option>
                                        <option value="inactive" <?php selected( $automation->status ?? 'inactive', 'inactive' ); ?>><?php _e( 'Inativo', 'bluesendmail' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                             <tr>
                                <th scope="row"><label for="bsm-trigger-list"><?php _e( 'Gatilho: Quando o contato for adicionado à lista...', 'bluesendmail' ); ?></label></th>
                                <td>
                                    <select name="trigger_meta" id="bsm-trigger-list" required>
                                        <option value=""><?php _e( 'Selecione uma lista...', 'bluesendmail' ); ?></option>
                                        <?php foreach($lists as $list): ?>
                                            <option value="<?php echo esc_attr($list->list_id); ?>" <?php selected($automation->trigger_meta ?? '', $list->list_id); ?>>
                                                <?php echo esc_html($list->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bsm-card" style="margin-top:24px;">
                    <h2 class="bsm-card-title"><?php _e('Sequência de Ações', 'bluesendmail'); ?></h2>
                    <div id="bsm-automation-steps-container">
                        <?php if (empty($steps)): ?>
                            <?php $this->render_step_template(0, null, $campaigns, $lists, $tags); ?>
                        <?php else: ?>
                            <?php foreach($steps as $index => $step): ?>
                                <?php $this->render_step_template($index, $step, $campaigns, $lists, $tags); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="bsm-add-automation-step" class="button button-secondary" style="margin-top:15px;">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Adicionar Passo', 'bluesendmail'); ?>
                    </button>
                </div>

                <div class="submit" style="padding-top: 20px;">
                    <?php submit_button( $automation ? __( 'Salvar Alterações', 'bluesendmail' ) : __( 'Criar Automação', 'bluesendmail' ), 'primary bsm-btn bsm-btn-primary', 'bsm_save_automation', false ); ?>
                </div>
            </form>
            
            <div id="bsm-automation-step-template" style="display:none;">
                <?php $this->render_step_template('__INDEX__', null, $campaigns, $lists, $tags, true); ?>
            </div>
        </div>
		<?php
	}

    private function render_step_template($index, $step, $campaigns, $lists, $tags, $is_template = false) {
        $step_number = is_numeric($index) ? $index + 1 : '__NUMBER__';
        $current_action = $step->action_type ?? 'send_campaign';
        ?>
        <div class="bsm-automation-step <?php echo ($index === 0) ? 'bsm-first-step' : ''; ?>" data-step-index="<?php echo esc_attr($index); ?>">
            <span class="bsm-step-reorder-handle dashicons dashicons-menu"></span>
            <div class="bsm-step-content">
                <div class="bsm-step-row">
                    <strong class="bsm-step-number"><?php echo esc_html($step_number); ?>. </strong>
                    
                    <div class="bsm-step-delay-selector" style="<?php echo ($index === 0 && !$is_template) ? 'display:none;' : ''; ?>">
                        <span>Esperar </span>
                        <input type="number" min="1" name="steps[<?php echo esc_attr($index); ?>][delay_value]" value="<?php echo esc_attr($step->delay_value ?? 1); ?>" class="bsm-delay-value">
                        <select name="steps[<?php echo esc_attr($index); ?>][delay_unit]">
                            <option value="minutes" <?php selected($step->delay_unit ?? '', 'minutes'); ?>>minuto(s)</option>
                            <option value="hours" <?php selected($step->delay_unit ?? '', 'hours'); ?>>hora(s)</option>
                            <option value="days" <?php selected($step->delay_unit ?? 'days', 'days'); ?>>dia(s)</option>
                        </select>
                        <span>, e então </span>
                    </div>

                    <div class="bsm-step-immediate-selector" style="<?php echo ($index > 0 || $is_template) ? 'display:none;' : ''; ?>">
                        <select name="steps[<?php echo esc_attr($index); ?>][delay_value]"> <option value="0">Imediatamente</option> </select>
                        <input type="hidden" name="steps[<?php echo esc_attr($index); ?>][delay_unit]" value="minutes">
                        <span> após entrar na lista, </span>
                    </div>
                    
                    <select class="bsm-action-type-selector" name="steps[<?php echo esc_attr($index); ?>][action_type]">
                        <option value="send_campaign" <?php selected($current_action, 'send_campaign'); ?>>enviar a campanha</option>
                        <option value="add_to_list" <?php selected($current_action, 'add_to_list'); ?>>adicionar à lista</option>
                        <option value="remove_from_list" <?php selected($current_action, 'remove_from_list'); ?>>remover da lista</option>
                        <option value="add_tag" <?php selected($current_action, 'add_tag'); ?>>adicionar a tag</option>
                        <option value="remove_tag" <?php selected($current_action, 'remove_tag'); ?>>remover a tag</option>
                    </select>

                    <select class="bsm-action-meta-selector bsm-action-meta-campaign" name="steps[<?php echo esc_attr($index); ?>][action_meta_campaign]" style="<?php echo ($current_action !== 'send_campaign') ? 'display:none;' : ''; ?>">
                        <option value="">Selecione a campanha...</option>
                        <?php foreach($campaigns as $campaign): ?>
                            <option value="<?php echo esc_attr($campaign->campaign_id); ?>" <?php selected($step->action_meta ?? '', $campaign->campaign_id); ?>><?php echo esc_html($campaign->title); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select class="bsm-action-meta-selector bsm-action-meta-list" name="steps[<?php echo esc_attr($index); ?>][action_meta_list]" style="<?php echo (!in_array($current_action, ['add_to_list', 'remove_from_list'])) ? 'display:none;' : ''; ?>">
                        <option value="">Selecione a lista...</option>
                        <?php foreach($lists as $list): ?>
                            <option value="<?php echo esc_attr($list->list_id); ?>" <?php selected($step->action_meta ?? '', $list->list_id); ?>><?php echo esc_html($list->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select class="bsm-action-meta-selector bsm-action-meta-tag" name="steps[<?php echo esc_attr($index); ?>][action_meta_tag]" style="<?php echo (!in_array($current_action, ['add_tag', 'remove_tag'])) ? 'display:none;' : ''; ?>">
                        <option value="">Selecione a tag...</option>
                        <?php foreach($tags as $tag): ?>
                            <option value="<?php echo esc_attr($tag->tag_id); ?>" <?php selected($step->action_meta ?? '', $tag->tag_id); ?>><?php echo esc_html($tag->name); ?></option>
                        <?php endforeach; ?>
                    </select>

                </div>
            </div>
            <button type="button" class="button bsm-remove-step dashicons dashicons-no-alt" title="<?php _e('Remover Passo', 'bluesendmail'); ?>"></button>
        </div>
        <?php
    }
}

