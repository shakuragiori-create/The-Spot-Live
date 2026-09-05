<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$today = current_time( 'Y-m-d' );
$orders_today = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}rp_orders WHERE DATE(created_at) = %s AND LOWER(TRIM(status)) NOT IN ('cancelled','canceled')", $today
) );
$revenue_today = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(total), 0) FROM {$wpdb->prefix}rp_orders WHERE DATE(created_at) = %s AND LOWER(TRIM(status)) NOT IN ('cancelled','canceled') AND (paid_at IS NOT NULL OR amount_paid >= total)", $today
) );
$pending_kot = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}rp_kot k
     JOIN {$wpdb->prefix}rp_orders o ON k.order_id = o.id
     WHERE k.status IN ('pending','preparing')
       AND LOWER(TRIM(o.status)) NOT IN ('cancelled','canceled')"
);
$active_tables = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}rp_tables WHERE status = 'occupied'"
);
$total_tables = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}rp_tables"
);

$recent_orders = $wpdb->get_results(
    "SELECT o.*, u.display_name as creator_name
     FROM {$wpdb->prefix}rp_orders o
     LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID
     WHERE DATE(o.created_at) = '" . esc_sql( $today ) . "'
     ORDER BY o.created_at DESC LIMIT 8"
);
?>

<h1 class="page-title">Dashboard</h1>

<div class="stat-grid">
    <div class="stat-card gold">
        <div class="stat-icon"><?php echo rp_panel_icon('clipboard'); ?></div>
        <div class="stat-value"><?php echo $orders_today; ?></div>
        <div class="stat-label">Orders Today</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><?php echo rp_panel_icon('wallet'); ?></div>
        <div class="stat-value">Rs.<?php echo number_format( $revenue_today, 0 ); ?></div>
        <div class="stat-label">Revenue Today</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><?php echo rp_panel_icon('flame'); ?></div>
        <div class="stat-value"><?php echo $pending_kot; ?></div>
        <div class="stat-label">Pending KOT</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><?php echo rp_panel_icon('chair'); ?></div>
        <div class="stat-value"><?php echo $active_tables; ?>/<?php echo $total_tables; ?></div>
        <div class="stat-label">Tables Occupied</div>
    </div>
</div>

<div class="p-card">
    <div class="p-card-header">
        <h3 class="p-card-title">Orders by Hour</h3>
    </div>
    <div style="position:relative;height:200px;width:100%">
        <canvas id="hourlyChart"></canvas>
    </div>
</div>

<div class="p-card">
    <div class="p-card-header">
        <h3 class="p-card-title">Recent Orders</h3>
        <a href="<?php echo esc_url( home_url( '/panel/orders' ) ); ?>" class="btn btn-sm btn-outline">View All</a>
    </div>
    <?php if ( $recent_orders ) : ?>
        <?php foreach ( $recent_orders as $order ) : ?>
            <a href="<?php echo esc_url( home_url( "/panel/orders/{$order->id}" ) ); ?>" class="order-item rp-pay-order <?php echo RP_Accounts::is_paid($order) ? 'is-paid' : 'is-unpaid'; ?>">
                <span class="order-num">#<?php echo esc_html( $order->id ); ?></span>
                <div class="order-meta">
                    <div class="order-meta-top">
                        <span class="badge badge-<?php echo esc_attr( $order->status ); ?>"><?php echo esc_html( ucfirst( $order->status ) ); ?></span>
                        <?php if ( $order->table_number ) : ?>
                            <span class="order-table">Table <?php echo esc_html( $order->table_number ); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="text-sm text-muted"><?php echo esc_html( $order->creator_name ?? 'System' ); ?> · <?php echo esc_html( wp_date( 'g:i A', strtotime( $order->created_at ) ) ); ?> · <?php echo esc_html( human_time_diff( strtotime( $order->created_at ), current_time( 'timestamp' ) ) ); ?> ago</span>
                </div>
                <span class="order-total">Rs.<?php echo number_format( (float) $order->total, 0 ); ?></span>
            </a>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="empty-state">
            <div class="empty-icon"><?php echo rp_panel_icon('clipboard'); ?></div>
            <p>No orders yet today</p>
        </div>
    <?php endif; ?>
</div>

<script>
window.rpDashData = {
    ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    nonce: '<?php echo wp_create_nonce( 'rp_dashboard_nonce' ); ?>'
};
</script>
