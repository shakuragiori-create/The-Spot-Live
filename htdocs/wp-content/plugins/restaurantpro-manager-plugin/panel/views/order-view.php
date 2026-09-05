<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$order_id = absint( get_query_var( 'rp_order_id' ) );
$order = $wpdb->get_row( $wpdb->prepare(
    "SELECT o.*, u.display_name as creator_name FROM {$wpdb->prefix}rp_orders o LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID WHERE o.id = %d", $order_id
) );

if ( ! $order ) {
    echo '<div class="empty-state"><div class="empty-icon"><?php echo rp_panel_icon('clipboard'); ?></div><p>Order not found.</p></div>';
    return;
}

$items = $wpdb->get_results( $wpdb->prepare(
    "SELECT oi.*, p.post_title as item_name FROM {$wpdb->prefix}rp_order_items oi LEFT JOIN {$wpdb->posts} p ON oi.menu_item_id = p.ID WHERE oi.order_id = %d", $order_id
) );

$next_map = [ 'new' => 'accepted', 'accepted' => 'preparing', 'preparing' => 'ready', 'ready' => 'completed' ];
$next_labels = [ 'new' => 'Accept Order', 'accepted' => 'Start Preparing', 'preparing' => 'Mark Ready', 'ready' => 'Complete Order' ];
$is_paid = RP_Accounts::is_paid( $order );
$current_status=strtolower(trim((string)$order->status)); if('canceled'===$current_status)$current_status='cancelled';
$next_status=$next_map[$current_status]??'';$next_label=$next_labels[$current_status]??'';
?>

<div class="flex-between mb-16">
    <h1 class="page-title" style="margin-bottom:0">Order #<?php echo esc_html( $order->id ); ?></h1>
    <div style="display:flex;gap:8px;align-items:center"><a href="<?php echo esc_url( home_url( '/panel/bill/' . $order->id ) ); ?>" target="_blank" class="btn btn-sm btn-primary">🖨 Print Bill</a><a href="<?php echo esc_url( home_url( '/panel/orders' ) ); ?>" class="btn btn-sm btn-outline">← Back</a></div>
</div>

<div class="p-card">
    <div class="flex-between mb-12">
        <div style="display:flex;gap:8px;align-items:center"><span class="badge badge-<?php echo esc_attr( $order->status ); ?>" style="font-size:.8rem;padding:6px 14px"><?php echo esc_html( ucfirst( $order->status ) ); ?></span><span class="badge" style="background:<?php echo $is_paid?'#dcfce7':'#fee2e2'; ?>;color:<?php echo $is_paid?'#166534':'#991b1b'; ?>"><?php echo $is_paid?'PAID':'NOT PAID'; ?></span></div>
        <span class="text-sm text-muted"><?php echo esc_html( wp_date( 'd M Y, g:i:s A', strtotime( $order->created_at ) ) ); ?></span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
        <div>
            <div class="text-sm text-muted">Table</div>
            <div class="fw-700"><?php echo $order->table_number ? 'Table ' . esc_html( $order->table_number ) : 'Takeaway'; ?></div>
        </div>
        <div>
            <div class="text-sm text-muted">Created By</div>
            <div class="fw-700"><?php echo esc_html( $order->creator_name ?? 'System' ); ?></div>
        </div>
    </div>
    <?php if ( $order->notes ) : ?>
        <div style="background:#f9f9f9;padding:10px 14px;border-radius:8px;font-size:.85rem;color:#666">
            <strong>Notes:</strong> <?php echo esc_html( $order->notes ); ?>
        </div>
    <?php endif; ?>
</div>

<div class="p-card">
    <h3 class="p-card-title mb-12">Items</h3>
    <?php foreach ( $items as $item ) : ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f5f5f5">
            <div>
                <div style="font-weight:600;font-size:.88rem"><?php echo esc_html( $item->item_name ?? 'Deleted Item' ); ?></div>
                <div class="text-sm text-muted">Qty: <?php echo esc_html( $item->quantity ); ?> × Rs.<?php echo number_format( (float) $item->price, 0 ); ?></div>
            </div>
            <div style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--br)">Rs.<?php echo number_format( $item->quantity * $item->price, 0 ); ?></div>
        </div>
    <?php endforeach; ?>
    <div class="flex-between" style="margin-top:14px;padding-top:14px;border-top:2px solid #eee">
        <span class="fw-700" style="font-size:1rem">Total</span>
        <span class="fw-700" style="font-family:'Poppins',sans-serif;font-size:1.3rem;color:var(--br)">Rs.<?php echo number_format( (float) $order->total, 0 ); ?></span>
    </div>
</div>

<?php if ( ! $is_paid && $current_status !== 'cancelled' ) : ?>
<div class="p-card" style="border:2px solid #fee2e2">
    <div class="flex-between"><div><strong>Payment Pending</strong><div class="text-sm text-muted">Mark this order paid to synchronize POS, Orders and Dashboard.</div></div><div style="display:flex;gap:8px"><select id="orderPaymentMethod" class="form-select" style="min-width:130px"><?php foreach ( RP_Billing::payment_methods() as $key=>$label ) : ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select><button type="button" class="btn btn-success" id="markOrderPaid" data-order="<?php echo (int)$order->id; ?>">Mark Paid</button></div></div>
</div>
<script>document.getElementById('markOrderPaid')?.addEventListener('click',function(){var b=this;b.disabled=true;var body=new URLSearchParams();body.append('action','rp_order_mark_paid');body.append('nonce',window.rpOrderViewData.nonce);body.append('order_id',b.dataset.order);body.append('payment_method',document.getElementById('orderPaymentMethod').value);fetch(window.rpOrderViewData.ajaxUrl,{method:'POST',credentials:'same-origin',body:body}).then(r=>r.json()).then(r=>{if(r&&r.success)location.reload();else{alert(r?.data?.message||'Could not save payment.');b.disabled=false;}}).catch(()=>{alert('Connection error.');b.disabled=false;});});</script>
<?php endif; ?>

<?php if ( $next_status && ! in_array($current_status,['completed','cancelled'],true) ) : ?>
<div style="display:flex;gap:10px;margin-top:16px">
    <button class="btn btn-success" style="flex:1;justify-content:center;padding:14px" id="advanceOrder" data-order="<?php echo esc_attr( $order->id ); ?>" data-status="<?php echo esc_attr( $next_status ); ?>">
        <?php echo esc_html( $next_label ); ?>
    </button>
    <?php if ( $current_status !== 'cancelled' ) : ?>
        <button class="btn btn-danger btn-sm" id="cancelOrder" data-order="<?php echo esc_attr( $order->id ); ?>">Cancel</button>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
window.rpOrderViewData = {
    ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    nonce: '<?php echo wp_create_nonce( 'rp_orders_nonce' ); ?>'
};
</script>
