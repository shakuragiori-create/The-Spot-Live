<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Admin_Orders {

    public function render(): void {
        $action = sanitize_text_field( $_GET['action'] ?? 'list' );

        switch ( $action ) {
            case 'new':
                $this->render_form();
                break;
            case 'edit':
                $this->render_form( absint( $_GET['order_id'] ?? 0 ) );
                break;
            case 'bill':
                $this->render_bill( absint( $_GET['order_id'] ?? 0 ) );
                break;
            default:
                $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;

        $status_filter = sanitize_text_field( $_GET['status'] ?? '' );
        $where = '';
        if ( $status_filter && in_array( $status_filter, array_keys( RP_Helpers::get_order_statuses() ), true ) ) {
            $where = $wpdb->prepare( "WHERE o.status = %s", $status_filter );
        }

        $orders = $wpdb->get_results(
            "SELECT o.*, u.display_name as creator_name, t.table_name
             FROM {$wpdb->prefix}rp_orders o
             LEFT JOIN {$wpdb->users} u ON u.ID = o.created_by
             LEFT JOIN {$wpdb->prefix}rp_tables t ON t.id = o.table_number
             $where
             ORDER BY o.created_at DESC
             LIMIT 100"
        );

        include RP_PLUGIN_DIR . 'admin/views/orders.php';
    }

    private function render_form( int $order_id = 0 ): void {
        global $wpdb;

        $order = null;
        $order_items = [];

        if ( $order_id ) {
            $order = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rp_orders WHERE id = %d",
                $order_id
            ) );
            if ( $order ) {
                $order_items = $wpdb->get_results( $wpdb->prepare(
                    "SELECT oi.*, COALESCE( NULLIF( oi.item_name, '' ), p.post_title, '(deleted item)' ) AS post_title
                     FROM {$wpdb->prefix}rp_order_items oi
                     LEFT JOIN {$wpdb->posts} p ON p.ID = oi.menu_item_id
                     WHERE oi.order_id = %d
                     ORDER BY oi.id ASC",
                    $order_id
                ) );
            }
        }

        $tables = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rp_tables ORDER BY table_name" );

        $menu_items = get_posts( [
            'post_type'      => 'rp_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [ 'key' => '_rp_is_available', 'value' => '1' ],
            ],
        ] );

        include RP_PLUGIN_DIR . 'admin/views/order-form.php';
    }

    /**
     * Editable, printable bill. The cashier may adjust lines, prices, discount,
     * charges, customer details and the footer note, then save + print.
     */
    private function render_bill( int $order_id ): void {
        global $wpdb;

        if ( ! current_user_can( 'rp_view_orders' ) ) {
            wp_die( 'You are not allowed to view bills.' );
        }

        $saved_notice = '';

        // ---- Save edits -------------------------------------------------
        if (
            isset( $_POST['rp_save_bill'] )
            && isset( $_POST['_wpnonce'] )
            && wp_verify_nonce( $_POST['_wpnonce'], 'rp_save_bill_' . $order_id )
            && current_user_can( 'rp_manage_orders' )
        ) {
            $names  = (array) ( $_POST['line_name'] ?? [] );
            $qtys   = (array) ( $_POST['line_qty'] ?? [] );
            $prices = (array) ( $_POST['line_price'] ?? [] );
            $ids    = (array) ( $_POST['line_item_id'] ?? [] );
            $remove = (array) ( $_POST['line_remove'] ?? [] );

            $lines = [];
            foreach ( $names as $i => $name ) {
                if ( ! empty( $remove[ $i ] ) ) {
                    continue;
                }
                $lines[] = [
                    'menu_item_id' => absint( $ids[ $i ] ?? 0 ),
                    'item_name'    => sanitize_text_field( wp_unslash( (string) $name ) ),
                    'quantity'     => (int) ( $qtys[ $i ] ?? 1 ),
                    'price'        => (float) ( $prices[ $i ] ?? 0 ),
                ];
            }

            // A new blank line typed at the bottom of the bill.
            $new_name = sanitize_text_field( wp_unslash( (string) ( $_POST['new_line_name'] ?? '' ) ) );
            if ( '' !== $new_name ) {
                $lines[] = [
                    'menu_item_id' => 0,
                    'item_name'    => $new_name,
                    'quantity'     => (int) ( $_POST['new_line_qty'] ?? 1 ),
                    'price'        => (float) ( $_POST['new_line_price'] ?? 0 ),
                ];
            }

            if ( ! empty( $lines ) ) {
                $calc = RP_Billing::calculate( $lines, [
                    'discount_type'   => sanitize_text_field( $_POST['discount_type'] ?? 'none' ),
                    'discount_value'  => $_POST['discount_value'] ?? 0,
                    'vat_enabled'     => ! empty( $_POST['vat_enabled'] ),
                    'vat_rate'        => $_POST['vat_rate'] ?? 0,
                    'service_enabled' => ! empty( $_POST['service_enabled'] ),
                    'service_rate'    => $_POST['service_rate'] ?? 0,
                    'amount_paid'     => $_POST['amount_paid'] ?? 0,
                ] );

                $payment_method = sanitize_text_field( $_POST['payment_method'] ?? 'cash' );
                if ( ! array_key_exists( $payment_method, RP_Billing::payment_methods() ) ) {
                    $payment_method = 'cash';
                }

                RP_Billing::persist( $order_id, $calc, [
                    'customer_name'  => sanitize_text_field( $_POST['customer_name'] ?? '' ),
                    'customer_phone' => sanitize_text_field( $_POST['customer_phone'] ?? '' ),
                    'payment_method' => $payment_method,
                    'notes'          => sanitize_textarea_field( $_POST['notes'] ?? '' ),
                ] );

                $saved_notice = 'Bill updated.';
            } else {
                $saved_notice = 'A bill needs at least one line — nothing was changed.';
            }
        }

        // ---- Load -------------------------------------------------------
        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT o.*, t.table_name, u.display_name AS creator_name
             FROM {$wpdb->prefix}rp_orders o
             LEFT JOIN {$wpdb->prefix}rp_tables t ON t.id = o.table_number
             LEFT JOIN {$wpdb->users} u ON u.ID = o.created_by
             WHERE o.id = %d",
            $order_id
        ) );

        if ( ! $order ) {
            echo '<div class="wrap"><h1>Bill not found</h1><p>That order no longer exists. '
               . '<a href="' . esc_url( admin_url( 'admin.php?page=rp-orders' ) ) . '">Back to Orders</a></p></div>';
            return;
        }

        // Older orders created before billing fields existed: fill sensible values.
        if ( (float) $order->subtotal <= 0 ) {
            $order->subtotal = (float) $order->total;
        }
        if ( '' === (string) $order->invoice_no ) {
            $order->invoice_no = RP_Billing::invoice_no( (int) $order->id );
            $wpdb->update(
                $wpdb->prefix . 'rp_orders',
                [ 'invoice_no' => $order->invoice_no ],
                [ 'id' => $order->id ],
                [ '%s' ],
                [ '%d' ]
            );
        }

        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT oi.*, COALESCE( NULLIF( oi.item_name, '' ), p.post_title, '(deleted item)' ) AS display_name
             FROM {$wpdb->prefix}rp_order_items oi
             LEFT JOIN {$wpdb->posts} p ON p.ID = oi.menu_item_id
             WHERE oi.order_id = %d
             ORDER BY oi.id ASC",
            $order_id
        ) );

        $shop = [
            'name'    => RP_Settings::get( 'restaurant_name', get_bloginfo( 'name' ) ),
            'address' => RP_Settings::get( 'restaurant_address', '' ),
            'phone'   => RP_Settings::get( 'restaurant_phone', '' ),
            'email'   => RP_Settings::get( 'restaurant_email', '' ),
            'footer'  => RP_Settings::get( 'bill_footer_note', '' ),
        ];

        $autoprint = sanitize_text_field( $_GET['autoprint'] ?? '' );

        include RP_PLUGIN_DIR . 'admin/views/bill.php';
    }
}
