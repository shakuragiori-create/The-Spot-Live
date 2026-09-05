<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$tables = $wpdb->get_results( "SELECT id, table_name, status FROM {$wpdb->prefix}rp_tables ORDER BY table_name ASC" );

$categories = get_terms([
    'taxonomy' => 'rp_menu_category',
    'hide_empty' => true,
]);
if ( is_wp_error( $categories ) ) $categories = [];

usort( $categories, function( $a, $b ) {
    $oa = (int) get_term_meta( $a->term_id, '_rp_order', true );
    $ob = (int) get_term_meta( $b->term_id, '_rp_order', true );
    return $oa === $ob ? strcmp( $a->name, $b->name ) : $oa <=> $ob;
});

$items = get_posts([
    'post_type' => 'rp_menu_item',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
    'meta_query' => [
        'relation' => 'OR',
        [ 'key' => '_rp_is_available', 'value' => '1' ],
        [ 'key' => '_rp_is_available', 'compare' => 'NOT EXISTS' ],
    ],
]);

$billing = RP_Billing::defaults();
?>

<div class="flex-between mb-16">
    <h1 class="page-title" style="margin-bottom:0">Point of Sale</h1>
    <span class="text-muted small"><?php echo esc_html( wp_date( 'd M Y, g:i A' ) ); ?></span>
</div>

<div class="pos-live-bar">
    <div class="pos-live-heading"><div><strong>Ongoing Orders</strong><span class="pos-live-sub">Live POS status</span></div><span class="pos-live-count" id="posOngoingCount">0</span></div>
    <div class="pos-live-orders" id="posOngoingOrders"><div class="pos-live-empty">No ongoing orders.</div></div>
</div>
<div class="pos-activity-card" id="posActivityCard">
    <div class="pos-activity-head"><strong>POS Notifications</strong><button type="button" class="btn btn-sm btn-outline" id="posActivityClear">Clear View</button></div>
    <div class="pos-activity-list" id="posActivityList"><div class="pos-live-empty">Waiting for order updates…</div></div>
</div>
<div class="pos-bill-modal" id="posBillModal" aria-hidden="true">
    <div class="pos-bill-backdrop" data-close-bill="1"></div>
    <div class="pos-bill-dialog"><div class="pos-bill-dialog-head"><strong>Visual Bill</strong><button type="button" class="pos-bill-close" id="posBillClose">×</button></div><iframe id="posBillFrame" title="Order Bill" src="about:blank"></iframe></div>
</div>

