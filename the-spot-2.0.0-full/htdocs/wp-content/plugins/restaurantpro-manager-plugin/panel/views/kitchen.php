<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$kots = $wpdb->get_results(
    "SELECT k.*, o.table_number, o.notes as order_notes
     FROM {$wpdb->prefix}rp_kot k
     JOIN {$wpdb->prefix}rp_orders o ON k.order_id = o.id
     WHERE k.status IN ('pending','preparing')
       AND LOWER(TRIM(o.status)) NOT IN ('cancelled','canceled')
     ORDER BY k.created_at ASC"
);

foreach ( $kots as &$kot ) {
    $kot->items = $wpdb->get_results( $wpdb->prepare(
        "SELECT oi.quantity, oi.notes, p.post_title as item_name
         FROM {$wpdb->prefix}rp_order_items oi
         LEFT JOIN {$wpdb->posts} p ON oi.menu_item_id = p.ID
         WHERE oi.order_id = %d", $kot->order_id
    ) );
}
unset( $kot );
?>

<h1 class="page-title">Kitchen Display</h1>

<div class="filter-tabs mb-16">
    <button class="filter-tab active" data-filter="all">All (<?php echo count( $kots ); ?>)</button>
    <button class="filter-tab" data-filter="pending">Pending</button>
    <button class="filter-tab" data-filter="preparing">Preparing</button>
</div>

<div class="kot-grid" id="kotGrid">
    <?php if ( $kots ) : ?>
        <?php foreach ( $kots as $kot ) : ?>
            <div class="kot-card status-<?php echo esc_attr( $kot->status ); ?>" data-status="<?php echo esc_attr( $kot->status ); ?>" data-kot="<?php echo esc_attr( $kot->id ); ?>">
                <div class="kot-card-header">
                    <div>
                        <span class="kot-order-num">#<?php echo esc_html( $kot->order_id ); ?></span>
                        <?php if ( $kot->table_number ) : ?>
                            <span class="text-sm text-muted" style="margin-left:8px">Table <?php echo esc_html( $kot->table_number ); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="kot-time"><?php echo esc_html( human_time_diff( strtotime( $kot->created_at ), current_time( 'timestamp' ) ) ); ?></span>
                </div>
                <div class="kot-card-body">
                    <?php foreach ( $kot->items as $item ) : ?>
                        <div class="kot-item">
                            <span><span class="kot-item-qty"><?php echo esc_html( $item->quantity ); ?>×</span> <?php echo esc_html( $item->item_name ?? 'Item' ); ?></span>
                            <?php if ( $item->notes ) : ?>
                                <span class="text-sm text-muted"><?php echo esc_html( $item->notes ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ( $kot->order_notes ) : ?>
                        <div style="margin-top:8px;padding:6px 10px;background:#fff8e1;border-radius:6px;font-size:.78rem;color:#f57f17">
                            📝 <?php echo esc_html( $kot->order_notes ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="kot-card-footer">
                    <?php if ( $kot->status === 'pending' ) : ?>
                        <button class="btn btn-sm btn-primary btn-block kot-action" data-kot="<?php echo esc_attr( $kot->id ); ?>" data-status="preparing">Start Preparing</button>
                    <?php elseif ( $kot->status === 'preparing' ) : ?>
                        <button class="btn btn-sm btn-success btn-block kot-action" data-kot="<?php echo esc_attr( $kot->id ); ?>" data-status="ready">Mark Ready</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="empty-state" style="grid-column:1/-1">
            <div class="empty-icon"><?php echo rp_panel_icon('flame'); ?></div>
            <p>All caught up! No pending orders.</p>
        </div>
    <?php endif; ?>
</div>

<script>
window.rpKitchenData = {
    ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    nonce: '<?php echo wp_create_nonce( 'rp_kitchen_nonce' ); ?>',
    refreshUrl: '<?php echo esc_url( home_url( '/panel/kitchen' ) ); ?>'
};
</script>
