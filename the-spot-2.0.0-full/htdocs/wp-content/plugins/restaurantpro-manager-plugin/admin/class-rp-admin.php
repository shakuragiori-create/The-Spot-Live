<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Admin {

    public function register_menu(): void {
        add_menu_page(
            'RestaurantPro',
            'RestaurantPro',
            'rp_view_reports',
            'rp-dashboard',
            [ new RP_Admin_Dashboard(), 'render' ],
            'dashicons-store',
            26
        );

        add_submenu_page( 'rp-dashboard', 'Dashboard', 'Dashboard', 'rp_view_reports', 'rp-dashboard' );

        add_submenu_page(
            'rp-dashboard', 'Point of Sale', '🧾 New Sale (POS)', 'rp_manage_orders', 'rp-pos',
            [ new RP_Admin_Pos(), 'render' ]
        );

        add_submenu_page(
            'rp-dashboard', 'Orders', 'Orders', 'rp_view_orders', 'rp-orders',
            [ new RP_Admin_Orders(), 'render' ]
        );

        add_submenu_page(
            'rp-dashboard', 'Accounts & Reports', 'Accounts', 'rp_view_reports', 'rp-reports',
            [ new RP_Admin_Reports(), 'render' ]
        );

        add_submenu_page(
            'rp-dashboard', 'Kitchen / KOT', 'Kitchen / KOT', 'rp_view_kot', 'rp-kitchen',
            [ new RP_Admin_Kitchen(), 'render' ]
        );

        add_submenu_page(
            'rp-dashboard', 'Tables', 'Tables', 'rp_manage_tables', 'rp-tables',
            [ new RP_Admin_Tables(), 'render' ]
        );

        add_submenu_page(
            'rp-dashboard', 'Reservations', 'Reservations', 'rp_manage_reservations', 'rp-reservations',
            [ new RP_Admin_Reservations(), 'render' ]
        );

        add_submenu_page(
            'rp-dashboard', 'Messages', 'Messages', 'rp_manage_reservations', 'rp-messages',
            [ new RP_Admin_Messages(), 'render' ]
        );

        add_submenu_page(
            'rp-dashboard', 'Settings', 'Settings', 'rp_manage_settings', 'rp-settings',
            [ new RP_Admin_Settings(), 'render' ]
        );
    }

    public function enqueue_assets( string $hook ): void {
        $rp_pages = [
            'toplevel_page_rp-dashboard',
            'restaurantpro_page_rp-pos',
            'restaurantpro_page_rp-orders',
            'restaurantpro_page_rp-reports',
            'restaurantpro_page_rp-kitchen',
            'restaurantpro_page_rp-tables',
            'restaurantpro_page_rp-reservations',
            'restaurantpro_page_rp-messages',
            'restaurantpro_page_rp-settings',
        ];

        if ( ! in_array( $hook, $rp_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'bootstrap5',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            [],
            '5.3.3'
        );
        wp_enqueue_style(
            'rp-admin',
            RP_PLUGIN_URL . 'admin/css/rp-admin.css',
            [ 'bootstrap5' ],
            RP_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'bootstrap5-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.3',
            true
        );

        wp_enqueue_script(
            'rp-admin-js',
            RP_PLUGIN_URL . 'admin/js/rp-admin.js',
            [ 'jquery', 'bootstrap5-js' ],
            RP_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'rp-admin-js', 'rpAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'rp_admin_nonce' ),
        ] );

        if ( $hook === 'toplevel_page_rp-dashboard' ) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                [],
                '4.4.1',
                true
            );
            wp_enqueue_script(
                'rp-dashboard-js',
                RP_PLUGIN_URL . 'admin/js/rp-dashboard.js',
                [ 'chartjs', 'rp-admin-js' ],
                RP_PLUGIN_VERSION,
                true
            );
        }

        if ( $hook === 'restaurantpro_page_rp-orders' ) {
            wp_enqueue_script(
                'rp-orders-js',
                RP_PLUGIN_URL . 'admin/js/rp-orders.js',
                [ 'jquery', 'rp-admin-js' ],
                RP_PLUGIN_VERSION,
                true
            );
        }

        if ( $hook === 'restaurantpro_page_rp-kitchen' ) {
            wp_enqueue_script(
                'rp-kitchen-js',
                RP_PLUGIN_URL . 'admin/js/rp-kitchen.js',
                [ 'jquery', 'rp-admin-js' ],
                RP_PLUGIN_VERSION,
                true
            );
        }

        // Bill / receipt styling + print isolation: POS (redirects to a bill)
        // and the Orders screen (where bills are opened and reprinted).
        if ( in_array( $hook, [ 'restaurantpro_page_rp-pos', 'restaurantpro_page_rp-orders' ], true ) ) {
            wp_enqueue_style(
                'rp-print',
                RP_PLUGIN_URL . 'admin/css/rp-print.css',
                [ 'rp-admin' ],
                RP_PLUGIN_VERSION
            );
        }

        if ( $hook === 'restaurantpro_page_rp-pos' ) {
            wp_enqueue_script(
                'rp-pos-js',
                RP_PLUGIN_URL . 'admin/js/rp-pos.js',
                [ 'rp-admin-js' ],
                RP_PLUGIN_VERSION,
                true
            );
            wp_localize_script( 'rp-pos-js', 'rpPos', [
                'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'rp_admin_nonce' ),
                'symbol'   => RP_Settings::get( 'currency_symbol', 'Rs.' ),
                'position' => RP_Settings::get( 'currency_position', 'before' ),
            ] );
        }

        if ( $hook === 'restaurantpro_page_rp-reports' ) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                [],
                '4.4.1',
                true
            );
            wp_enqueue_script(
                'rp-reports-js',
                RP_PLUGIN_URL . 'admin/js/rp-reports.js',
                [ 'chartjs' ],
                RP_PLUGIN_VERSION,
                true
            );
        }
    }
}
