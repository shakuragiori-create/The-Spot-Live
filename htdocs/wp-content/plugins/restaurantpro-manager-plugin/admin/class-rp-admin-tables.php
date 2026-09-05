<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Admin_Tables {

    public function render(): void {
        global $wpdb;

        if ( isset( $_POST['rp_add_table'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'rp_add_table' ) && current_user_can( 'rp_manage_tables' ) ) {
            $name     = sanitize_text_field( $_POST['table_name'] ?? '' );
            $capacity = absint( $_POST['capacity'] ?? 4 );
            if ( $name ) {
                $wpdb->insert(
                    $wpdb->prefix . 'rp_tables',
                    [ 'table_name' => $name, 'capacity' => $capacity, 'status' => 'available' ],
                    [ '%s', '%d', '%s' ]
                );
            }
        }

        if ( isset( $_GET['delete_table'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'rp_delete_table' ) && current_user_can( 'rp_manage_tables' ) ) {
            $wpdb->delete( $wpdb->prefix . 'rp_tables', [ 'id' => absint( $_GET['delete_table'] ) ], [ '%d' ] );
        }

        $tables = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rp_tables ORDER BY id ASC" );

        include RP_PLUGIN_DIR . 'admin/views/tables.php';
    }
}
