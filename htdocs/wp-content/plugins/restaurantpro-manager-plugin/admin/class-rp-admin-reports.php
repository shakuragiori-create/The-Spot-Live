<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Accounting screen — daily / weekly / monthly sales.
 *
 * The numbers are rendered server-side on page load (a plain GET form changes
 * the range) so the screen still works if AJAX is blocked by the host. The
 * charts are drawn from the same data, printed as JSON.
 */
class RP_Admin_Reports {

    public function render(): void {
        if ( ! current_user_can( 'rp_view_reports' ) ) {
            wp_die( 'You are not allowed to view reports.' );
        }

        $group = sanitize_text_field( $_GET['group'] ?? 'day' );
        if ( ! in_array( $group, [ 'day', 'week', 'month' ], true ) ) {
            $group = 'day';
        }

        $preset = sanitize_text_field( $_GET['preset'] ?? '' );
        $today  = current_time( 'Y-m-d' );

        $from = $this->clean_date( $_GET['from'] ?? '' );
        $to   = $this->clean_date( $_GET['to'] ?? '' );

        // Quick-range buttons win over the date boxes.
        switch ( $preset ) {
            case 'today':
                $from = $to = $today;
                break;
            case 'yesterday':
                $from = $to = date( 'Y-m-d', strtotime( '-1 day', strtotime( $today ) ) );
                break;
            case '7':
                $to   = $today;
                $from = date( 'Y-m-d', strtotime( '-6 days', strtotime( $today ) ) );
                break;
            case '30':
                $to   = $today;
                $from = date( 'Y-m-d', strtotime( '-29 days', strtotime( $today ) ) );
                break;
            case 'month':
                $from = date( 'Y-m-01', strtotime( $today ) );
                $to   = $today;
                break;
            case 'year':
                $from = date( 'Y-01-01', strtotime( $today ) );
                $to   = $today;
                break;
        }

        if ( ! $to ) {
            $to = $today;
        }
        if ( ! $from ) {
            $span = [ 'day' => '-29 days', 'week' => '-11 weeks', 'month' => '-11 months' ];
            $from = date( 'Y-m-d', strtotime( $span[ $group ], strtotime( $to ) ) );
        }
        if ( strtotime( $from ) > strtotime( $to ) ) {
            [ $from, $to ] = [ $to, $from ];
        }

        $report = RP_Ajax_Reports::build( $from, $to, $group );

        $csv_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=rp_reports_csv&from=' . $from . '&to=' . $to . '&group=' . $group ),
            'rp_reports_csv'
        );

        include RP_PLUGIN_DIR . 'admin/views/reports.php';
    }

    private function clean_date( $value ): string {
        $value = trim( (string) $value );
        return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
    }
}
