<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Ajax_Kitchen {

    public function register(): void {
        add_action( 'wp_ajax_rp_update_kot_status', [ $this, 'update_status' ] );
        add_action( 'wp_ajax_rp_get_kitchen_orders', [ $this, 'get_orders' ] );
    }

    public function update_status(): void {
        $nonce = $_POST['nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'rp_admin_nonce' ) && ! wp_verify_nonce( $nonce, 'rp_kitchen_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_manage_kot' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        $kot_id = absint( $_POST['kot_id'] ?? 0 );
        $status = sanitize_text_field( $_POST['status'] ?? '' );

        $valid = array_keys( RP_Helpers::get_kot_statuses() );
        if ( ! $kot_id || ! in_array( $status, $valid, true ) ) {
            wp_send_json_error( [ 'message' => 'Invalid parameters.' ], 400 );
        }

        global $wpdb;

        $order_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}rp_kot WHERE id = %d",
            $kot_id
        ) );

        // Never allow a stale kitchen action to resurrect a cancelled order.
        if ( $order_id ) {
            $order_status = strtolower( trim( (string) $wpdb->get_var( $wpdb->prepare(
                "SELECT status FROM {$wpdb->prefix}rp_orders WHERE id = %d",
                $order_id
            ) ) ) );
            if ( in_array( $order_status, [ 'cancelled', 'canceled' ], true ) ) {
                $wpdb->update(
                    $wpdb->prefix . 'rp_kot',
                    [ 'status' => 'cancelled' ],
                    [ 'id' => $kot_id ],
                    [ '%s' ],
                    [ '%d' ]
                );
                wp_send_json_error( [ 'message' => 'This order has been cancelled and is no longer active in the kitchen.' ], 409 );
            }
        }

        $wpdb->update(
            $wpdb->prefix . 'rp_kot',
            [ 'status' => $status ],
            [ 'id' => $kot_id ],
            [ '%s' ],
            [ '%d' ]
        );

        // If KOT is ready, update order status to ready only when the order is
        // still active. This keeps Order -> Kitchen -> Order synchronization
        // one-way safe and prevents a cancelled order from becoming Ready again.
        if ( $status === 'ready' && $order_id ) {
            $wpdb->update(
                $wpdb->prefix . 'rp_orders',
                [ 'status' => 'ready' ],
                [ 'id' => $order_id ],
                [ '%s' ],
                [ '%d' ]
            );
        }

        $order_id_for_notice = (int) $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}rp_kot WHERE id = %d", $kot_id ) );
        if ( $order_id_for_notice ) RP_Notifications::add( 'kitchen', $status === 'ready' ? 'Order Ready' : 'Kitchen Update', sprintf( 'KOT #%d is now %s.', $kot_id, ucwords( str_replace( '_', ' ', $status ) ) ), $order_id_for_notice );
        RP_Activity::log( 'kot_status_changed', sprintf( 'KOT #%d status changed to %s', $kot_id, $status ), 'kot', $kot_id );

        wp_send_json_success( [ 'message' => 'KOT updated.' ] );
    }

    public function get_orders(): void {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_admin_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
        if ( ! current_user_can( 'rp_view_kot' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        global $wpdb;

        $kots = $wpdb->get_results(
            "SELECT k.*, o.table_number, o.notes as order_notes
             FROM {$wpdb->prefix}rp_kot k
             JOIN {$wpdb->prefix}rp_orders o ON o.id = k.order_id
             WHERE k.status IN ('pending','preparing','ready')
               AND LOWER(TRIM(o.status)) NOT IN ('cancelled','canceled')
             ORDER BY k.created_at ASC"
        );

        ob_start();
        if ( $kots ) {
            foreach ( $kots as $kot ) {
                $kot->items = $wpdb->get_results( $wpdb->prepare(
                    "SELECT oi.*, p.post_title
                     FROM {$wpdb->prefix}rp_order_items oi
                     JOIN {$wpdb->posts} p ON p.ID = oi.menu_item_id
                     WHERE oi.order_id = %d",
                    $kot->order_id
                ) );
                ?>
                <div class="col-md-4 col-lg-3 rp-kot-card" data-status="<?php echo esc_attr( $kot->status ); ?>">
                    <div class="card border-0 shadow-sm h-100 <?php echo $kot->status === 'pending' ? 'border-start border-4 border-warning' : ( $kot->status === 'preparing' ? 'border-start border-4 border-info' : 'border-start border-4 border-success' ); ?>">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <strong>KOT #<?php echo esc_html( $kot->id ); ?></strong>
                            <?php echo RP_Helpers::status_badge( $kot->status ); ?>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><small class="text-muted">Order #<?php echo esc_html( $kot->order_id ); ?> | Table <?php echo esc_html( $kot->table_number ?: 'Takeaway' ); ?></small></p>
                            <ul class="list-unstyled mb-2">
                                <?php foreach ( $kot->items as $item ) : ?>
                                    <li class="py-1 border-bottom"><strong><?php echo esc_html( $item->quantity ); ?>x</strong> <?php echo esc_html( $item->post_title ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ( $kot->order_notes ) : ?>
                                <p class="mb-0"><small class="text-danger"><strong>Note:</strong> <?php echo esc_html( $kot->order_notes ); ?></small></p>
                            <?php endif; ?>
                            <p class="mb-0 mt-2"><small class="text-muted"><?php echo esc_html( RP_Helpers::time_ago( $kot->created_at ) ); ?></small></p>
                        </div>
                        <?php if ( current_user_can( 'rp_manage_kot' ) ) : ?>
                            <div class="card-footer bg-transparent">
                                <?php if ( $kot->status === 'pending' ) : ?>
                                    <button class="btn btn-sm btn-info w-100 rp-kot-action" data-kot="<?php echo esc_attr( $kot->id ); ?>" data-status="preparing">Start Preparing</button>
                                <?php elseif ( $kot->status === 'preparing' ) : ?>
                                    <button class="btn btn-sm btn-success w-100 rp-kot-action" data-kot="<?php echo esc_attr( $kot->id ); ?>" data-status="ready">Mark Ready</button>
                                <?php elseif ( $kot->status === 'ready' ) : ?>
                                    <button class="btn btn-sm btn-dark w-100 rp-kot-action" data-kot="<?php echo esc_attr( $kot->id ); ?>" data-status="completed">Complete</button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-success">All clear! No pending kitchen orders.</div></div>';
        }
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }
}
