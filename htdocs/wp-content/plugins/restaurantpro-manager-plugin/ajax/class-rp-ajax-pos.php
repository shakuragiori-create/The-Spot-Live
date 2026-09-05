<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * POS checkout endpoint. All money is recomputed server-side by RP_Billing —
 * the cart in the browser is only ever a preview.
 */
class RP_Ajax_Pos {

    public function register(): void {
        add_action( 'wp_ajax_rp_pos_create_order', [ $this, 'create_order' ] );
        add_action( 'wp_ajax_rp_pos_current_orders', [ $this, 'current_orders' ] );
    }

    public function create_order(): void {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_admin_nonce' ) && ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_orders_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed. Please reload the page.' ], 403 );
        }
        if ( ! current_user_can( 'rp_manage_orders' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        $lines = json_decode( wp_unslash( (string) ( $_POST['items'] ?? '[]' ) ), true );
        if ( ! is_array( $lines ) || empty( $lines ) ) {
            wp_send_json_error( [ 'message' => 'The cart is empty.' ], 400 );
        }

        $pay_now = ! empty( $_POST['pay_now'] );

        $calc = RP_Billing::calculate( $lines, [
            'discount_type'   => sanitize_text_field( $_POST['discount_type'] ?? 'none' ),
            'discount_value'  => $_POST['discount_value'] ?? 0,
            'vat_enabled'     => ! empty( $_POST['vat_enabled'] ),
            'vat_rate'        => $_POST['vat_rate'] ?? 0,
            'service_enabled' => ! empty( $_POST['service_enabled'] ),
            'service_rate'    => $_POST['service_rate'] ?? 0,
            'amount_paid'     => $pay_now ? ( $_POST['amount_paid'] ?? 0 ) : 0,
        ] );

        if ( empty( $calc['items'] ) ) {
            wp_send_json_error( [ 'message' => 'No valid items in the cart.' ], 400 );
        }

        $order_type = sanitize_text_field( $_POST['order_type'] ?? 'dine_in' );
        if ( ! array_key_exists( $order_type, RP_Billing::order_types() ) ) {
            $order_type = 'dine_in';
        }

        $payment_method = sanitize_text_field( $_POST['payment_method'] ?? 'cash' );
        if ( ! array_key_exists( $payment_method, RP_Billing::payment_methods() ) ) {
            $payment_method = 'cash';
        }

        $table_id = absint( $_POST['table_id'] ?? 0 );
        $status   = $pay_now ? 'completed' : 'new';

        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'rp_orders',
            [
                'table_number'    => $table_id,
                'status'          => $status,
                'total'           => $calc['total'],
                'subtotal'        => $calc['subtotal'],
                'discount_type'   => $calc['discount_type'],
                'discount_value'  => $calc['discount_value'],
                'discount_amount' => $calc['discount_amount'],
                'vat_rate'        => $calc['vat_rate'],
                'vat_amount'      => $calc['vat_amount'],
                'service_rate'    => $calc['service_rate'],
                'service_amount'  => $calc['service_amount'],
                'amount_paid'     => $calc['amount_paid'],
                'change_due'      => $calc['change_due'],
                'payment_method'  => $payment_method,
                'paid_at'         => $pay_now ? current_time( 'mysql' ) : null,
                'customer_name'   => sanitize_text_field( $_POST['customer_name'] ?? '' ),
                'customer_phone'  => sanitize_text_field( $_POST['customer_phone'] ?? '' ),
                'order_type'      => $order_type,
                'notes'           => sanitize_textarea_field( $_POST['notes'] ?? '' ),
                'created_by'      => get_current_user_id(),
                // Stamp with WordPress's configured site time, not MySQL's own server
                // clock — the "today" filters in POS/Orders use current_time(), and if
                // the two disagree (e.g. MySQL on UTC vs a Nepal site timezone), orders
                // silently fall out of "today" while Kitchen (no date filter) still shows them.
                'created_at'      => current_time( 'mysql' ),
            ],
            [
                '%d', '%s', '%f', '%f', '%s', '%f', '%f', '%f', '%f', '%f', '%f',
                '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
            ]
        );

        $order_id = (int) $wpdb->insert_id;
        if ( ! $order_id ) {
            // Surface the real DB error (visible to staff/admin in the POS
            // message banner) instead of a dead-end generic message, so a
            // failure can actually be diagnosed instead of just looking
            // "broken" with no clue why.
            $detail = $wpdb->last_error ? ' (' . $wpdb->last_error . ')' : '';
            wp_send_json_error( [ 'message' => 'Could not save the order. Please try again.' . $detail ], 500 );
        }

        // Invoice number is derived from the row id, so it can never collide.
        $invoice_no = RP_Billing::invoice_no( $order_id );
        $wpdb->update(
            $wpdb->prefix . 'rp_orders',
            [ 'invoice_no' => $invoice_no ],
            [ 'id' => $order_id ],
            [ '%s' ],
            [ '%d' ]
        );

        foreach ( $calc['items'] as $item ) {
            $wpdb->insert(
                $wpdb->prefix . 'rp_order_items',
                [
                    'order_id'     => $order_id,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'item_name'    => $item['item_name'],
                    'notes'        => $item['notes'],
                ],
                [ '%d', '%d', '%d', '%f', '%s', '%s' ]
            );
        }

        if ( ! empty( $_POST['send_kot'] ) ) {
            $wpdb->insert(
                $wpdb->prefix . 'rp_kot',
                [ 'order_id' => $order_id, 'status' => 'pending', 'created_at' => current_time( 'mysql' ) ],
                [ '%d', '%s', '%s' ]
            );
        }

        // Occupy the table for open orders; leave it free once already paid.
        if ( $table_id && ! $pay_now ) {
            $wpdb->update(
                $wpdb->prefix . 'rp_tables',
                [ 'status' => 'occupied' ],
                [ 'id' => $table_id ],
                [ '%s' ],
                [ '%d' ]
            );
        }

        RP_Notifications::add( 'new_order', 'New POS Order', sprintf( 'Order %s was created on the POS.', $invoice_no ), $order_id );
        RP_Activity::log( 'pos_order_created', sprintf( 'POS order %s created (Rs.%s)%s', $invoice_no, number_format( $calc['total'], 2 ), $pay_now ? ' — paid' : '' ), 'order', $order_id );
        if ( $pay_now ) RP_Notifications::add( 'payment', 'Payment Completed', sprintf( 'Order %s was paid: %s.', $invoice_no, RP_Billing::money( $calc['total'] ) ), $order_id );
        if ( ! empty( $_POST['send_kot'] ) ) RP_Notifications::add( 'order_kot', 'Order Sent to Kitchen', sprintf( 'Order %s is waiting in the kitchen.', $invoice_no ), $order_id );

        $bill_url = home_url( "/panel/bill/{$order_id}" );

        wp_send_json_success( [
            'order_id'   => $order_id,
            'invoice_no' => $invoice_no,
            'total'      => $calc['total'],
            'change_due' => $calc['change_due'],
            'bill_url'   => $pay_now ? $bill_url . '&autoprint=80' : $bill_url,
            'message'    => $pay_now
                ? sprintf( 'Bill %s saved. Opening the print view…', $invoice_no )
                : sprintf( 'Order #%d sent to the kitchen.', $order_id ),
        ] );
    }

    /** Return today's orders for the POS screen so POS and Orders stay synchronized. */
    public function current_orders(): void {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_admin_nonce' ) ) wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        if ( ! current_user_can( 'rp_manage_orders' ) ) wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        global $wpdb;
        $orders_table = $wpdb->prefix . 'rp_orders';
        $items_table  = $wpdb->prefix . 'rp_order_items';
        $today = current_time( 'Y-m-d' );
        $orders = $wpdb->get_results( $wpdb->prepare(
            "SELECT o.id,o.invoice_no,o.status,o.total,o.amount_paid,o.paid_at,o.payment_method,o.order_type,o.table_number,o.customer_name,o.customer_phone,o.created_at,
                    (SELECT SUM(quantity) FROM {$items_table} oi WHERE oi.order_id=o.id) AS item_count
             FROM {$orders_table} o
             WHERE (DATE(o.created_at)=%s OR EXISTS (SELECT 1 FROM {$wpdb->prefix}rp_kot kk WHERE kk.order_id=o.id AND DATE(kk.created_at)=%s)) AND LOWER(TRIM(o.status)) NOT IN ('cancelled','canceled')
             ORDER BY o.created_at DESC LIMIT 100", $today, $today
        ), ARRAY_A );
        $labels = RP_Helpers::get_order_statuses();
        $data = array_map( static function( $o ) use ( $labels ) {
            $status=(string)$o['status']; $total=(float)$o['total']; $amount=(float)$o['amount_paid'];
            $paid=!empty($o['paid_at']) || ($total>0 && $amount+0.009 >= $total);
            return [ 'id'=>(int)$o['id'],'invoice_no'=>(string)$o['invoice_no'],'status'=>$status,'status_label'=>$labels[$status]??ucfirst($status),'total'=>$total,'amount_paid'=>$amount,'paid'=>$paid,'payment_method'=>(string)$o['payment_method'],'order_type'=>(string)$o['order_type'],'table_number'=>(int)$o['table_number'],'customer_name'=>(string)$o['customer_name'],'customer_phone'=>(string)$o['customer_phone'],'created_at'=>(string)$o['created_at'],'time_label'=>wp_date('g:i A',strtotime($o['created_at'])),'item_count'=>(int)$o['item_count'],'bill_url'=>home_url('/panel/bill/'.(int)$o['id']),'pdf_url'=>home_url('/panel/bill/'.(int)$o['id'].'/pdf'),'order_url'=>home_url('/panel/orders/'.(int)$o['id']) ];
        }, $orders ?: [] );
        wp_send_json_success( [ 'orders'=>$data, 'server_now'=>current_time('mysql'), 'business_date'=>$today ] );
    }

}
