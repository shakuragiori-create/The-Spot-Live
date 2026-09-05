<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$tables = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rp_tables ORDER BY table_name ASC" );
$menu_items = get_posts([
    'post_type' => 'rp_menu_item', 'posts_per_page' => -1, 'post_status' => 'publish',
    'meta_query' => [[ 'key' => '_rp_is_available', 'value' => '1' ]], 'orderby' => 'title', 'order' => 'ASC',
]);
$categories = get_terms([ 'taxonomy' => 'rp_menu_category', 'hide_empty' => true ]);
?>

<div class="new-order-head">
  <div><span class="eyebrow">QUICK ORDER</span><h1 class="page-title">New Order</h1><p class="page-subtitle">Tap items to add them — designed for fast phone ordering.</p></div>
  <a href="<?php echo esc_url( home_url( '/panel/orders' ) ); ?>" class="btn btn-sm btn-outline">← Orders</a>
</div>

<form id="newOrderForm" class="new-order-form">
<input type="hidden" name="action" value="rp_create_order"><input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'rp_orders_nonce' ); ?>">

<section class="p-card order-customer-card">
  <div class="order-section-head"><div><h3 class="p-card-title">Order details</h3><span>Customer, table and special instructions</span></div></div>
  <div class="form-row order-details-grid">
    <div class="form-group"><label class="form-label">Customer</label><input type="text" name="customer_name" class="form-input" placeholder="Customer name"></div>
    <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="customer_phone" class="form-input" placeholder="Phone number"></div>
    <div class="form-group"><label class="form-label">Table</label><select name="table_number" class="form-select"><option value="0">No Table (Takeaway)</option><?php foreach ( $tables as $t ) : ?><option value="<?php echo esc_attr( $t->id ); ?>"><?php echo esc_html( $t->table_name ); ?><?php echo $t->status === 'occupied' ? ' — Occupied' : ''; ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label class="form-label">Notes</label><input type="text" name="notes" class="form-input" placeholder="Special requests..."></div>
  </div>
</section>

<div class="new-order-workspace">
  <section class="p-card order-menu-card">
    <div class="order-section-head menu-head"><div><h3 class="p-card-title">Choose items</h3><span><?php echo count($menu_items); ?> available items</span></div><div class="menu-search-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="text" id="menuSearch" class="form-input" placeholder="Search menu..."></div></div>
    <div class="filter-tabs order-category-tabs" id="catFilter"><button type="button" class="filter-tab active" data-cat="all">All</button><?php foreach ( $categories as $cat ) : ?><button type="button" class="filter-tab" data-cat="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></button><?php endforeach; ?></div>
    <div id="menuItemsList" class="quick-menu-grid">
    <?php foreach ( $menu_items as $item ) : $price=get_post_meta($item->ID,'_rp_price',true); $cats=wp_get_post_terms($item->ID,'rp_menu_category',['fields'=>'ids']); $cat_ids=implode(',',$cats); ?>
      <div class="menu-pick-item" data-id="<?php echo esc_attr($item->ID); ?>" data-price="<?php echo esc_attr($price); ?>" data-name="<?php echo esc_attr($item->post_title); ?>" data-cats="<?php echo esc_attr($cat_ids); ?>">
        <div class="menu-pick-info"><span class="menu-pick-name"><?php echo esc_html($item->post_title); ?></span><span class="menu-pick-price">Rs.<?php echo esc_html($price); ?></span></div>
        <button type="button" class="add-item-btn" aria-label="Add <?php echo esc_attr($item->post_title); ?>">+</button>
      </div>
    <?php endforeach; ?>
    </div>
  </section>

  <aside class="p-card order-summary-card" id="orderItems" style="display:none">
    <div class="order-summary-head"><div><h3 class="p-card-title">Current order</h3><span id="orderItemCountLabel">0 items</span></div><strong id="orderTotal">Rs.0</strong></div>
    <div id="selectedItems" class="selected-items-list"></div>
    <div class="order-submit-area"><button type="submit" class="btn btn-primary btn-block" id="submitOrder" disabled>Create Order</button></div>
  </aside>
</div>

<div class="mobile-order-bar" id="mobileOrderBar"><div><span id="mobileOrderCount">0 items</span><strong id="mobileOrderTotal">Rs.0</strong></div><button type="button" id="mobileSubmitOrder" disabled>Create Order</button></div>
</form>

