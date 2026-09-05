<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Admin_Dashboard {

    public function render(): void {
        global $wpdb;

        $today = current_time( 'Y-m-d' );

        $today_orders = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_orders WHERE DATE(created_at) = %s",
            $today
        ) );

        $today_revenue = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM {$wpdb->prefix}rp_orders WHERE DATE(created_at) = %s AND status != 'cancelled'",
            $today
        ) );

        $pending_kots = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_kot WHERE status IN ('pending','preparing')"
        );

        $pending_reservations = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_reservations WHERE status = 'pending'"
        );

        $popular_items = $wpdb->get_results(
            "SELECT oi.menu_item_id, p.post_title, SUM(oi.quantity) as total_qty
             FROM {$wpdb->prefix}rp_order_items oi
             JOIN {$wpdb->posts} p ON p.ID = oi.menu_item_id
             GROUP BY oi.menu_item_id
             ORDER BY total_qty DESC
             LIMIT 5"
        );

        $recent_orders = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_orders WHERE DATE(created_at) = %s ORDER BY created_at DESC LIMIT 10",
            $today
        ) );

        include RP_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}
