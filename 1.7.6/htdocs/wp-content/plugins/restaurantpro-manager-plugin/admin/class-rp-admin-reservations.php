<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Admin_Reservations {

    public function render(): void {
        global $wpdb;

        if ( isset( $_POST['rp_update_reservation'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'rp_update_reservation' ) && current_user_can( 'rp_manage_reservations' ) ) {
            $id     = absint( $_POST['reservation_id'] );
            $status = sanitize_text_field( $_POST['status'] );
            $valid  = array_keys( RP_Helpers::get_reservation_statuses() );
            if ( $id && in_array( $status, $valid, true ) ) {
                $wpdb->update(
                    $wpdb->prefix . 'rp_reservations',
                    [ 'status' => $status ],
                    [ 'id' => $id ],
                    [ '%s' ],
                    [ '%d' ]
                );
            }
        }

        $status_filter = sanitize_text_field( $_GET['status'] ?? '' );
        $where = '';
        if ( $status_filter && in_array( $status_filter, array_keys( RP_Helpers::get_reservation_statuses() ), true ) ) {
            $where = $wpdb->prepare( "WHERE status = %s", $status_filter );
        }

        $reservations = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rp_reservations $where ORDER BY reservation_date DESC, reservation_time DESC LIMIT 50"
        );

        include RP_PLUGIN_DIR . 'admin/views/reservations.php';
    }
}
