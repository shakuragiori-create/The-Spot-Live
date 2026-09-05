<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Roles {

    private static array $capabilities = [
        'rp_manage_menu',
        'rp_manage_orders',
        'rp_view_orders',
        'rp_manage_tables',
        'rp_manage_kot',
        'rp_view_kot',
        'rp_manage_settings',
        'rp_view_reports',
        'rp_manage_staff',
        'rp_manage_reservations',
        'rp_manage_permissions',
        'rp_view_system_health',
    ];

    private static array $roles = [
        'rp_super_admin' => [
            'label' => 'Super Admin',
            'caps'  => [
                'read'                   => true,
                'upload_files'           => true,
                'edit_posts'             => true,
                'rp_manage_menu'         => true,
                'rp_manage_orders'       => true,
                'rp_view_orders'         => true,
                'rp_manage_tables'       => true,
                'rp_manage_kot'          => true,
                'rp_view_kot'            => true,
                'rp_manage_settings'     => true,
                'rp_view_reports'        => true,
                'rp_manage_staff'        => true,
                'rp_manage_reservations' => true,
                'rp_manage_permissions'  => true,
                'rp_view_system_health'  => true,
            ],
        ],
        'rp_restaurant_admin' => [
            'label' => 'Restaurant Admin',
            'caps'  => [
                'read'                   => true,
                'upload_files'           => true,
                'edit_posts'             => true,
                'rp_manage_menu'         => true,
                'rp_manage_orders'       => true,
                'rp_view_orders'         => true,
                'rp_manage_tables'       => true,
                'rp_manage_kot'          => true,
                'rp_view_kot'            => true,
                'rp_manage_settings'     => true,
                'rp_view_reports'        => true,
                'rp_manage_staff'        => true,
                'rp_manage_reservations' => true,
            ],
        ],
        'rp_manager' => [
            'label' => 'Restaurant Manager',
            'caps'  => [
                'read'                   => true,
                'upload_files'           => true,
                'edit_posts'             => true,
                'rp_manage_menu'         => true,
                'rp_manage_orders'       => true,
                'rp_view_orders'         => true,
                'rp_manage_tables'       => true,
                'rp_manage_kot'          => true,
                'rp_view_kot'            => true,
                'rp_view_reports'        => true,
                'rp_manage_reservations' => true,
            ],
        ],
        'rp_cashier' => [
            'label' => 'Cashier',
            'caps'  => [
                'read'             => true,
                'rp_manage_orders' => true,
                'rp_view_orders'   => true,
                'rp_view_reports'  => true,
            ],
        ],
        'rp_waiter' => [
            'label' => 'Waiter',
            'caps'  => [
                'read'             => true,
                'rp_manage_orders' => true,
                'rp_view_orders'   => true,
                'rp_view_kot'      => true,
            ],
        ],
        'rp_kitchen_staff' => [
            'label' => 'Kitchen Staff',
            'caps'  => [
                'read'          => true,
                'rp_view_kot'   => true,
                'rp_manage_kot' => true,
            ],
        ],
    ];

    public static function create(): void {
        foreach ( self::$roles as $slug => $role ) {
            if ( ! get_role( $slug ) ) {
                add_role( $slug, $role['label'], $role['caps'] );
            }
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( self::$capabilities as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    public static function remove(): void {
        foreach ( array_keys( self::$roles ) as $slug ) {
            remove_role( $slug );
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( self::$capabilities as $cap ) {
                $admin->remove_cap( $cap );
            }
        }
    }
}
