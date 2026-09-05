<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$order_id = absint( $order_id ?? get_query_var( 'rp_order_id' ) );
$order = $wpdb->get_row( $wpdb->prepare(
    "SELECT o.*, u.display_name AS creator_name
     FROM {$wpdb->prefix}rp_orders o
     LEFT JOIN {$wpdb->users} u ON o.created_by = u.ID
     WHERE o.id = %d",
    $order_id
) );

if ( ! $order ) {
    status_header( 404 );
    echo '<div style="font-family:Arial,sans-serif;padding:30px;text-align:center">Order not found.</div>';
    return;
}

$items = $wpdb->get_results( $wpdb->prepare(
    "SELECT oi.*, COALESCE(NULLIF(oi.item_name,''), p.post_title) AS display_name
     FROM {$wpdb->prefix}rp_order_items oi
     LEFT JOIN {$wpdb->posts} p ON oi.menu_item_id = p.ID
     WHERE oi.order_id = %d
     ORDER BY oi.id ASC",
    $order_id
) );

$restaurant_name = RP_Settings::get( 'restaurant_name', 'The Spot' );
$restaurant_phone = RP_Settings::get( 'restaurant_phone', '' );
$restaurant_address = RP_Settings::get( 'restaurant_address', '' );
$currency = 'Rs.';
$auto_print = ! empty( $_GET['autoprint'] );
$pdf_url = home_url( '/panel/bill/' . $order->id . '/pdf' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" sizes="512x512" href="<?php echo esc_url( home_url( '/wp-content/themes/the-spot-theme/assets/images/favicon.png' ) ); ?>?v=2.3.0">
<link rel="apple-touch-icon" href="<?php echo esc_url( home_url( '/wp-content/themes/the-spot-theme/assets/images/favicon.png' ) ); ?>?v=2.3.0">
<title><?php echo esc_html( $order->invoice_no ?: 'Bill #' . $order->id ); ?> — <?php echo esc_html( $restaurant_name ); ?></title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f1f3f5;color:#111;font-family:Arial,Helvetica,sans-serif}
.bill-wrap{width:80mm;max-width:100%;margin:24px auto;background:#fff;padding:16px 12px;box-shadow:0 4px 20px rgba(0,0,0,.10)}
.center{text-align:center}.brand{font-size:21px;font-weight:800;margin-bottom:3px}.muted{color:#666;font-size:11px;line-height:1.45}
.meta{margin:12px 0;padding:9px 0;border-top:1px dashed #aaa;border-bottom:1px dashed #aaa;font-size:11px;line-height:1.55}
table{width:100%;border-collapse:collapse;font-size:11px}.items th{font-size:10px;text-transform:uppercase;border-bottom:1px solid #222;padding:5px 0;text-align:left}.items th:last-child,.items td:last-child{text-align:right}.items td{padding:6px 0;vertical-align:top;border-bottom:1px dotted #ddd}.item-name{padding-right:5px}.qty{white-space:nowrap;color:#555}.totals{margin-top:8px;border-top:1px solid #222;padding-top:5px}.total-row{display:flex;justify-content:space-between;padding:3px 0;font-size:11px}.grand{font-size:15px;font-weight:800;border-top:1px dashed #888;margin-top:4px;padding-top:7px}.footer{text-align:center;border-top:1px dashed #aaa;margin-top:12px;padding-top:10px;font-size:11px;line-height:1.5}.print-actions{width:80mm;max-width:100%;margin:0 auto 14px;display:flex;gap:7px}.print-actions button,.print-actions a{flex:1;border:0;border-radius:7px;padding:9px;cursor:pointer;text-align:center;text-decoration:none;font-size:12px}.print-actions button{background:#111;color:#fff}.print-actions a{background:#e9ecef;color:#111}
@media print{body{background:#fff}.bill-wrap{width:80mm;margin:0;padding:8px;box-shadow:none}.print-actions{display:none}@page{size:80mm auto;margin:0}}
</style>
</head>
<body>
<div class="bill-wrap">
    <div class="center">
        <div class="brand"><?php echo esc_html( $restaurant_name ); ?></div>
        <?php if ( $restaurant_address ) : ?><div class="muted"><?php echo esc_html( $restaurant_address ); ?></div><?php endif; ?>
        <?php if ( $restaurant_phone ) : ?><div class="muted">Tel: <?php echo esc_html( $restaurant_phone ); ?></div><?php endif; ?>
        <div class="muted">PAN No.: 621781410</div>
        <div class="muted" style="margin-top:5px">SALES RECEIPT</div>
    </div>

    <div class="meta">
        <div><strong>Bill:</strong> <?php echo esc_html( $order->invoice_no ?: RP_Billing::invoice_no( $order->id ) ); ?></div>
        <div><strong>Order:</strong> #<?php echo esc_html( $order->id ); ?> &nbsp; <strong>Date:</strong> <?php echo esc_html( wp_date( 'd M Y, g:i A', strtotime( $order->created_at ) ) ); ?></div>
        <div><strong>Type:</strong> <?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $order->order_type ) ) ); ?>
            <?php if ( ! empty( $order->table_number ) ) : ?> &nbsp; <strong>Table:</strong> <?php echo esc_html( $order->table_number ); ?><?php endif; ?></div>
        <?php if ( ! empty( $order->customer_name ) ) : ?><div><strong>Customer:</strong> <?php echo esc_html( $order->customer_name ); ?></div><?php endif; ?>
        <?php if ( ! empty( $order->customer_phone ) ) : ?><div><strong>Phone:</strong> <?php echo esc_html( $order->customer_phone ); ?></div><?php endif; ?>
    </div>

    <table class="items">
        <thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ( $items as $item ) : ?>
            <tr>
                <td class="item-name"><?php echo esc_html( $item->display_name ?: 'Item' ); ?></td>
                <td class="qty"><?php echo esc_html( $item->quantity ); ?></td>
                <td><?php echo esc_html( $currency . number_format( (float) $item->price * (int) $item->quantity, 2 ) ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row"><span>Subtotal</span><span><?php echo esc_html( $currency . number_format( (float) $order->subtotal, 2 ) ); ?></span></div>
        <?php if ( (float) $order->discount_amount > 0 ) : ?><div class="total-row"><span>Discount</span><span>-<?php echo esc_html( $currency . number_format( (float) $order->discount_amount, 2 ) ); ?></span></div><?php endif; ?>
        <?php if ( (float) $order->service_amount > 0 ) : ?><div class="total-row"><span>Service (<?php echo esc_html( $order->service_rate ); ?>%)</span><span><?php echo esc_html( $currency . number_format( (float) $order->service_amount, 2 ) ); ?></span></div><?php endif; ?>
        <?php if ( (float) $order->vat_amount > 0 ) : ?><div class="total-row"><span>VAT (<?php echo esc_html( $order->vat_rate ); ?>%)</span><span><?php echo esc_html( $currency . number_format( (float) $order->vat_amount, 2 ) ); ?></span></div><?php endif; ?>
        <div class="total-row grand"><span>GRAND TOTAL</span><span><?php echo esc_html( $currency . number_format( (float) $order->total, 2 ) ); ?></span></div>
        <?php if ( (float) $order->amount_paid > 0 ) : ?><div class="total-row"><span>Paid</span><span><?php echo esc_html( $currency . number_format( (float) $order->amount_paid, 2 ) ); ?></span></div><?php endif; ?>
        <?php if ( (float) $order->change_due > 0 ) : ?><div class="total-row"><span>Change</span><span><?php echo esc_html( $currency . number_format( (float) $order->change_due, 2 ) ); ?></span></div><?php endif; ?>
    </div>

    <div class="footer">
        <strong>Payment:</strong> <?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $order->payment_method ) ) ); ?><br>
        Thank you for visiting <?php echo esc_html( $restaurant_name ); ?>!<br>
        Please visit us again.
    </div>
</div>

<div class="print-actions">
    <button type="button" onclick="window.print()">🖨 Print</button>
    <button type="button" id="sharePdfBtn">↗ Share PDF</button>
    <a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank">PDF</a>
    <a href="<?php echo esc_url( home_url( '/panel/orders/' . $order->id ) ); ?>">Back</a>
</div>
<script>
(function(){var btn=document.getElementById('sharePdfBtn');if(!btn)return;btn.addEventListener('click',async function(){var url=<?php echo wp_json_encode($pdf_url); ?>;try{var r=await fetch(url,{credentials:'same-origin'});var blob=await r.blob();var file=new File([blob],<?php echo wp_json_encode(($order->invoice_no?:('bill-'.$order->id)).'.pdf'); ?>,{type:'application/pdf'});if(navigator.share&&navigator.canShare&&navigator.canShare({files:[file]})){await navigator.share({title:'<?php echo esc_js($restaurant_name); ?> Bill',text:'Bill <?php echo esc_js($order->invoice_no ?: '#'.$order->id); ?>',files:[file]});return;}if(navigator.share){await navigator.share({title:'<?php echo esc_js($restaurant_name); ?> Bill',url:url});return;}window.open(url,'_blank');}catch(e){window.open(url,'_blank');}});})();
</script>

<?php if ( $auto_print ) : ?>
<script>window.addEventListener('load',function(){setTimeout(function(){window.print();},250);});</script>
<?php endif; ?>
</body>
</html>
