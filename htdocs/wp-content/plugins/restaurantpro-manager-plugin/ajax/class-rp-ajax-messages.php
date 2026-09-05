<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class RP_Ajax_Messages {
    public function register(): void { add_action( 'wp_ajax_rp_messages_poll', [ $this, 'poll' ] ); }
    public function poll(): void {
        if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message'=>'Please log in again.' ], 403 );
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_admin_nonce' ) ) wp_send_json_error( [ 'message'=>'Security check failed.' ], 403 );
        global $wpdb; $me=get_current_user_id(); $after=absint($_POST['after_id']??0); $t=$wpdb->prefix.'rp_internal_messages';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT m.id,m.sender_id,m.message,m.created_at,u.display_name FROM {$t} m LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id WHERE m.recipient_id=%d AND m.is_read=0 AND m.id>%d ORDER BY m.id ASC LIMIT 20",$me,$after),ARRAY_A);
        $count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE recipient_id=%d AND is_read=0",$me));
        wp_send_json_success(['messages'=>array_map(static function($r){return ['id'=>(int)$r['id'],'sender_id'=>(int)$r['sender_id'],'sender'=>sanitize_text_field($r['display_name']?:'Team member'),'message'=>sanitize_textarea_field($r['message']),'created_at'=>(string)$r['created_at']];},$rows?:[]),'unread'=>$count]);
    }
}
