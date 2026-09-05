<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Admin_Pos {

    public function render(): void {
        global $wpdb;

        $tables = $wpdb->get_results( "SELECT id, table_name, status FROM {$wpdb->prefix}rp_tables ORDER BY table_name" );

        $categories = get_terms( [
            'taxonomy'   => 'rp_menu_category',
            'hide_empty' => true,
        ] );
        if ( is_wp_error( $categories ) ) {
            $categories = [];
        }

        // Sort by the category's display order, then name.
        usort( $categories, static function ( $a, $b ) {
            $oa = (int) get_term_meta( $a->term_id, '_rp_order', true );
            $ob = (int) get_term_meta( $b->term_id, '_rp_order', true );
            return $oa === $ob ? strcmp( $a->name, $b->name ) : $oa <=> $ob;
        } );

        $items = get_posts( [
            'post_type'      => 'rp_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
            'meta_query'     => [
                'relation' => 'OR',
                [ 'key' => '_rp_is_available', 'value' => '1' ],
                [ 'key' => '_rp_is_available', 'compare' => 'NOT EXISTS' ],
            ],
        ] );

        $billing = RP_Billing::defaults();

        include RP_PLUGIN_DIR . 'admin/views/pos.php';
    }
}
