<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'WP_List_Table' ) ) { require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'; }

class BlueSendMail_Tags_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => __( 'Tag', 'bluesendmail' ),
            'plural'   => __( 'Tags', 'bluesendmail' ),
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'       => '<input type="checkbox" />',
            'name'     => __( 'Nome', 'bluesendmail' ),
            'contacts' => __( 'Contatos', 'bluesendmail' ),
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_tags = $wpdb->prefix . 'bluesendmail_tags';
        $table_contact_tags = $wpdb->prefix . 'bluesendmail_contact_tags';

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $sql = "SELECT t.tag_id, t.name, COUNT(ct.contact_id) as contacts
                FROM {$table_tags} AS t
                LEFT JOIN {$table_contact_tags} AS ct ON t.tag_id = ct.tag_id
                GROUP BY t.tag_id, t.name
                ORDER BY t.name ASC
                LIMIT %d OFFSET %d";
        
        $this->items = $wpdb->get_results( $wpdb->prepare( $sql, $per_page, $offset ), ARRAY_A );
        $total_items = $wpdb->get_var( "SELECT COUNT(tag_id) FROM $table_tags" );

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);
        $this->_column_headers = array( $this->get_columns(), [], [] );
    }

    protected function column_default( $item, $column_name ) {
        return esc_html( $item[ $column_name ] );
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="tag[]" value="%s" />', $item['tag_id'] );
    }

    protected function column_name( $item ) {
        $delete_nonce = wp_create_nonce( 'bsm_delete_tag_' . $item['tag_id'] );
        $delete_url = admin_url( 'admin.php?page=bluesendmail-tags&action=delete&tag=' . $item['tag_id'] . '&_wpnonce=' . $delete_nonce );
        $actions = [
            'delete' => sprintf( '<a href="%s" style="color:red;" onclick="return confirm(\'Tem a certeza?\');">%s</a>', esc_url( $delete_url ), __( 'Excluir', 'bluesendmail' ) ),
        ];
        return sprintf( '<strong>%1$s</strong> %2$s', esc_html( $item['name'] ), $this->row_actions( $actions ) );
    }
}
