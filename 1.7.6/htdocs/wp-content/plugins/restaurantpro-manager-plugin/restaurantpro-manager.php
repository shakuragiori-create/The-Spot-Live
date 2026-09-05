<?php
/**
 * Plugin Name: RestaurantPro Manager
 * Plugin URI:
 * Description: Complete restaurant management system — orders, KOT, tables, reservations, digital menu, and staff roles.
 * Version: 1.5.0
 * Author: RestaurantPro
 * License: GPL v2 or later
 * Text Domain: restaurantpro
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RP_PLUGIN_VERSION', '1.7.6' );
define( 'RP_PLUGIN_FILE', __FILE__ );
define( 'RP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RP_DB_VERSION', '1.5.1' );

require_once RP_PLUGIN_DIR . 'includes/class-rp-database.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-roles.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-activator.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-deactivator.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-post-types.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-settings.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-helpers.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-billing.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-accounts.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-notifications.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-menu-seeder.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-activity.php';
require_once RP_PLUGIN_DIR . 'includes/class-rp-backup.php';
require_once RP_PLUGIN_DIR . 'panel/class-rp-panel.php';

register_activation_hook( __FILE__, [ 'RP_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'RP_Deactivator', 'deactivate' ] );

final class RestaurantPro_Manager {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    private function load_dependencies(): void {
        if ( is_admin() ) {
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-dashboard.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-orders.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-pos.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-kitchen.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-tables.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-reservations.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-messages.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-reports.php';
            require_once RP_PLUGIN_DIR . 'admin/class-rp-admin-settings.php';
        }

        require_once RP_PLUGIN_DIR . 'public/class-rp-public.php';
        require_once RP_PLUGIN_DIR . 'public/class-rp-ajax-public.php';
        require_once RP_PLUGIN_DIR . 'ajax/class-rp-ajax-orders.php';
        require_once RP_PLUGIN_DIR . 'ajax/class-rp-ajax-pos.php';
        require_once RP_PLUGIN_DIR . 'ajax/class-rp-ajax-kitchen.php';
        require_once RP_PLUGIN_DIR . 'ajax/class-rp-ajax-dashboard.php';
        require_once RP_PLUGIN_DIR . 'ajax/class-rp-ajax-reports.php';
        require_once RP_PLUGIN_DIR . 'ajax/class-rp-ajax-tables.php';
        require_once RP_PLUGIN_DIR . 'ajax/class-rp-ajax-notifications.php';
        require_once RP_PLUGIN_DIR . 'ajax/class-rp-ajax-messages.php';
    }

    private function register_hooks(): void {
        add_action( 'init', [ 'RP_Post_Types', 'register' ] );
        add_action( 'init', [ $this, 'check_db_upgrade' ] );
        // Self-heal role capabilities on every load. If another plugin, a core
        // update, or a role reset ever strips the rp_* capabilities from the
        // administrator/staff roles, POS/Orders would start failing with a
        // silent "Permission denied" that looks like "order creation is
        // broken" with no obvious cause. add_cap() is a no-op when the
        // capability is already present, so this is cheap on every request.
        add_action( 'init', [ 'RP_Roles', 'create' ], 5 );
        // Panel sessions are often left open all day on a POS tablet. The
        // default WordPress nonce window is only ~24h split into two 12h
        // ticks, so a nonce created first thing in the morning can go stale
        // by evening and "Charge" starts failing with "Security check
        // failed" — easy to mistake for order creation being broken.
        add_filter( 'nonce_life', static function () {
            return 2 * DAY_IN_SECONDS;
        } );

        RP_Panel::instance();
RP_Backup::init();

        if ( is_admin() ) {
            $admin = new RP_Admin();
            add_action( 'admin_menu', [ $admin, 'register_menu' ] );
            add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_assets' ] );
        }

        $public = new RP_Public();
        add_action( 'wp_enqueue_scripts', [ $public, 'enqueue_assets' ] );

        $ajax_public = new RP_Ajax_Public();
        $ajax_public->register();

        $ajax_orders = new RP_Ajax_Orders();
        $ajax_orders->register();

        $ajax_pos = new RP_Ajax_Pos();
        $ajax_pos->register();

        $ajax_kitchen = new RP_Ajax_Kitchen();
        $ajax_kitchen->register();

        $ajax_dashboard = new RP_Ajax_Dashboard();
        $ajax_dashboard->register();

        $ajax_reports = new RP_Ajax_Reports();
        $ajax_reports->register();

        $ajax_tables = new RP_Ajax_Tables();
        $ajax_tables->register();

        $ajax_notifications = new RP_Ajax_Notifications();
        $ajax_messages = new RP_Ajax_Messages();
        $ajax_messages->register();
        $ajax_notifications->register();

        RP_Menu_Seeder::register();
    }

    public function check_db_upgrade(): void {
        if ( get_option( 'rp_db_version' ) !== RP_DB_VERSION ) {
            RP_Database::create_tables();
            RP_Activator::seed_defaults();
            update_option( 'rp_db_version', RP_DB_VERSION );
        }
    }
}

add_action( 'plugins_loaded', [ 'RestaurantPro_Manager', 'instance' ] );

function rp_manager_get_setting( string $key, mixed $default = '' ): mixed {
    return RP_Settings::get( $key, $default );
}
