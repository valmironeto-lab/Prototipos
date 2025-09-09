<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BSM_Tags_Page extends BSM_Admin_Page {
    public function render() {
        ?>
        <div class="wrap bsm-wrap">
            <?php $this->render_header( __( 'Tags', 'bluesendmail' ) ); ?>
            <div id="col-container" class="wp-clearfix">
                <div id="col-left">
                    <div class="col-wrap">
                        <div class="form-wrap">
                            <h2><?php _e( 'Adicionar Nova Tag', 'bluesendmail' ); ?></h2>
                            <form method="post">
                                <?php wp_nonce_field( 'bsm_save_tag_nonce_action', 'bsm_save_tag_nonce_field' ); ?>
                                <div class="form-field">
                                    <label for="tag-name"><?php _e( 'Nome', 'bluesendmail' ); ?></label>
                                    <input name="name" id="tag-name" type="text" value="" size="40" required />
                                    <p><?php _e( 'O nome é como a tag aparece no seu site.', 'bluesendmail' ); ?></p>
                                </div>
                                <?php submit_button( __( 'Adicionar Nova Tag', 'bluesendmail' ), 'primary', 'bsm_save_tag' ); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="col-right">
                    <div class="col-wrap">
                        <form method="post">
                            <?php
                            $tags_table = new BlueSendMail_Tags_List_Table();
                            $tags_table->prepare_items();
                            $tags_table->display();
                            ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
