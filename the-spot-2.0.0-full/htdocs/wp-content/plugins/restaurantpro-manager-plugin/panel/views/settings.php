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

    <button type="submit" class="btn btn-primary btn-block" style="padding:14px;font-size:.95rem">Save Settings</button>
</form>
