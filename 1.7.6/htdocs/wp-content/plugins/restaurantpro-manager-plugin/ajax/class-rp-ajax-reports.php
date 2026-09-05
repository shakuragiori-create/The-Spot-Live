<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Accounting / reports data.
 *
 * Revenue is defined as SUM(total) over orders with status = 'completed'
 * inside the requested date range. Everything is computed in SQL so the
 * free-hosting CPU budget is respected.
 */
class RP_Ajax_Reports {

    public function register(): void {
        add_action( 'wp_ajax_rp_reports_data', [ $this, 'data' ] );
        // CSV export is a plain browser GET so InfinityFree's anti-bot layer
        // never sees a "scripted" request.
        add_action( 'admin_post_rp_reports_csv', [ $this, 'csv' ] );
    }

    /* ------------------------------------------------------------------ */
    /* Endpoints                                                          */
    /* ------------------------------------------------------------------ */

    public function data(): void {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_admin_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_view_reports' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        [ $from, $to, $group ] = self::read_range( $_POST );

        wp_send_json_success( self::build( $from, $to, $group ) );
    }

    public function csv(): void {
        if ( ! current_user_can( 'rp_view_reports' ) ) {
            wp_die( 'Permission denied.' );
        }
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'rp_reports_csv' ) ) {
            wp_die( 'Security check failed.' );
        }

        [ $from, $to, $group ] = self::read_range( $_GET );
        $report = self::build( $from, $to, $group );

        $filename = 'the-spot-' . $group . '-report-' . $from . '-to-' . $to . '.csv';

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        $out = fopen( 'php://output', 'w' );

        // Excel needs the BOM to read UTF-8 correctly.
        fwrite( $out, "\xEF\xBB\xBF" );

        fputcsv( $out, [ 'The Spot Fast Food & Tea — Sales report' ] );
        fputcsv( $out, [ 'Period', $from . ' to ' . $to, 'Grouped by', $group ] );
        fputcsv( $out, [ 'Generated', wp_date( 'Y-m-d H:i' ) ] );
        fputcsv( $out, [] );

        fputcsv( $out, [ 'SUMMARY' ] );
        fputcsv( $out, [ 'Orders',          $report['totals']['orders'] ] );
        fputcsv( $out, [ 'Gross sales',     $report['totals']['subtotal'] ] );
        fputcsv( $out, [ 'Discounts',       $report['totals']['discount'] ] );
        fputcsv( $out, [ 'Service charge',  $report['totals']['service'] ] );
        fputcsv( $out, [ 'VAT',             $report['totals']['vat'] ] );
        fputcsv( $out, [ 'Net revenue',     $report['totals']['revenue'] ] );
        fputcsv( $out, [ 'Average bill',    $report['totals']['avg'] ] );
        fputcsv( $out, [] );

        fputcsv( $out, [ ucfirst( $group ) . ' breakdown' ] );
        fputcsv( $out, [ 'Period', 'Orders', 'Revenue', 'VAT', 'Service', 'Discount' ] );
        foreach ( $report['labels'] as $i => $label ) {
            fputcsv( $out, [
                $label,
                $report['orders'][ $i ],
                $report['revenue'][ $i ],
                $report['vat'][ $i ],
                $report['service'][ $i ],
                $report['discount'][ $i ],
            ] );
        }
        fputcsv( $out, [] );

        fputcsv( $out, [ 'Top items' ] );
        fputcsv( $out, [ 'Item', 'Qty sold', 'Revenue' ] );
        foreach ( $report['top_items'] as $row ) {
            fputcsv( $out, [ $row['label'], $row['qty'], $row['revenue'] ] );
        }
        fputcsv( $out, [] );

        fputcsv( $out, [ 'By category' ] );
        fputcsv( $out, [ 'Category', 'Qty sold', 'Revenue' ] );
        foreach ( $report['categories'] as $row ) {
            fputcsv( $out, [ $row['label'], $row['qty'], $row['revenue'] ] );
        }
        fputcsv( $out, [] );

        fputcsv( $out, [ 'By payment method' ] );
        fputcsv( $out, [ 'Method', 'Orders', 'Revenue' ] );
        foreach ( $report['payments'] as $row ) {
            fputcsv( $out, [ $row['label'], $row['orders'], $row['revenue'] ] );
        }

        fclose( $out );
        exit;
    }

    /* ------------------------------------------------------------------ */
    /* Query building                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{0:string,1:string,2:string} from (Y-m-d), to (Y-m-d), group
     */
    private static function read_range( array $src ): array {
        $group = sanitize_text_field( $src['group'] ?? 'day' );
        if ( ! in_array( $group, [ 'day', 'week', 'month' ], true ) ) {
            $group = 'day';
        }

        $from = self::clean_date( $src['from'] ?? '' );
        $to   = self::clean_date( $src['to'] ?? '' );

        if ( ! $to ) {
            $to = current_time( 'Y-m-d' );
        }
        if ( ! $from ) {
            $span = [ 'day' => '-29 days', 'week' => '-11 weeks', 'month' => '-11 months' ];
            $from = gmdate( 'Y-m-d', strtotime( $span[ $group ], strtotime( $to ) ) );
        }
        if ( strtotime( $from ) > strtotime( $to ) ) {
            [ $from, $to ] = [ $to, $from ];
        }

        return [ $from, $to, $group ];
    }

    private static function clean_date( $value ): string {
        $value = trim( (string) $value );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return '';
        }
        return $value;
    }

    /**
     * Ordered bucket keys → human labels, so empty days/weeks/months still
     * appear on the chart instead of being silently skipped.
     */
    private static function buckets( string $from, string $to, string $group ): array {
        $out   = [];
        $start = strtotime( $from );
        $end   = strtotime( $to );
        $guard = 0;

        if ( 'week' === $group ) {
            // Walk from the Monday of the first week.
            $cursor = strtotime( 'monday this week', $start );
            while ( $cursor <= $end && $guard++ < 400 ) {
                $out[ date( 'oW', $cursor ) ] = 'Wk ' . date( 'W', $cursor ) . ' · ' . date( 'd M', $cursor );
                $cursor = strtotime( '+7 days', $cursor );
            }
        } elseif ( 'month' === $group ) {
            $cursor = strtotime( date( 'Y-m-01', $start ) );
            while ( $cursor <= $end && $guard++ < 400 ) {
                $out[ date( 'Y-m', $cursor ) ] = date( 'M Y', $cursor );
                $cursor = strtotime( '+1 month', $cursor );
            }
        } else {
            $cursor = $start;
            while ( $cursor <= $end && $guard++ < 400 ) {
                $out[ date( 'Y-m-d', $cursor ) ] = date( 'd M', $cursor );
                $cursor = strtotime( '+1 day', $cursor );
            }
        }

        return $out;
    }

    private static function bucket_sql( string $group ): string {
        if ( 'week' === $group ) {
            return "YEARWEEK( o.created_at, 3 )";
        }
        if ( 'month' === $group ) {
            // %% because this string is fed through $wpdb->prepare().
            return "DATE_FORMAT( o.created_at, '%%Y-%%m' )";
        }
        return "DATE( o.created_at )";
    }

    public static function build( string $from, string $to, string $group ): array {
        global $wpdb;

        $orders_tbl = $wpdb->prefix . 'rp_orders';
        $items_tbl  = $wpdb->prefix . 'rp_order_items';

        $start = $from . ' 00:00:00';
        $end   = $to . ' 23:59:59';

        $bucket = self::bucket_sql( $group );

        /* ---- series ---- */
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT {$bucket} AS bucket,
                    COUNT(*)                    AS orders,
                    SUM(o.total)                AS revenue,
                    SUM(o.vat_amount)           AS vat,
                    SUM(o.service_amount)       AS service,
                    SUM(o.discount_amount)      AS discount
             FROM {$orders_tbl} o
             WHERE o.status = 'completed'
               AND o.created_at BETWEEN %s AND %s
             GROUP BY bucket",
            $start,
            $end
        ), ARRAY_A );

        $by_key = [];
        foreach ( $rows as $row ) {
            $by_key[ (string) $row['bucket'] ] = $row;
        }

        $buckets  = self::buckets( $from, $to, $group );
        $labels   = [];
        $revenue  = [];
        $orders   = [];
        $vat      = [];
        $service  = [];
        $discount = [];

        foreach ( $buckets as $key => $label ) {
            $row        = $by_key[ (string) $key ] ?? null;
            $labels[]   = $label;
            $orders[]   = $row ? (int) $row['orders'] : 0;
            $revenue[]  = $row ? round( (float) $row['revenue'], 2 ) : 0.0;
            $vat[]      = $row ? round( (float) $row['vat'], 2 ) : 0.0;
            $service[]  = $row ? round( (float) $row['service'], 2 ) : 0.0;
            $discount[] = $row ? round( (float) $row['discount'], 2 ) : 0.0;
        }

        /* ---- totals ---- */
        $t = $wpdb->get_row( $wpdb->prepare(
            "SELECT COUNT(*)               AS orders,
                    SUM(o.total)           AS revenue,
                    SUM(o.subtotal)        AS subtotal,
                    SUM(o.vat_amount)      AS vat,
                    SUM(o.service_amount)  AS service,
                    SUM(o.discount_amount) AS discount
             FROM {$orders_tbl} o
             WHERE o.status = 'completed'
               AND o.created_at BETWEEN %s AND %s",
            $start,
            $end
        ), ARRAY_A );

        $count      = (int) ( $t['orders'] ?? 0 );
        $rev        = round( (float) ( $t['revenue'] ?? 0 ), 2 );
        $sub        = round( (float) ( $t['subtotal'] ?? 0 ), 2 );
        // Legacy orders have no subtotal — fall back to revenue so the
        // "gross sales" line is never blank.
        if ( $sub <= 0 ) {
            $sub = $rev;
        }

        $totals = [
            'orders'   => $count,
            'revenue'  => $rev,
            'subtotal' => $sub,
            'vat'      => round( (float) ( $t['vat'] ?? 0 ), 2 ),
            'service'  => round( (float) ( $t['service'] ?? 0 ), 2 ),
            'discount' => round( (float) ( $t['discount'] ?? 0 ), 2 ),
            'avg'      => $count ? round( $rev / $count, 2 ) : 0.0,
        ];

        /* ---- top items ---- */
        $top = $wpdb->get_results( $wpdb->prepare(
            "SELECT COALESCE( NULLIF( oi.item_name, '' ), p.post_title, '(deleted item)' ) AS label,
                    SUM(oi.quantity)              AS qty,
                    SUM(oi.quantity * oi.price)   AS revenue
             FROM {$items_tbl} oi
             INNER JOIN {$orders_tbl} o ON o.id = oi.order_id
             LEFT JOIN {$wpdb->posts} p ON p.ID = oi.menu_item_id
             WHERE o.status = 'completed'
               AND o.created_at BETWEEN %s AND %s
             GROUP BY label
             ORDER BY revenue DESC
             LIMIT 15",
            $start,
            $end
        ), ARRAY_A );

        /* ---- category split (one category per item: the lowest term id) ---- */
        $cats = $wpdb->get_results( $wpdb->prepare(
            "SELECT COALESCE( t.name, 'Other / custom' )  AS label,
                    SUM(oi.quantity)                      AS qty,
                    SUM(oi.quantity * oi.price)           AS revenue
             FROM {$items_tbl} oi
             INNER JOIN {$orders_tbl} o ON o.id = oi.order_id
             LEFT JOIN (
                 SELECT tr.object_id, MIN(tt.term_id) AS term_id
                 FROM {$wpdb->term_relationships} tr
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                 WHERE tt.taxonomy = 'rp_menu_category'
                 GROUP BY tr.object_id
             ) m ON m.object_id = oi.menu_item_id
             LEFT JOIN {$wpdb->terms} t ON t.term_id = m.term_id
             WHERE o.status = 'completed'
               AND o.created_at BETWEEN %s AND %s
             GROUP BY label
             ORDER BY revenue DESC
             LIMIT 20",
            $start,
            $end
        ), ARRAY_A );

        /* ---- payment split ---- */
        $pay_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT COALESCE( NULLIF( o.payment_method, '' ), 'cash' ) AS method,
                    COUNT(*)     AS orders,
                    SUM(o.total) AS revenue
             FROM {$orders_tbl} o
             WHERE o.status = 'completed'
               AND o.created_at BETWEEN %s AND %s
             GROUP BY method
             ORDER BY revenue DESC",
            $start,
            $end
        ), ARRAY_A );

        $method_labels = RP_Billing::payment_methods();
        $payments      = [];
        foreach ( $pay_rows as $row ) {
            $payments[] = [
                'label'   => $method_labels[ $row['method'] ] ?? ucfirst( (string) $row['method'] ),
                'orders'  => (int) $row['orders'],
                'revenue' => round( (float) $row['revenue'], 2 ),
            ];
        }

        return [
            'from'       => $from,
            'to'         => $to,
            'group'      => $group,
            'labels'     => $labels,
            'revenue'    => $revenue,
            'orders'     => $orders,
            'vat'        => $vat,
            'service'    => $service,
            'discount'   => $discount,
            'totals'     => $totals,
            'top_items'  => array_map( [ __CLASS__, 'shape_item' ], $top ),
            'categories' => array_map( [ __CLASS__, 'shape_item' ], $cats ),
            'payments'   => $payments,
        ];
    }

    private static function shape_item( array $row ): array {
        return [
            'label'   => (string) $row['label'],
            'qty'     => (int) $row['qty'],
            'revenue' => round( (float) $row['revenue'], 2 ),
        ];
    }
}
