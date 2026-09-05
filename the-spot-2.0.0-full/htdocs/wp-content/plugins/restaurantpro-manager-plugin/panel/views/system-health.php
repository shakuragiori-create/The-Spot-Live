<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! in_array( $role, [ 'admin', 'superadmin' ], true ) ) { echo '<p class="text-danger">Access denied.</p>'; return; }

global $wpdb;

$wp_tz = wp_timezone_string();
$php_tz = date_default_timezone_get();
$wp_time = current_time( 'Y-m-d H:i:s' );
$php_time = date( 'Y-m-d H:i:s' );
$utc_time = gmdate( 'Y-m-d H:i:s' );
$db_time = $wpdb->get_var( "SELECT NOW()" );
$kt_time = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'Asia/Kathmandu' ) );

$tz_match = ( $wp_tz === 'Asia/Kathmandu' );
$php_tz_match = ( $php_tz === 'Asia/Kathmandu' || $php_tz === 'UTC' );

$wp_version = get_bloginfo( 'version' );
$php_version = phpversion();
$plugin_version = RP_PLUGIN_VERSION;
$db_version = get_option( 'rp_db_version', 'unknown' );

$db_ok = ! empty( $db_time );

$tables = [ 'rp_orders', 'rp_order_items', 'rp_tables', 'rp_kot', 'rp_reservations', 'rp_settings', 'rp_messages', 'rp_internal_messages', 'rp_activity_log', 'rp_staff_permissions', 'rp_ledger_accounts', 'rp_ledger_entries', 'rp_salary_records', 'rp_staff_leave', 'rp_cash_shifts', 'rp_inventory_items', 'rp_inventory_movements', 'rp_expenses' ];
$table_status = [];
foreach ( $tables as $t ) {
    $full = $wpdb->prefix . $t;
    $table_status[ $t ] = ( $wpdb->get_var( "SHOW TABLES LIKE '{$full}'" ) === $full );
}

$nonce_ok = wp_verify_nonce( wp_create_nonce( 'rp_health_check' ), 'rp_health_check' );

$uploads = wp_upload_dir();
$upload_writable = wp_is_writable( $uploads['basedir'] );
$plugin_readable = is_readable( RP_PLUGIN_DIR );

$is_ssl = is_ssl();
$home_url = home_url();
$site_url = site_url();
?>

<h1 class="page-title" data-i18n="System Health & Diagnostics">System Health & Diagnostics</h1>

<div class="stat-grid">
    <div class="stat-card <?php echo $tz_match ? 'green' : 'red'; ?>">
        <div class="stat-icon"><?php echo rp_panel_icon('clock'); ?></div>
        <div class="stat-value" style="font-size:1rem"><?php echo esc_html( $wp_tz ); ?></div>
        <div class="stat-label" data-i18n="WordPress Timezone">WordPress Timezone</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><?php echo rp_panel_icon('activity'); ?></div>
        <div class="stat-value" style="font-size:.95rem"><?php echo esc_html( $plugin_version ); ?></div>
        <div class="stat-label" data-i18n="Plugin Version">Plugin Version</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon"><?php echo rp_panel_icon('settings'); ?></div>
        <div class="stat-value" style="font-size:.95rem"><?php echo esc_html( $php_version ); ?></div>
        <div class="stat-label" data-i18n="PHP Version">PHP Version</div>
    </div>
    <div class="stat-card <?php echo $db_ok ? 'green' : 'red'; ?>">
        <div class="stat-icon"><?php echo rp_panel_icon('grid'); ?></div>
        <div class="stat-value" style="font-size:.95rem"><?php echo $db_ok ? 'OK' : 'FAIL'; ?></div>
        <div class="stat-label" data-i18n="Database Connection">Database Connection</div>
    </div>
</div>

<div class="p-card">
    <h3 class="p-card-title mb-12">Timezone & Time</h3>
    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600" data-i18n="WordPress Timezone">WordPress Timezone</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $wp_tz ); ?> <?php echo $tz_match ? '<span style="color:#16a34a">&#10003;</span>' : '<span style="color:#dc2626">&#10007; Should be Asia/Kathmandu</span>'; ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600" data-i18n="PHP Timezone">PHP Timezone</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $php_tz ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600" data-i18n="Current WordPress Time">Current WordPress Time</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $wp_time ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">Asia/Kathmandu Time</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $kt_time ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600" data-i18n="Current PHP Time">Current PHP Time</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $php_time ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600" data-i18n="UTC Time">UTC Time</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $utc_time ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">Database Time</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $db_time ); ?></td></tr>
    </table>
</div>

<div class="p-card">
    <h3 class="p-card-title mb-12">Environment</h3>
    <table style="width:100%;border-collapse:collapse;font-size:.88rem">
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600" data-i18n="WordPress Version">WordPress Version</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $wp_version ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600" data-i18n="PHP Version">PHP Version</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $php_version ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600" data-i18n="Plugin Version">Plugin Version</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $plugin_version ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">DB Schema Version</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $db_version ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">HTTPS</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo $is_ssl ? '<span style="color:#16a34a">Yes</span>' : '<span style="color:#dc2626">No</span>'; ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">Home URL</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $home_url ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">Site URL</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo esc_html( $site_url ); ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">Nonce System</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo $nonce_ok ? '<span style="color:#16a34a">Working</span>' : '<span style="color:#dc2626">Failed</span>'; ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">Uploads Writable</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo $upload_writable ? '<span style="color:#16a34a">Yes</span>' : '<span style="color:#dc2626">No</span>'; ?></td></tr>
        <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">Plugin Readable</td><td style="padding:8px;border-bottom:1px solid #eee"><?php echo $plugin_readable ? '<span style="color:#16a34a">Yes</span>' : '<span style="color:#dc2626">No</span>'; ?></td></tr>
    </table>
</div>

<div class="p-card">
    <h3 class="p-card-title mb-12">Database Tables</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;font-size:.85rem">
        <?php foreach ( $table_status as $name => $exists ) : ?>
            <div style="padding:8px;border-radius:8px;background:<?php echo $exists ? '#dcfce7' : '#fee2e2'; ?>">
                <?php echo $exists ? '<span style="color:#16a34a">&#10003;</span>' : '<span style="color:#dc2626">&#10007;</span>'; ?>
                <?php echo esc_html( $name ); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
