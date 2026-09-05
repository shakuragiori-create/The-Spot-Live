<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables
$tables = [ 'rp_orders', 'rp_order_items', 'rp_tables', 'rp_kot', 'rp_reservations', 'rp_settings', 'rp_messages' ];
foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}

// Delete all plugin CPT posts
$post_types = [ 'rp_menu_item', 'rp_testimonial' ];
foreach ( $post_types as $pt ) {
    $posts = get_posts( [ 'post_type' => $pt, 'numberposts' => -1, 'post_status' => 'any' ] );
    foreach ( $posts as $post ) {
        wp_delete_post( $post->ID, true );
    }
}

// Delete taxonomy terms
$terms = get_terms( [ 'taxonomy' => 'rp_menu_category', 'hide_empty' => false ] );
if ( ! is_wp_error( $terms ) ) {
    foreach ( $terms as $term ) {
        wp_delete_term( $term->term_id, 'rp_menu_category' );
    }
}

// Remove options
delete_option( 'rp_db_version' );

// Remove roles
$roles = [ 'rp_restaurant_admin', 'rp_manager', 'rp_cashier', 'rp_waiter', 'rp_kitchen_staff' ];
foreach ( $roles as $role ) {
    remove_role( $role );
}

// Remove caps from admin
$admin = get_role( 'administrator' );
if ( $admin ) {
    $caps = [ 'rp_manage_menu', 'rp_manage_orders', 'rp_view_orders', 'rp_manage_tables', 'rp_manage_kot', 'rp_view_kot', 'rp_manage_settings', 'rp_view_reports', 'rp_manage_staff', 'rp_manage_reservations' ];
    foreach ( $caps as $cap ) {
        $admin->remove_cap( $cap );
    }
}
