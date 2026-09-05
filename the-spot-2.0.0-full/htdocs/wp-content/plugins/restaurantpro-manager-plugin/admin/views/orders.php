<?php if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'next_status_helper' ) ) {
    function next_status_helper( string $status ): string {
        $map = [ 'new' => 'accepted', 'accepted' => 'preparing', 'preparing' => 'ready', 'ready' => 'completed' ];
        return $map[ $status ] ?? '';
    }
    function next_status_label_helper( string $status ): string {
        $map = [ 'new' => 'Accept', 'accepted' => 'Start Preparing', 'preparing' => 'Mark Ready', 'ready' => 'Complete' ];
        return $map[ $status ] ?? '';
    }
}
?>
<div class="wrap rp-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Orders</h1>
        <div class="d-flex gap-2">
            <?php if ( current_user_can( 'rp_manage_orders' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-pos' ) ); ?>" class="btn btn-primary">🧾 New Sale (POS)</a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-orders&action=new' ) ); ?>" class="btn btn-outline-dark">+ New Order</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-3">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-orders' ) ); ?>" class="btn btn-sm <?php echo ! $status_filter ? 'btn-dark' : 'btn-outline-secondary'; ?>">All</a>
        <?php foreach ( RP_Helpers::get_order_statuses() as $key => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( "admin.php?page=rp-orders&status={$key}" ) ); ?>" class="btn btn-sm <?php echo $status_filter === $key ? 'btn-dark' : 'btn-outline-secondary'; ?>"><?php echo esc_html( $label ); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Invoice</th>
                            <th>Table</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Created By</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $orders ) : ?>
                            <?php foreach ( $orders as $order ) : ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html( $order->id ); ?></strong></td>
                                    <td class="small text-muted"><?php echo esc_html( $order->invoice_no ?: '—' ); ?></td>
                                    <td>
                                        <?php
                                        if ( ! empty( $order->table_name ) ) {
                                            echo esc_html( $order->table_name );
                                        } elseif ( $order->table_number ) {
                                            echo 'Table ' . esc_html( $order->table_number );
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo RP_Helpers::status_badge( $order->status ); ?></td>
                                    <td><?php echo esc_html( RP_Helpers::format_price( (float) $order->total ) ); ?></td>
                                    <td><?php echo esc_html( $order->creator_name ?? 'System' ); ?></td>
                                    <td><?php echo esc_html( RP_Helpers::time_ago( $order->created_at ) ); ?></td>
                                    <td class="text-nowrap">
                                        <a href="<?php echo esc_url( admin_url( "admin.php?page=rp-orders&action=bill&order_id={$order->id}" ) ); ?>" class="btn btn-sm btn-outline-dark">Bill</a>
                                        <a href="<?php echo esc_url( admin_url( "admin.php?page=rp-orders&action=edit&order_id={$order->id}" ) ); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        <?php if ( current_user_can( 'rp_manage_orders' ) && ! in_array( $order->status, [ 'completed', 'cancelled' ], true ) ) : ?>
                                            <button class="btn btn-sm btn-outline-success rp-update-order-status" data-order="<?php echo esc_attr( $order->id ); ?>" data-status="<?php echo esc_attr( next_status_helper( $order->status ) ); ?>">
                                                <?php echo esc_html( next_status_label_helper( $order->status ) ); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No orders yet. Take your first order from the <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-pos' ) ); ?>">POS</a>.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
