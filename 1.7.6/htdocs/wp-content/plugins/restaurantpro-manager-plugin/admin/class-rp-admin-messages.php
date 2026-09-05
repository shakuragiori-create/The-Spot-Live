<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Contact-form enquiries submitted from the website.
 */
class RP_Admin_Messages {

    public function render(): void {
        global $wpdb;

        if ( ! current_user_can( 'rp_manage_reservations' ) ) {
            wp_die( 'You are not allowed to read messages.' );
        }

        $table  = $wpdb->prefix . 'rp_messages';
        $notice = '';

        if ( isset( $_POST['rp_message_action'] ) && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_message_action' ) ) {
            $id     = absint( $_POST['message_id'] ?? 0 );
            $action = sanitize_text_field( $_POST['rp_message_action'] );

            if ( $id ) {
                if ( 'delete' === $action ) {
                    $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
                    $notice = 'Message deleted.';
                } elseif ( in_array( $action, [ 'read', 'unread' ], true ) ) {
                    $wpdb->update( $table, [ 'status' => $action ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
                    $notice = 'Message marked as ' . $action . '.';
                }
            }
        }

        $status_filter = sanitize_text_field( $_GET['status'] ?? '' );
        $where         = '';
        if ( in_array( $status_filter, [ 'unread', 'read' ], true ) ) {
            $where = $wpdb->prepare( "WHERE status = %s", $status_filter );
        }

        $messages = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 100" );

        $counts = [
            'all'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
            'unread' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'unread'" ),
        ];

        include RP_PLUGIN_DIR . 'admin/views/messages.php';
    }
}
