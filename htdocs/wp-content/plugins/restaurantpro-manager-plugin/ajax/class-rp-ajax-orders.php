<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Ajax_Orders {

    public function register(): void {
        add_action( 'wp_ajax_rp_create_order', [ $this, 'create_order' ] );
        add_action( 'wp_ajax_rp_update_order_status', [ $this, 'update_status' ] );
        add_action( 'wp_ajax_rp_order_mark_paid', [ $this, 'mark_paid' ] );
        add_action( 'wp_ajax_rp_orders_sync', [ $this, 'sync_orders' ] );
    }

    public function create_order(): void {
        $nonce = $_POST['nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'rp_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'rp_orders_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_manage_orders' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        $table_number = absint( $_POST['table_number'] ?? 0 );
        $notes = sanitize_textarea_field( $_POST['notes'] ?? '' );
        $items_raw = json_decode( stripslashes( $_POST['items'] ?? '[]' ), true );

        if ( empty( $items_raw ) || ! is_array( $items_raw ) ) {
            wp_send_json_error( [ 'message' => 'No items provided.' ], 400 );
        }

        $lines = [];
        foreach ( $items_raw as $item ) {
            $menu_item_id = absint( $item['menu_item_id'] ?? $item['id'] ?? 0 );
            $quantity = max( 1, intval( $item['quantity'] ?? 1 ) );

            if ( ! $menu_item_id || get_post_type( $menu_item_id ) !== 'rp_menu_item' ) {
                continue;
            }

            $price = (float) get_post_meta( $menu_item_id, '_rp_price', true );
            $name = get_the_title( $menu_item_id );

            if ( '' === $name ) {
                continue;
            }

            $lines[] = [
                'menu_item_id' => $menu_item_id,
                'item_name'    => $name,
                'quantity'     => $quantity,
                'price'        => $price,
                'notes'        => '',
            ];
        }

        $calc = RP_Billing::calculate( $lines, [
            'discount_type'   => 'none',
            'discount_value'  => 0,
            'vat_enabled'     => false,
            'vat_rate'        => 0,
            'service_enabled' => false,
            'service_rate'    => 0,
            'amount_paid'     => 0,
        ] );

        if ( empty( $calc['items'] ) ) {
            wp_send_json_error( [ 'message' => 'No valid menu items.' ], 400 );
        }

        $order_type = $table_number ? 'dine_in' : 'takeaway';

        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'rp_orders',
            [
                'table_number'    => $table_number,
                'status'          => 'new',
                'total'           => $calc['total'],
                'subtotal'        => $calc['subtotal'],
                'discount_type'   => $calc['discount_type'],
                'discount_value'  => $calc['discount_value'],
                'discount_amount' => $calc['discount_amount'],
                'vat_rate'        => $calc['vat_rate'],
                'vat_amount'      => $calc['vat_amount'],
                'service_rate'    => $calc['service_rate'],
                'service_amount'  => $calc['service_amount'],
                'amount_paid'     => 0,
                'change_due'      => 0,
                'payment_method'  => 'cash',
                'customer_name'   => sanitize_text_field( $_POST['customer_name'] ?? '' ),
                'customer_phone'  => sanitize_text_field( $_POST['customer_phone'] ?? '' ),
                'order_type'      => $order_type,
                'notes'           => $notes,
                'created_by'      => get_current_user_id(),
                // Use WP's configured site time, not MySQL's own clock — keeps this
                // order's date bucket consistent with the current_time()-based "today"
                // filters used by POS/Orders/Kitchen sync.
                'created_at'      => current_time( 'mysql' ),
            ]
        );

        if ( ! $inserted ) {
            $detail = $wpdb->last_error ? ' (' . $wpdb->last_error . ')' : '';
            wp_send_json_error( [ 'message' => 'Could not save the order. Please try again.' . $detail ], 500 );
        }

        $order_id = (int) $wpdb->insert_id;
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

        $wpdb->insert(
            $wpdb->prefix . 'rp_kot',
            [ 'order_id' => $order_id, 'status' => 'pending', 'created_at' => current_time( 'mysql' ) ],
            [ '%d', '%s', '%s' ]
        );

        if ( $table_number ) {
            $wpdb->update(
                $wpdb->prefix . 'rp_tables',
                [ 'status' => 'occupied' ],
                [ 'id' => $table_number ],
                [ '%s' ],
                [ '%d' ]
            );
        }

        RP_Notifications::add( 'new_order', 'New Order', sprintf( 'Order %s was created and sent to the kitchen.', $invoice_no ), $order_id );
        RP_Activity::log( 'order_created', sprintf( 'Order %s created (Rs.%s)', $invoice_no, number_format( $calc['total'], 2 ) ), 'order', $order_id );

        wp_send_json_success( [
            'order_id'   => $order_id,
            'invoice_no' => $invoice_no,
            'total'      => $calc['total'],
            'redirect'   => home_url( "/panel/orders/{$order_id}" ),
            'bill_url'   => home_url( "/panel/bill/{$order_id}" ),
            'message'    => sprintf( 'Order %s created and sent to the kitchen.', $invoice_no ),
        ] );
    }

    public function update_status(): void {
        $nonce = $_POST['nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'rp_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'rp_orders_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_manage_orders' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        $status   = sanitize_text_field( $_POST['status'] ?? '' );

        $valid = array_keys( RP_Helpers::get_order_statuses() );
        if ( ! $order_id || ! in_array( $status, $valid, true ) ) {
            wp_send_json_error( [ 'message' => 'Invalid parameters.' ], 400 );
        }

        global $wpdb;

        $updated = $wpdb->update(
            $wpdb->prefix . 'rp_orders',
            [ 'status' => $status ],
            [ 'id' => $order_id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            wp_send_json_error( [ 'message' => 'Could not update the order. Please try again.' ], 500 );
        }

        // Keep the kitchen ticket in lock-step with the order. A cancelled order
        // must never remain actionable on the Kitchen Display, even if the KOT
        // was already pending or being prepared. The cancelled KOT is retained
        // for audit/history, but is excluded from active kitchen queues.
        if ( 'cancelled' === $status ) {
            $wpdb->update(
                $wpdb->prefix . 'rp_kot',
                [ 'status' => 'cancelled' ],
                [ 'order_id' => $order_id ],
                [ '%s' ],
                [ '%d' ]
            );

            // Cancelled orders should also release their table immediately.
            $table = $wpdb->get_var( $wpdb->prepare(
                "SELECT table_number FROM {$wpdb->prefix}rp_orders WHERE id = %d",
                $order_id
            ) );
            if ( $table ) {
                $wpdb->update(
                    $wpdb->prefix . 'rp_tables',
                    [ 'status' => 'available' ],
                    [ 'id' => $table ],
                    [ '%s' ],
                    [ '%d' ]
                );
            }
        }

        $invoice_no = (string) $wpdb->get_var( $wpdb->prepare( "SELECT invoice_no FROM {$wpdb->prefix}rp_orders WHERE id = %d", $order_id ) );
        RP_Notifications::add( 'status', 'Order Status Updated', sprintf( '%s is now %s.', $invoice_no ?: ('Order #' . $order_id), ucwords( str_replace( '_', ' ', $status ) ) ), $order_id );

        // If completed, free the table
        if ( $status === 'completed' ) {
            $table = $wpdb->get_var( $wpdb->prepare(
                "SELECT table_number FROM {$wpdb->prefix}rp_orders WHERE id = %d",
                $order_id
            ) );
            if ( $table ) {
                $wpdb->update(
                    $wpdb->prefix . 'rp_tables',
                    [ 'status' => 'available' ],
                    [ 'id' => $table ],
                    [ '%s' ],
                    [ '%d' ]
                );
            }
        }

        RP_Activity::log( 'order_status_changed', sprintf( 'Order #%d status changed to %s', $order_id, $status ), 'order', $order_id );

        wp_send_json_success( [ 'message' => 'Order updated.' ] );
    }

    public function mark_paid(): void {
        $nonce = $_POST['nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'rp_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'rp_orders_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_manage_orders' ) ) wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        $order_id = absint( $_POST['order_id'] ?? 0 );
        $method = sanitize_text_field( $_POST['payment_method'] ?? 'cash' );
        if ( ! array_key_exists( $method, RP_Billing::payment_methods() ) ) $method = 'cash';
        global $wpdb;
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rp_orders WHERE id=%d", $order_id ) );
        if ( ! $order ) wp_send_json_error( [ 'message' => 'Order not found.' ], 404 );
        $wpdb->update( $wpdb->prefix . 'rp_orders', [
            'amount_paid' => (float) $order->total,
            'change_due' => 0,
            'payment_method' => $method,
            'paid_at' => current_time( 'mysql' ),
        ], [ 'id' => $order_id ], [ '%f','%f','%s','%s' ], [ '%d' ] );
        RP_Notifications::add( 'payment', 'Payment Completed', sprintf( '%s was marked paid: %s.', $order->invoice_no ?: ('Order #'.$order_id), RP_Billing::money( $order->total ) ), $order_id );
        RP_Activity::log( 'payment_received', sprintf( 'Order #%d paid via %s (Rs.%s)', $order_id, $method, number_format( (float) $order->total, 2 ) ), 'order', $order_id );
        wp_send_json_success( [ 'message' => 'Payment saved.', 'paid' => true ] );
    }

    public function sync_orders(): void {
        $nonce = $_POST['nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'rp_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'rp_orders_nonce' ) ) wp_send_json_error( [ 'message'=>'Security check failed.' ], 403 );
        if ( ! current_user_can( 'rp_manage_orders' ) ) wp_send_json_error( [ 'message'=>'Permission denied.' ], 403 );
        global $wpdb;
        $date = sanitize_text_field( $_POST['date'] ?? current_time('Y-m-d') );
        $status = strtolower( trim( sanitize_text_field( $_POST['status'] ?? '' ) ) );
        $where = '1=1'; $params = [];
        if ( $date !== 'all' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) ) {
            $where .= " AND (DATE(o.created_at)=%s OR EXISTS (SELECT 1 FROM {$wpdb->prefix}rp_kot kk WHERE kk.order_id=o.id AND DATE(kk.created_at)=%s))";
            $params[]=$date; $params[]=$date;
        }
        if ( $status ) {
            if ( in_array($status,['cancelled','canceled'],true) ) $where .= " AND LOWER(TRIM(o.status)) IN ('cancelled','canceled')";
            elseif ( in_array($status,['new','accepted','preparing','ready','completed'],true) ) { $where .= ' AND LOWER(TRIM(o.status))=%s'; $params[]=$status; }
        }
        $sql = "SELECT o.*,u.display_name AS creator_name FROM {$wpdb->prefix}rp_orders o LEFT JOIN {$wpdb->users} u ON o.created_by=u.ID WHERE {$where} ORDER BY o.created_at DESC LIMIT 300";
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql,...$params)) : $wpdb->get_results($sql);
        $out=[]; $labels=class_exists('RP_Helpers')?RP_Helpers::get_order_statuses():[];
        foreach($rows?:[] as $o){ $raw=strtolower(trim((string)$o->status)); $st=in_array($raw,['cancelled','canceled'],true)?'cancelled':$raw; $paid=class_exists('RP_Accounts')?RP_Accounts::is_paid($o):false; $out[]=['id'=>(int)$o->id,'invoice_no'=>(string)$o->invoice_no,'status'=>$st,'status_label'=>$st==='cancelled'?'Cancelled':($labels[$st]??ucfirst($st)),'paid'=>(bool)$paid,'table_number'=>(int)$o->table_number,'customer_name'=>(string)$o->customer_name,'total'=>(float)$o->total,'created_at'=>(string)$o->created_at,'time_label'=>wp_date('g:i:s A',strtotime($o->created_at)),'order_url'=>home_url('/panel/orders/'.(int)$o->id),'bill_url'=>home_url('/panel/bill/'.(int)$o->id)]; }
        wp_send_json_success(['orders'=>$out,'count'=>count($out),'server_time'=>current_time('mysql'),'business_date'=>$date]);
    }

}
