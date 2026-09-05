<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$categories = get_terms([ 'taxonomy' => 'rp_menu_category', 'hide_empty' => false ]);
$menu_items = get_posts([
    'post_type' => 'rp_menu_item',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
]);
?>

<h1 class="page-title">Menu Management</h1>

<!-- Add Item -->
<div class="p-card">
    <h3 class="p-card-title mb-12">Add Menu Item</h3>
    <form method="post">
        <?php wp_nonce_field( 'rp_menu_action' ); ?>
        <input type="hidden" name="rp_menu_action" value="add">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" name="item_name" class="form-input" required placeholder="Chicken Momo">
            </div>
            <div class="form-group">
                <label class="form-label">Price (Rs.)</label>
                <input type="number" name="price" class="form-input" required min="1" placeholder="150">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end">
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="0">— None —</option>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:6px">
                    <input type="checkbox" name="is_veg" value="1" style="width:16px;height:16px;accent-color:#27ae60"> Veg
                </label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Add Item</button>
    </form>
</div>

<!-- Categories Management -->
<div class="p-card">
    <h3 class="p-card-title mb-12">Categories (<?php echo count( $categories ); ?>)</h3>

    <!-- Add Category -->
    <form method="post" style="display:flex;gap:10px;margin-bottom:16px">
        <?php wp_nonce_field( 'rp_menu_action' ); ?>
        <input type="hidden" name="rp_menu_action" value="add_category">
        <input type="text" name="cat_name" class="form-input" placeholder="New category name" required style="flex:1">
        <button type="submit" class="btn btn-dark">Add</button>
    </form>

    <!-- Category List -->
    <?php foreach ( $categories as $cat ) : ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f5f5f5">
            <div style="flex:1;min-width:0">
                <span class="fw-700" style="font-size:.85rem"><?php echo esc_html( $cat->name ); ?></span>
                <span class="text-sm text-muted" style="margin-left:6px">(<?php echo esc_html( $cat->count ); ?> items)</span>
            </div>
            <!-- Edit -->
            <form method="post" style="display:flex;gap:4px;align-items:center" class="cat-edit-form">
                <?php wp_nonce_field( 'rp_menu_action' ); ?>
                <input type="hidden" name="rp_menu_action" value="edit_category">
                <input type="hidden" name="cat_id" value="<?php echo esc_attr( $cat->term_id ); ?>">
                <input type="text" name="cat_name" class="form-input" value="<?php echo esc_attr( $cat->name ); ?>" style="padding:6px 10px;font-size:.78rem;width:120px;border-radius:6px">
                <button type="submit" class="btn btn-sm btn-outline" title="Save">✓</button>
            </form>
            <!-- Delete -->
            <form method="post" style="display:inline">
                <?php wp_nonce_field( 'rp_menu_action' ); ?>
                <input type="hidden" name="rp_menu_action" value="delete_category">
                <input type="hidden" name="cat_id" value="<?php echo esc_attr( $cat->term_id ); ?>">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete category \'<?php echo esc_js( $cat->name ); ?>\'? Items won\'t be deleted.')">×</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if ( empty( $categories ) ) : ?>
        <div class="text-sm text-muted" style="padding:12px 0">No categories yet. Add one above.</div>
    <?php endif; ?>
</div>

<!-- Item List -->
<div class="p-card">
    <h3 class="p-card-title mb-12">All Items (<?php echo count( $menu_items ); ?>)</h3>
    <?php foreach ( $menu_items as $item ) :
        $price = get_post_meta( $item->ID, '_rp_price', true );
        $available = get_post_meta( $item->ID, '_rp_is_available', true );
        $is_veg = get_post_meta( $item->ID, '_rp_is_veg', true );
        $cats = wp_get_post_terms( $item->ID, 'rp_menu_category', ['fields' => 'names'] );
    ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f5f5f5">
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:.85rem;display:flex;align-items:center;gap:5px">
                    <?php echo esc_html( $item->post_title ); ?>
                    <?php if ( $is_veg === '1' ) : ?><span style="width:8px;height:8px;background:#27ae60;border-radius:50%;display:inline-block"></span><?php endif; ?>
                    <?php if ( $available !== '1' ) : ?><span class="badge" style="background:#fee;color:#c00;font-size:.6rem">OFF</span><?php endif; ?>
                </div>
                <div class="text-sm text-muted"><?php echo esc_html( implode( ', ', $cats ) ?: 'Uncategorized' ); ?></div>
            </div>
            <span style="font-family:'Poppins',sans-serif;font-weight:700;font-size:.82rem;color:var(--br)">Rs.<?php echo esc_html( $price ); ?></span>
            <form method="post" style="display:inline">
                <?php wp_nonce_field( 'rp_menu_action' ); ?>
                <input type="hidden" name="rp_menu_action" value="toggle">
                <input type="hidden" name="item_id" value="<?php echo esc_attr( $item->ID ); ?>">
                <button class="btn btn-sm <?php echo $available === '1' ? 'btn-outline' : 'btn-success'; ?>"><?php echo $available === '1' ? 'Disable' : 'Enable'; ?></button>
            </form>
            <form method="post" style="display:inline">
                <?php wp_nonce_field( 'rp_menu_action' ); ?>
                <input type="hidden" name="rp_menu_action" value="delete">
                <input type="hidden" name="item_id" value="<?php echo esc_attr( $item->ID ); ?>">
                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">×</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if ( ! $menu_items ) : ?>
        <div class="empty-state"><div class="empty-icon"><?php echo rp_panel_icon('book-open'); ?></div><p>No menu items yet</p></div>
    <?php endif; ?>
</div>
