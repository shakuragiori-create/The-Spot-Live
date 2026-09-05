<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$today = current_time( 'Y-m-d' );
$order_date = sanitize_text_field( $_GET['date'] ?? $today );
if ( $order_date !== 'all' && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $order_date ) ) $order_date = $today;
$status_filter = strtolower( trim( sanitize_text_field( $_GET['status'] ?? '' ) ) );
$allowed_statuses = [ 'new','accepted','preparing','ready','completed','cancelled','canceled' ];

$where = '1=1';
$params = [];
if ( $order_date !== 'all' ) {
    // Match either the order timestamp OR its KOT timestamp. This keeps Orders in sync
    // with Kitchen even when an older installation has a timezone mismatch in one table.
    $where .= " AND (DATE(o.created_at)=%s OR EXISTS (SELECT 1 FROM {$wpdb->prefix}rp_kot kk WHERE kk.order_id=o.id AND DATE(kk.created_at)=%s))";
    $params[] = $order_date;
    $params[] = $order_date;
}
if ( $status_filter && in_array( $status_filter, $allowed_statuses, true ) ) {
    if ( in_array( $status_filter, [ 'cancelled','canceled' ], true ) ) {
        $where .= " AND LOWER(TRIM(o.status)) IN ('cancelled','canceled')";
    } else {
        $where .= ' AND LOWER(TRIM(o.status))=%s';
        $params[] = $status_filter;
    }
}
$sql = "SELECT o.*, u.display_name AS creator_name FROM {$wpdb->prefix}rp_orders o LEFT JOIN {$wpdb->users} u ON o.created_by=u.ID WHERE {$where} ORDER BY o.created_at DESC LIMIT 300";
$orders = $params ? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) : $wpdb->get_results( $sql );
$statuses = [ ''=>'All', 'new'=>'New', 'accepted'=>'Accepted', 'preparing'=>'Preparing', 'ready'=>'Ready', 'completed'=>'Done', 'cancelled'=>'Cancelled' ];
?>
<div class="flex-between mb-16">
  <div><h1 class="page-title" style="margin-bottom:2px">Orders</h1><div class="text-sm text-muted"><span id="rpOrdersDateLabel"><?php echo $order_date==='all' ? 'All dates' : ( $order_date === $today ? 'Today' : esc_html( wp_date('d M Y', strtotime($order_date)) ) ); ?></span> • <span id="rpOrdersSyncState">Loading…</span></div></div>
  <form method="get" style="display:flex;gap:8px;align-items:center">
    <select name="date" class="form-input"><option value="<?php echo esc_attr($today); ?>" <?php selected($order_date,$today); ?>>Today</option><option value="all" <?php selected($order_date,'all'); ?>>All dates</option></select>
    <?php if($status_filter): ?><input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>"><?php endif; ?><button class="btn btn-sm btn-outline">Go</button>
  </form>
</div>
<div class="filter-tabs"><?php foreach($statuses as $key=>$label): $args=['date'=>$order_date]; if($key)$args['status']=$key; ?><a href="<?php echo esc_url(add_query_arg($args,home_url('/panel/orders'))); ?>" class="filter-tab <?php echo $status_filter===$key?'active':''; ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></div>
<div id="rpOrdersList" class="p-card rp-orders-list" style="padding:12px 16px">
<?php if($orders): foreach($orders as $o): $paid=class_exists('RP_Accounts')?RP_Accounts::is_paid($o):false; $raw=strtolower(trim((string)$o->status)); $status=in_array($raw,['cancelled','canceled'],true)?'cancelled':$raw; ?>
<a href="<?php echo esc_url(home_url('/panel/orders/'.(int)$o->id)); ?>" class="order-item rp-pay-order <?php echo $paid?'is-paid':'is-unpaid'; ?>" data-order-id="<?php echo (int)$o->id; ?>">
  <span class="order-num">#<?php echo (int)$o->id; ?></span>
  <div class="order-meta"><div class="order-meta-top"><span class="badge badge-<?php echo esc_attr($status); ?>"><?php echo esc_html($status==='cancelled'?'Cancelled':ucfirst($status)); ?></span><span class="badge" style="background:<?php echo $paid?'#dcfce7':'#fee2e2'; ?>;color:<?php echo $paid?'#166534':'#991b1b'; ?>"><?php echo $paid?'PAID':'NOT PAID'; ?></span><?php if($o->table_number): ?><span class="order-table">T<?php echo (int)$o->table_number; ?></span><?php endif; ?></div><span class="text-sm text-muted"><?php echo esc_html(wp_date('g:i:s A',strtotime($o->created_at))); ?> • <?php echo esc_html($o->customer_name?:'Walk-in Customer'); ?></span></div>
  <span style="display:flex;align-items:center;gap:8px"><span class="order-total">Rs.<?php echo number_format((float)$o->total,0); ?></span><span class="btn btn-sm btn-outline" data-bill="<?php echo esc_url(home_url('/panel/bill/'.(int)$o->id)); ?>">🧾</span></span>
</a>
<?php endforeach; else: ?><div class="empty-state"><div class="empty-icon">📋</div><p>No orders found for this date</p></div><?php endif; ?>
</div>
<a href="<?php echo esc_url(home_url('/panel/orders/new')); ?>" class="btn-fab" aria-label="New Order">+</a>
<script>window.rpOrdersData=<?php echo wp_json_encode(['ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('rp_orders_nonce'),'date'=>$order_date,'status'=>$status_filter]); ?>;</script>
