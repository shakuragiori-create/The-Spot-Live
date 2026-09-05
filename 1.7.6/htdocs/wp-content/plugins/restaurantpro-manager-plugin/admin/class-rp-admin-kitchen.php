<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Admin_Kitchen {

    public function render(): void {
        global $wpdb;

        $kots = $wpdb->get_results(
            "SELECT k.*, o.table_number, o.notes as order_notes, o.status as order_status
             FROM {$wpdb->prefix}rp_kot k
             JOIN {$wpdb->prefix}rp_orders o ON o.id = k.order_id
             WHERE k.status IN ('pending','preparing','ready')
             ORDER BY k.created_at ASC"
        );

        foreach ( $kots as &$kot ) {
            $kot->items = $wpdb->get_results( $wpdb->prepare(
                "SELECT oi.*, p.post_title
                 FROM {$wpdb->prefix}rp_order_items oi
                 JOIN {$wpdb->posts} p ON p.ID = oi.menu_item_id
                 WHERE oi.order_id = %d",
                $kot->order_id
            ) );
        }
        unset( $kot );

        include RP_PLUGIN_DIR . 'admin/views/kitchen.php';
    }
}
