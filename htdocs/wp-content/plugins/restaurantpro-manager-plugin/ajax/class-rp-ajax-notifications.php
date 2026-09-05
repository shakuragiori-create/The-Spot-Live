<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class RP_Ajax_Notifications {
    public function register(): void { add_action( 'wp_ajax_rp_notifications_poll', [ $this, 'poll' ] ); }
    public function poll(): void {
        if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message'=>'Please log in again.' ], 403 );
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_admin_nonce' ) ) wp_send_json_error( [ 'message'=>'Security check failed.' ], 403 );
        $after = absint( $_POST['after_id'] ?? 0 );
        wp_send_json_success( [ 'events'=>RP_Notifications::latest($after,30), 'newest_id'=>RP_Notifications::newest_id(), 'server_now'=>current_time('mysql') ] );
    }
}
