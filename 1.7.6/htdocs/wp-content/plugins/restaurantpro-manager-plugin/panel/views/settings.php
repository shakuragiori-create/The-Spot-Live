<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$saved = isset( $_GET['saved'] );

$settings = [];
$fields = [ 'restaurant_name', 'restaurant_phone', 'restaurant_address', 'restaurant_email', 'whatsapp_number', 'facebook_url', 'tiktok_url', 'map_url' ];
foreach ( $fields as $f ) {
    $settings[ $f ] = RP_Settings::get( $f, '' );
}
?>

<h1 class="page-title">Settings</h1>

<?php if ( $saved ) : ?>
    <div style="background:#e8f5e9;color:#2e7d32;padding:12px 16px;border-radius:10px;font-size:.85rem;margin-bottom:16px;font-weight:600">
        ✓ Settings saved successfully.
    </div>
<?php endif; ?>

<form method="post">
    <?php wp_nonce_field( 'rp_settings_save' ); ?>
    <input type="hidden" name="rp_settings_save" value="1">

    <div class="p-card">
        <h3 class="p-card-title mb-12">Restaurant Info</h3>
        <div class="form-group">
            <label class="form-label">Restaurant Name</label>
            <input type="text" name="restaurant_name" class="form-input" value="<?php echo esc_attr( $settings['restaurant_name'] ); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="restaurant_phone" class="form-input" value="<?php echo esc_attr( $settings['restaurant_phone'] ); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Address</label>
            <input type="text" name="restaurant_address" class="form-input" value="<?php echo esc_attr( $settings['restaurant_address'] ); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="restaurant_email" class="form-input" value="<?php echo esc_attr( $settings['restaurant_email'] ); ?>">
        </div>
    </div>

    <div class="p-card">
        <h3 class="p-card-title mb-12">Social & Contact</h3>
        <div class="form-group">
            <label class="form-label">WhatsApp Number (without +)</label>
            <input type="text" name="whatsapp_number" class="form-input" value="<?php echo esc_attr( $settings['whatsapp_number'] ); ?>" placeholder="9779845423522">
        </div>
        <div class="form-group">
            <label class="form-label">Facebook URL</label>
            <input type="url" name="facebook_url" class="form-input" value="<?php echo esc_attr( $settings['facebook_url'] ); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">TikTok URL</label>
            <input type="url" name="tiktok_url" class="form-input" value="<?php echo esc_attr( $settings['tiktok_url'] ); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Google Maps URL</label>
            <input type="url" name="map_url" class="form-input" value="<?php echo esc_attr( $settings['map_url'] ); ?>">
        </div>
    </div>

    <?php
    // Role Access Control - only visible to admin
    $panel = RP_Panel::instance();
    $current_user = wp_get_current_user();
    $rp_role = get_user_meta( $current_user->ID, 'rp_role', true );
    if ( $rp_role === 'admin' ) :
        $roles_config = [
            'receptionist' => 'Reception / Manager',
            'kitchen'      => 'Kitchen',
            'waiter'       => 'Waiter',
        ];
        $all_pages = [
            'pos'          => 'POS',
            'dashboard'    => 'Dashboard',
            'orders'       => 'Orders',
            'messages'     => 'Messages',
            'kitchen'      => 'Kitchen',
            'tables'       => 'Tables',
            'reservations' => 'Reservations',
            'accounts'     => 'Accounts',
            'reports'      => 'Reports',
            'menu'         => 'Menu',
            'gallery'      => 'Gallery',
            'users'        => 'Staff',
            'settings'     => 'Settings',
            'inventory'    => 'Inventory',
            'expenses'     => 'Expenses',
            'shifts'       => 'Cash Shift',
            'activity'     => 'Activity Log',
            'backup'       => 'Backup',
        ];
        $defaults = [
            'receptionist' => [ 'dashboard', 'pos', 'orders', 'order-new', 'order-view', 'tables', 'reservations', 'messages' ],
            'kitchen'      => [ 'kitchen', 'messages' ],
            'waiter'       => [ 'pos', 'orders', 'order-new', 'order-view', 'tables', 'messages' ],
        ];
    ?>
    <div class="p-card">
        <h3 class="p-card-title mb-12">Role Access Control</h3>
        <p style="font-size:.82rem;color:#888;margin-bottom:16px">Configure which panel pages each role can access. Admin always has full access.</p>
        <?php foreach ( $roles_config as $role_key => $role_label ) :
            $saved_json = RP_Settings::get( 'role_pages_' . $role_key, '' );
            $saved_pages = ( $saved_json !== '' ) ? json_decode( $saved_json, true ) : null;
            $allowed = is_array( $saved_pages ) ? $saved_pages : $defaults[ $role_key ];
        ?>
            <div class="form-group">
                <label class="form-label" style="font-weight:600;margin-bottom:8px"><?php echo esc_html( $role_label ); ?></label>
                <div style="display:flex;flex-wrap:wrap;gap:8px 16px">
                    <?php foreach ( $all_pages as $page_slug => $page_label ) : ?>
                        <label style="display:inline-flex;align-items:center;gap:4px;font-size:.84rem;cursor:pointer">
                            <input type="checkbox" name="role_pages[<?php echo esc_attr( $role_key ); ?>][]" value="<?php echo esc_attr( $page_slug ); ?>"<?php echo in_array( $page_slug, $allowed, true ) ? ' checked' : ''; ?>>
                            <?php echo esc_html( $page_label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-block" style="padding:14px;font-size:.95rem">Save Settings</button>
</form>