<style>
.new-order-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px}.eyebrow{display:block;font-size:10px;font-weight:800;letter-spacing:1.6px;color:var(--br);margin-bottom:5px}.page-subtitle{margin:5px 0 0;color:#777;font-size:12px}.order-customer-card{margin-bottom:16px}.order-section-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:14px}.order-section-head span,.order-summary-head span{font-size:11px;color:#888}.order-details-grid{display:grid;grid-template-columns:1fr 1fr 1fr 1.4fr;gap:12px}.new-order-workspace{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:16px;align-items:start}.order-menu-card{min-width:0}.menu-head{align-items:flex-end}.menu-search-wrap{position:relative;width:min(300px,45%)}.menu-search-wrap svg{position:absolute;left:11px;top:50%;width:16px;height:16px;transform:translateY(-50%);stroke:#888;fill:none;stroke-width:1.8;pointer-events:none}.menu-search-wrap input{padding-left:34px}.order-category-tabs{display:flex;gap:7px;overflow-x:auto;white-space:nowrap;padding-bottom:7px;margin-bottom:12px;scrollbar-width:thin}.quick-menu-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.menu-pick-item{display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:70px;padding:11px;border:1px solid #e8e8e8;border-radius:12px;background:#fff;transition:border-color .18s,box-shadow .18s,transform .18s}.menu-pick-item:hover{border-color:#d2b000;box-shadow:0 7px 18px rgba(0,0,0,.06);transform:translateY(-1px)}.menu-pick-info{min-width:0}.menu-pick-name{display:block;font-size:12px;font-weight:700;line-height:1.35;white-space:normal}.menu-pick-price{display:block;margin-top:5px;font-family:'Poppins',sans-serif;font-size:12px;font-weight:800;color:var(--br)}.add-item-btn{width:36px;height:36px;flex:0 0 36px;border:1px solid #ddd;border-radius:10px;background:#f7f7f7;color:#222;font-size:21px;font-weight:500;line-height:1;cursor:pointer}.add-item-btn:active{transform:scale(.94);background:var(--y)}.order-summary-card{position:sticky;top:14px;padding:0;overflow:hidden}.order-summary-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:16px;border-bottom:1px solid #eee}.order-summary-head strong{font-family:'Poppins',sans-serif;color:var(--br);font-size:18px}.selected-items-list{padding:7px 16px;max-height:calc(100vh - 310px);overflow:auto}.selected-item{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:8px;padding:10px 0;border-bottom:1px solid #f0f0f0}.selected-item .qty-controls{display:flex;align-items:center;gap:4px}.selected-item .qty-btn{width:27px;height:27px;border:0;border-radius:7px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-weight:700;cursor:pointer}.selected-item .qty-val{font-weight:800;font-size:11px;min-width:18px;text-align:center}.selected-item .item-name{font-size:11px;font-weight:600;line-height:1.35}.selected-item .item-total{font-family:'Poppins',sans-serif;font-weight:800;font-size:11px;color:var(--br)}.order-submit-area{padding:14px 16px;border-top:1px solid #eee}.mobile-order-bar{display:none}.mobile-order-bar button{border:0;border-radius:10px;background:var(--y);color:#111;font-weight:800;padding:12px 16px}.mobile-order-bar button:disabled{opacity:.45}.mobile-order-bar span{display:block;font-size:10px;color:#777}.mobile-order-bar strong{font-family:'Poppins',sans-serif;font-size:17px}
@media(max-width:1050px){.order-details-grid{grid-template-columns:1fr 1fr}.new-order-workspace{grid-template-columns:minmax(0,1fr) 310px}.quick-menu-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.new-order-head{align-items:center}.new-order-head .page-subtitle{display:none}.order-customer-card{padding:13px}.order-details-grid{grid-template-columns:1fr 1fr;gap:9px}.order-details-grid .form-group:last-child{grid-column:1/-1}.new-order-workspace{display:block}.order-menu-card{padding:13px}.menu-head{display:block}.menu-search-wrap{width:100%;margin-top:10px}.order-category-tabs{margin-left:-2px;margin-right:-2px}.quick-menu-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.menu-pick-item{min-height:76px;padding:10px}.add-item-btn{width:38px;height:38px;flex-basis:38px}.order-summary-card{position:relative;top:auto;margin-top:12px;margin-bottom:82px}.selected-items-list{max-height:320px}.order-submit-area{display:none}.mobile-order-bar{position:fixed;left:10px;right:10px;bottom:10px;z-index:1000;display:flex;align-items:center;justify-content:space-between;gap:12px;background:#111;color:#fff;border:1px solid #333;border-radius:14px;padding:9px 10px 9px 14px;box-shadow:0 12px 30px rgba(0,0,0,.25)}.mobile-order-bar button{min-width:120px}.new-order-form{padding-bottom:8px}}
@media(max-width:430px){.order-details-grid{grid-template-columns:1fr}.order-details-grid .form-group:last-child{grid-column:auto}.quick-menu-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.menu-pick-name{font-size:11px}.menu-pick-price{font-size:11px}.add-item-btn{width:34px;height:34px;flex-basis:34px}}
</style>
<script>window.rpNewOrderData={ajaxUrl:'<?php echo esc_url(admin_url('admin-ajax.php')); ?>',nonce:'<?php echo wp_create_nonce('rp_orders_nonce'); ?>',redirectUrl:'<?php echo esc_url(home_url('/panel/orders')); ?>'};</script>
