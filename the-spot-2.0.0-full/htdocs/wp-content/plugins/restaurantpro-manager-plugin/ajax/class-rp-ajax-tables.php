<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Ajax_Tables {

    public function register(): void {
        add_action( 'wp_ajax_rp_update_table_status', [ $this, 'update_status' ] );
    }

    public function update_status(): void {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_admin_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_manage_tables' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        $table_id = absint( $_POST['table_id'] ?? 0 );
        $status   = sanitize_text_field( $_POST['status'] ?? '' );

        $valid = array_keys( RP_Helpers::get_table_statuses() );
        if ( ! $table_id || ! in_array( $status, $valid, true ) ) {
            wp_send_json_error( [ 'message' => 'Invalid parameters.' ], 400 );
        }

        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'rp_tables',
            [ 'status' => $status ],
            [ 'id' => $table_id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( false === $result ) {
            wp_send_json_error( [ 'message' => 'Database error.' ], 500 );
        }

        wp_send_json_success( [ 'message' => 'Table updated.' ] );
    }
}
