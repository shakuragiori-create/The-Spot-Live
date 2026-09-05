<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Ajax_Dashboard {

    public function register(): void {
        add_action( 'wp_ajax_rp_get_dashboard_stats', [ $this, 'get_stats' ] );
        add_action( 'wp_ajax_rp_get_hourly_stats', [ $this, 'get_hourly_stats' ] );
    }

    public function get_stats(): void {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_admin_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_view_reports' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        global $wpdb;
        $today = current_time( 'Y-m-d' );

        // Orders by hour today
        $hourly = $wpdb->get_results( $wpdb->prepare(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count
             FROM {$wpdb->prefix}rp_orders
             WHERE DATE(created_at) = %s AND LOWER(TRIM(status)) NOT IN ('cancelled','canceled')
             GROUP BY HOUR(created_at)
             ORDER BY hour",
            $today
        ), ARRAY_A );

        $hours_labels = [];
        $hours_data = [];
        for ( $h = 8; $h <= 23; $h++ ) {
            $hours_labels[] = sprintf( '%02d:00', $h );
            $found = 0;
            foreach ( $hourly as $row ) {
                if ( (int) $row['hour'] === $h ) {
                    $found = (int) $row['count'];
                    break;
                }
            }
            $hours_data[] = $found;
        }

        wp_send_json_success( [
            'hours_labels' => $hours_labels,
            'hours_data'   => $hours_data,
        ] );
    }

    public function get_hourly_stats(): void {
        $nonce = $_POST['nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'rp_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'rp_dashboard_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        global $wpdb;
        $today = current_time( 'Y-m-d' );

        $hourly = $wpdb->get_results( $wpdb->prepare(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count
             FROM {$wpdb->prefix}rp_orders
             WHERE DATE(created_at) = %s AND LOWER(TRIM(status)) NOT IN ('cancelled','canceled')
             GROUP BY HOUR(created_at)
             ORDER BY hour",
            $today
        ), ARRAY_A );

        $hours = [];
        $counts = [];
        for ( $h = 8; $h <= 23; $h++ ) {
            $hours[] = sprintf( '%d:00', $h );
            $found = 0;
            foreach ( $hourly as $row ) {
                if ( (int) $row['hour'] === $h ) {
                    $found = (int) $row['count'];
                    break;
                }
            }
            $counts[] = $found;
        }

        wp_send_json_success( [ 'hours' => $hours, 'counts' => $counts ] );
    }
}