<div class="pos-layout">
    <!-- LEFT: Item Picker -->
    <div class="pos-left">
        <div class="p-card">
            <div class="form-group">
                <input type="search" id="posSearch" class="form-input" placeholder="Search items..." autocomplete="off">
            </div>

            <div class="filter-tabs" id="catFilter">
                <button type="button" class="filter-tab active" data-cat="all">All</button>
                <?php foreach ( $categories as $cat ) :
                    $icon = get_term_meta( $cat->term_id, '_rp_icon', true );
                ?>
                    <button type="button" class="filter-tab" data-cat="<?php echo esc_attr( $cat->slug ); ?>">
                        <?php echo $icon ? esc_html( $icon ) . ' ' : ''; ?><?php echo esc_html( $cat->name ); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="pos-item-grid" id="posItemGrid">
                <?php foreach ( $items as $item ) :
                    $price = get_post_meta( $item->ID, '_rp_price', true );
                    $veg = get_post_meta( $item->ID, '_rp_is_veg', true ) === '1';
                    $terms = wp_get_object_terms( $item->ID, 'rp_menu_category', [ 'fields' => 'slugs' ] );
                    $slugs = is_wp_error( $terms ) ? [] : implode( ' ', $terms );
                ?>
                    <button type="button" class="pos-item-btn"
                            data-id="<?php echo esc_attr( $item->ID ); ?>"
                            data-name="<?php echo esc_attr( $item->post_title ); ?>"
                            data-price="<?php echo esc_attr( $price ); ?>"
                            data-cats="<?php echo esc_attr( $slugs ); ?>"
                            data-veg="<?php echo $veg ? '1' : '0'; ?>">
                        <span class="pos-item-name">
                            <?php echo esc_html( $item->post_title ); ?>
                            <?php if ( $veg ) : ?><span class="veg-dot" title="Vegetarian"></span><?php endif; ?>
                        </span>
                        <span class="pos-item-price">Rs.<?php echo esc_html( number_format( (float) $price, 0 ) ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <p class="text-muted small text-center mt-12" id="posNoItems" style="display:none">No items found.</p>
        </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="pos-right">
        <div class="p-card pos-cart-card">
            <div class="pos-cart-header">
                <strong>Current Bill</strong>
                <button type="button" class="btn btn-sm btn-outline-light" id="clearCart">Clear</button>
            </div>

            <div class="pos-cart-body">
                <div id="cartItems" class="cart-items">
                    <p class="text-muted small text-center py-20" id="cartEmpty">Tap items to add to bill</p>
                </div>

                <button type="button" class="btn btn-sm btn-outline btn-block mb-12" id="addCustomLine">+ Custom Line</button>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label small">Order Type</label>
                        <select class="form-select form-select-sm" id="orderType">
                            <?php foreach ( RP_Billing::order_types() as $k => $v ) : ?>
                                <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label small">Table</label>
                        <select class="form-select form-select-sm" id="orderTable">
                            <option value="0">— None —</option>
                            <?php foreach ( $tables as $t ) : ?>
                                <option value="<?php echo esc_attr( $t->id ); ?>"><?php echo esc_html( $t->table_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label small">Customer</label>
                        <input type="text" class="form-input form-input-sm" id="customerName" placeholder="Name">
                    </div>
                    <div class="form-col">
                        <label class="form-label small">Phone</label>
                        <input type="tel" class="form-input form-input-sm" id="customerPhone" placeholder="Phone">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label small">Discount</label>
                        <select class="form-select form-select-sm" id="discountType">
                            <option value="none">None</option>
                            <option value="percent">Percent %</option>
                            <option value="amount">Amount</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label small">Value</label>
                        <input type="number" class="form-input form-input-sm" id="discountValue" value="0" min="0" step="0.01" disabled>
                    </div>
                </div>

                <div class="pos-charges">
                    <div class="charge-row">
                        <div class="form-check">
                            <input type="checkbox" id="serviceEnabled" <?php checked( $billing['service_enabled'] ); ?>>
                            <label for="serviceEnabled">Service</label>
                        </div>
                        <div class="input-group-sm">
                            <input type="number" id="serviceRate" value="<?php echo esc_attr( $billing['service_rate'] ); ?>" min="0" max="100" step="0.01">
                            <span>%</span>
                        </div>
                    </div>
                    <div class="charge-row">
                        <div class="form-check">
                            <input type="checkbox" id="vatEnabled" <?php checked( $billing['vat_enabled'] ); ?>>
                            <label for="vatEnabled">VAT</label>
                        </div>
                        <div class="input-group-sm">
                            <input type="number" id="vatRate" value="<?php echo esc_attr( $billing['vat_rate'] ); ?>" min="0" max="100" step="0.01">
                            <span>%</span>
                        </div>
                    </div>
                </div>

                <div class="pos-totals">
                    <div class="total-row"><span>Subtotal</span><span id="totalSubtotal">Rs.0</span></div>
                    <div class="total-row text-danger" id="discountRow" style="display:none"><span>Discount</span><span id="totalDiscount">-Rs.0</span></div>
                    <div class="total-row" id="serviceRow" style="display:none"><span>Service <small id="serviceRateLabel"></small></span><span id="totalService">Rs.0</span></div>
                    <div class="total-row" id="vatRow" style="display:none"><span>VAT <small id="vatRateLabel"></small></span><span id="totalVat">Rs.0</span></div>
                    <div class="total-row total-grand"><strong>GRAND TOTAL</strong><strong id="totalGrand">Rs.0</strong></div>
                </div>

                <div class="form-row mt-12">
                    <div class="form-col">
                        <label class="form-label small">Payment</label>
                        <select class="form-select form-select-sm" id="paymentMethod">
                            <?php foreach ( RP_Billing::payment_methods() as $k => $v ) : ?>
                                <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $v ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label class="form-label small">Tendered</label>
                        <input type="number" class="form-input form-input-sm" id="amountTendered" min="0" step="1" placeholder="0">
                    </div>
                </div>
                <p class="text-success fw-700 small mt-8" id="changeRow" style="display:none">Change: <span id="changeAmount">Rs.0</span></p>

                <div class="form-check mt-12">
                    <input type="checkbox" id="sendKot" checked>
                    <label for="sendKot">Send kitchen ticket (KOT)</label>
                </div>
            </div>

            <div class="pos-cart-footer">
                <button type="button" class="btn btn-primary btn-lg btn-block" id="chargeBtn">💰 Charge & Print Bill</button>
                <button type="button" class="btn btn-outline btn-sm btn-block mt-8" id="holdBtn">Send to Kitchen (Unpaid)</button>
            </div>

            <p class="small mt-8 mb-0" id="posMsg"></p>
        </div>
    </div>
</div>

<style>.pos-live-bar{background:var(--w);border:1px solid #e8e8e8;border-radius:12px;margin-bottom:14px;overflow:hidden;box-shadow:0 3px 12px rgba(0,0,0,.04)}.pos-live-heading{padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #eee}.pos-live-sub{display:block;font-size:11px;color:#888;margin-top:2px}.pos-live-count{min-width:28px;height:28px;border-radius:20px;background:#fff3cd;color:#7a5600;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800}.pos-live-orders{display:flex;gap:10px;overflow-x:auto;padding:10px}.pos-live-empty{padding:12px;color:#999;font-size:12px;text-align:center;width:100%}.pos-live-order{min-width:245px;border:2px solid #e6e6e6;border-radius:10px;padding:11px;background:#fff}.pos-live-order.is-unpaid{border-color:#ef4444;animation:rpOrderRed 1.4s infinite}.pos-live-order.is-paid{border-color:#22c55e;animation:rpOrderGreen 1.8s infinite}.pos-live-top{display:flex;justify-content:space-between;gap:8px;align-items:center}.pos-live-invoice{font-weight:800;font-size:13px}.pos-status-pill{font-size:10px;font-weight:800;padding:4px 7px;border-radius:20px;text-transform:uppercase}.pos-status-new{background:#fff3cd;color:#8a6200}.pos-status-accepted{background:#e0f2fe;color:#075985}.pos-status-preparing{background:#fef3c7;color:#92400e}.pos-status-ready{background:#dcfce7;color:#166534}.pos-live-meta{font-size:11px;color:#777;margin-top:7px;line-height:1.5}.pos-live-total{font-size:15px;font-weight:800;margin-top:5px}.pos-live-actions{display:flex;gap:6px;margin-top:9px}.pos-live-actions .btn{flex:1}.pos-activity-card{background:#fff;border:1px solid #e8e8e8;border-radius:12px;margin-bottom:14px;overflow:hidden}.pos-activity-head{padding:10px 14px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center}.pos-activity-list{max-height:180px;overflow:auto}.pos-activity-item{display:flex;gap:9px;padding:9px 13px;border-bottom:1px solid #f3f3f3;font-size:12px}.pos-activity-item strong{display:block;font-size:12px}.pos-activity-item small{color:#999}.pos-bill-modal{display:none;position:fixed;inset:0;z-index:100000}.pos-bill-modal.open{display:block}.pos-bill-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.62)}.pos-bill-dialog{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(520px,94vw);height:min(88vh,760px);background:#f5f5f5;border-radius:14px;box-shadow:0 25px 70px rgba(0,0,0,.35);overflow:hidden;display:flex;flex-direction:column}.pos-bill-dialog-head{height:48px;flex:0 0 48px;background:#202020;color:#fff;padding:0 14px;display:flex;align-items:center;justify-content:space-between}.pos-bill-close{border:0;background:transparent;color:#fff;font-size:28px;cursor:pointer;line-height:1}.pos-bill-dialog iframe{width:100%;height:100%;border:0;background:#fff}.
.pos-layout{display:grid;grid-template-columns:1fr 420px;gap:16px}
.pos-left{min-width:0}
.pos-right{position:sticky;top:16px;align-self:start}
.pos-item-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px;max-height:60vh;overflow-y:auto;padding-right:4px}
.pos-item-btn{background:var(--w);border:2px solid #e8e8e8;border-radius:10px;padding:12px 10px;text-align:left;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;gap:4px}
.pos-item-btn:hover{border-color:var(--y);background:#fffdf0;transform:translateY(-2px)}
.pos-item-name{font-size:.82rem;font-weight:600;color:var(--b);display:flex;align-items:center;gap:4px}
.pos-item-price{font-family:'Poppins',sans-serif;font-weight:700;font-size:.78rem;color:var(--br)}
.veg-dot{width:8px;height:8px;border-radius:50%;background:#27ae60;flex-shrink:0}
.pos-cart-card{display:flex;flex-direction:column;max-height:calc(100vh - 100px)}
.pos-cart-header{background:var(--b);color:var(--w);padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-radius:12px 12px 0 0}
.pos-cart-body{flex:1;overflow-y:auto;padding:16px}
.pos-cart-footer{padding:16px;border-top:1px solid #eee}
.cart-items{max-height:200px;overflow-y:auto;margin-bottom:12px}
.cart-item{display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f0f0f0}
.cart-item:last-child{border-bottom:none}
.cart-item-name{flex:1;font-size:.85rem}
.cart-item-qty{display:flex;align-items:center;gap:4px}
.cart-item-qty button{width:26px;height:26px;border-radius:6px;background:#f0f0f0;border:none;font-weight:700;cursor:pointer}
.cart-item-qty span{min-width:20px;text-align:center;font-weight:700}
.cart-item-price{font-weight:700;font-size:.85rem;color:var(--br)}
.cart-item-remove{color:#dc3545;cursor:pointer;font-size:1.1rem}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px}
.form-col{}
.form-input-sm,.form-select-sm{padding:8px 10px;font-size:.85rem}
.pos-charges{background:#f8f8f8;border-radius:8px;padding:10px;margin-bottom:12px}
.charge-row{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px}
.charge-row:last-child{margin-bottom:0}
.form-check{display:flex;align-items:center;gap:6px;font-size:.85rem}
.form-check input{width:16px;height:16px}
.input-group-sm{display:flex;align-items:center;gap:4px}
.input-group-sm input{width:60px;padding:6px 8px;font-size:.85rem;border:1px solid #ddd;border-radius:6px}
.pos-totals{background:var(--w);border:1px solid #eee;border-radius:8px;padding:12px}
.total-row{display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:4px}
.total-row small{color:#888}
.total-grand{font-size:1.1rem;margin-top:8px;padding-top:8px;border-top:1px dashed #ddd}
@media(max-width:900px){
    .pos-layout{grid-template-columns:1fr}
    .pos-right{position:static}
}
</style>

<script>
window.rpPosData = {
    ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    nonce: '<?php echo wp_create_nonce( 'rp_admin_nonce' ); ?>',
    panelUrl: '<?php echo esc_url( home_url( '/panel' ) ); ?>'
};
</script>
