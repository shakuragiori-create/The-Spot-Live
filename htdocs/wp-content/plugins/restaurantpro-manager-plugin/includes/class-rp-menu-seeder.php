<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * One-time importer that seeds The Spot's menu (categories + items) into the
 * rp_menu_item CPT and rp_menu_category taxonomy. Idempotent (skips items whose
 * title already exists) and chunked over AJAX so it never trips the host's
 * CPU / execution-time limits.
 */
class RP_Menu_Seeder {

    const CHUNK = 25;

    public static function register(): void {
        add_action( 'wp_ajax_rp_seed_menu', [ __CLASS__, 'ajax_seed' ] );
    }

    /**
     * Full menu catalogue. Categories carry their tab icon / section heading /
     * display order; items carry price, veg + popular flags, optional section
     * sub-heading, and (for the platter) a description.
     */
    public static function catalog(): array {
        $categories = [
            'burgers' => [ 'name' => 'Burgers',            'icon' => '🍔', 'heading' => 'Burgers',                       'order' => 10 ],
            'chicken' => [ 'name' => 'Chicken',            'icon' => '🍗', 'heading' => 'Chicken',                       'order' => 20 ],
            'vegmomo' => [ 'name' => 'Veg & Momo',         'icon' => '🥦', 'heading' => 'Veg',                           'order' => 30 ],
            'cmomo'   => [ 'name' => 'Chicken Momo',       'icon' => '🥟', 'heading' => 'Chicken Momo',                  'order' => 40 ],
            'chow'    => [ 'name' => 'Chowmein',           'icon' => '🍜', 'heading' => 'Chowmein',                      'order' => 50 ],
            'pizza'   => [ 'name' => 'Mini Pizza',         'icon' => '🍕', 'heading' => 'Mini Pizza',                    'order' => 60 ],
            'rice'    => [ 'name' => 'Fried Rice',         'icon' => '🍚', 'heading' => 'Fried Rice',                    'order' => 70 ],
            'rolls'   => [ 'name' => 'Rolls',              'icon' => '🌯', 'heading' => 'Rolls',                         'order' => 80 ],
            'paratha' => [ 'name' => 'Paratha',            'icon' => '🫓', 'heading' => 'Paratha',                       'order' => 90 ],
            'snacks'  => [ 'name' => 'Snacks',             'icon' => '🌽', 'heading' => 'Corns, Pakauda & More',         'order' => 100 ],
            'noodles' => [ 'name' => 'Current / Noodles',  'icon' => '🍝', 'heading' => 'Current Time (Instant Noodles)', 'order' => 110 ],
            'thukpa'  => [ 'name' => 'Thukpa',             'icon' => '🍲', 'heading' => 'Thukpa',                        'order' => 120 ],
            'tacos'   => [ 'name' => 'Tacos & Salad',      'icon' => '🌮', 'heading' => 'Nepali Tacos & Salad',          'order' => 130 ],
            'soups'   => [ 'name' => 'Soups',              'icon' => '🥣', 'heading' => 'Soups',                         'order' => 140 ],
            'drinks'  => [ 'name' => 'Drinks',             'icon' => '🍵', 'heading' => '',                              'order' => 150 ],
            'platter' => [ 'name' => 'Platter & Hookah',   'icon' => '⭐', 'heading' => '',                              'order' => 160 ],
        ];

        // [ title, price, category-slug, veg, popular, section, desc ]
        $rows = [
            // Burgers
            [ 'Veg Burger', 150, 'burgers', true, false, '', '' ],
            [ 'Chicken Burger', 200, 'burgers', false, false, '', '' ],
            [ 'French Fries', 120, 'burgers', true, false, '', '' ],
            // Chicken
            [ 'Chicken Taas Khaja', 280, 'chicken', false, false, '', '' ],
            [ 'Hot Chicken Lollipop', 200, 'chicken', false, false, '', '' ],
            [ 'Hot Wings', 200, 'chicken', false, false, '', '' ],
            [ 'Sausage Chilly', 180, 'chicken', false, false, '', '' ],
            [ 'Chicken Chilly', 220, 'chicken', false, false, '', '' ],
            [ 'Dragon Chicken', 250, 'chicken', false, false, '', '' ],
            [ 'Chicken Lollipop', 180, 'chicken', false, false, '', '' ],
            [ 'Chicken Roast', 250, 'chicken', false, false, '', '' ],
            [ 'Chicken 65', 250, 'chicken', false, false, '', '' ],
            // Veg & Momo
            [ 'Veg Momo (Steam)', 100, 'vegmomo', true, false, '', '' ],
            [ 'Veg Momo (Fry)', 110, 'vegmomo', true, false, '', '' ],
            [ 'Veg Momo (Kothey)', 120, 'vegmomo', true, false, '', '' ],
            [ 'Veg Momo (C-Momo)', 130, 'vegmomo', true, false, '', '' ],
            [ 'Veg Momo (Jhol)', 130, 'vegmomo', true, false, '', '' ],
            [ 'Veg Momo (Crunchy)', 160, 'vegmomo', true, false, '', '' ],
            [ 'Veg Khaja Set', 200, 'vegmomo', true, false, '', '' ],
            // Chicken Momo
            [ 'Chicken Momo (Steam)', 120, 'cmomo', false, false, '', '' ],
            [ 'Chicken Momo (Fry)', 130, 'cmomo', false, false, '', '' ],
            [ 'Chicken Momo (Kothey)', 140, 'cmomo', false, false, '', '' ],
            [ 'Chicken Momo (C-Momo)', 160, 'cmomo', false, false, '', '' ],
            [ 'Chicken Momo (Jhol)', 160, 'cmomo', false, false, '', '' ],
            [ 'Chicken Momo (Crunchy)', 190, 'cmomo', false, false, '', '' ],
            // Chowmein
            [ 'Veg Chowmein', 100, 'chow', true, false, '', '' ],
            [ 'Egg Chowmein', 120, 'chow', false, false, '', '' ],
            [ 'Chicken Chowmein', 130, 'chow', false, false, '', '' ],
            [ 'Spot Special Chowmein', 180, 'chow', false, true, '', '' ],
            [ 'Kima Noodles', 180, 'chow', false, false, '', '' ],
            // Mini Pizza
            [ 'Cheese Pizza', 120, 'pizza', true, false, '', '' ],
            [ 'Veg Pizza', 140, 'pizza', true, false, '', '' ],
            [ 'Chicken Pizza', 160, 'pizza', false, false, '', '' ],
            [ 'Spot Special Pizza', 350, 'pizza', false, true, '', '' ],
            // Fried Rice
            [ 'Veg Fried Rice', 120, 'rice', true, false, '', '' ],
            [ 'Egg Fried Rice', 140, 'rice', false, false, '', '' ],
            [ 'Chicken Fried Rice', 160, 'rice', false, false, '', '' ],
            [ 'Spot Special Fried Rice', 200, 'rice', false, true, '', '' ],
            // Rolls
            [ 'Veg Rolls', 150, 'rolls', true, false, '', '' ],
            [ 'Chicken Rolls', 180, 'rolls', false, false, '', '' ],
            [ 'Spring Rolls', 160, 'rolls', true, false, '', '' ],
            [ 'Chrispy Cheese Chicken Rolls', 200, 'rolls', false, false, '', '' ],
            // Paratha
            [ 'Aalu Paratha', 60, 'paratha', true, false, '', '' ],
            [ 'Paneer Paratha', 100, 'paratha', true, false, '', '' ],
            [ 'Chicken Cheese Paratha', 120, 'paratha', false, false, '', '' ],
            // Snacks
            [ 'Sweet Corns', 120, 'snacks', true, false, '', '' ],
            [ 'Crispy Sweet Corns', 150, 'snacks', true, false, '', '' ],
            [ 'Veg Pakauda', 100, 'snacks', true, false, '', '' ],
            [ 'Chicken Pakauda', 150, 'snacks', false, false, '', '' ],
            [ 'Chicken Sausage (Per Piece)', 50, 'snacks', false, false, '', '' ],
            [ 'Syafale (Per Piece)', 60, 'snacks', false, false, '', '' ],
            [ 'Corn Dog', 150, 'snacks', false, false, '', '' ],
            [ 'Cheese Corn Dog', 200, 'snacks', false, false, '', '' ],
            [ 'Potato Cheese Balls', 220, 'snacks', true, false, '', '' ],
            [ 'Chips Chilly', 160, 'snacks', true, false, '', '' ],
            // Current / Noodles
            [ 'Veg Current', 80, 'noodles', true, false, '', '' ],
            [ 'Current with Egg', 100, 'noodles', false, false, '', '' ],
            [ 'Spot Special Current', 150, 'noodles', false, true, '', '' ],
            // Thukpa
            [ 'Veg Thukpa', 140, 'thukpa', true, false, '', '' ],
            [ 'Chicken Thukpa', 160, 'thukpa', false, false, '', '' ],
            [ 'Spot Special Thukpa', 200, 'thukpa', false, true, '', '' ],
            // Tacos & Salad
            [ 'Paneer Cheese Tacos', 180, 'tacos', true, false, '', '' ],
            [ 'Chicken Cheese Tacos', 200, 'tacos', false, false, '', '' ],
            [ 'Green Salad', 150, 'tacos', true, false, '', '' ],
            [ 'Fruit Salad', 200, 'tacos', true, false, '', '' ],
            // Soups
            [ 'Chicken Soup', 120, 'soups', false, false, '', '' ],
            [ 'Veg Soup', 100, 'soups', true, false, '', '' ],
            [ 'Mushroom Soup', 110, 'soups', true, false, '', '' ],
            // Drinks — Tea
            [ 'Black Tea', 20, 'drinks', false, false, '🍵 Tea', '' ],
            [ 'Lemon Tea', 20, 'drinks', false, false, '🍵 Tea', '' ],
            [ 'Milk Tea', 25, 'drinks', false, false, '🍵 Tea', '' ],
            [ 'Spot Special Tea', 35, 'drinks', false, true, '🍵 Tea', '' ],
            [ 'Matka Tea', 50, 'drinks', false, false, '🍵 Tea', '' ],
            // Drinks — Coffee
            [ 'Black Coffee', 50, 'drinks', false, false, '☕ Coffee', '' ],
            [ 'Milk Coffee', 80, 'drinks', false, false, '☕ Coffee', '' ],
            // Drinks — Milk Shakes & Lassi
            [ 'Banana Milk Shake', 120, 'drinks', false, false, '🥛 Milk Shakes & Lassi', '' ],
            [ 'Chocolate Milk Shake', 160, 'drinks', false, false, '🥛 Milk Shakes & Lassi', '' ],
            [ 'Strawberry Milk Shake', 160, 'drinks', false, false, '🥛 Milk Shakes & Lassi', '' ],
            [ 'Vanilla Milk Shake', 170, 'drinks', false, false, '🥛 Milk Shakes & Lassi', '' ],
            [ 'Plain Lassi', 80, 'drinks', false, false, '🥛 Milk Shakes & Lassi', '' ],
            [ 'Sweet Lassi', 80, 'drinks', false, false, '🥛 Milk Shakes & Lassi', '' ],
            [ 'Banana Lassi', 100, 'drinks', false, false, '🥛 Milk Shakes & Lassi', '' ],
            // Drinks — Cold Drinks
            [ 'Coke / Fanta / Sprite', 60, 'drinks', false, false, '🥤 Cold Drinks', '' ],
            [ 'Masala Sprite', 80, 'drinks', false, false, '🥤 Cold Drinks', '' ],
            [ 'Masala Soda', 80, 'drinks', false, false, '🥤 Cold Drinks', '' ],
            [ 'Mint Lemonade', 120, 'drinks', false, false, '🥤 Cold Drinks', '' ],
            [ 'Hot Chocolate', 140, 'drinks', false, false, '🥤 Cold Drinks', '' ],
            [ 'Virjin Mojito', 150, 'drinks', false, false, '🥤 Cold Drinks', '' ],
            [ 'Blue Lagoon', 180, 'drinks', false, false, '🥤 Cold Drinks', '' ],
            // Platter & Hookah
            [ 'Spot Special Platter', 999, 'platter', false, true, '', 'Lollipop 3pc • Hot Wings 2pc • Pizza • Kothey Momo 4pc • Chicken C-Momo 4pc • French Fries • Chicken Noodles • Salad' ],
            [ 'Hookah (Mint)', 300, 'platter', false, false, '💨 Hookah', '' ],
            [ 'Hookah (Lady Killer)', 400, 'platter', false, false, '💨 Hookah', '' ],
            [ 'Hookah Extra Coal', 50, 'platter', false, false, '💨 Hookah', '' ],
        ];

        $items = [];
        foreach ( $rows as $r ) {
            $items[] = [
                'title'   => $r[0],
                'price'   => $r[1],
                'cat'     => $r[2],
                'veg'     => $r[3],
                'popular' => $r[4],
                'section' => $r[5],
                'desc'    => $r[6],
            ];
        }

        return [ 'categories' => $categories, 'items' => $items ];
    }

    private static function ensure_categories( array $categories ): void {
        foreach ( $categories as $slug => $cat ) {
            $term = get_term_by( 'slug', $slug, 'rp_menu_category' );
            if ( ! $term ) {
                $created = wp_insert_term( $cat['name'], 'rp_menu_category', [ 'slug' => $slug ] );
                if ( is_wp_error( $created ) ) {
                    continue;
                }
                $term_id = (int) $created['term_id'];
                update_term_meta( $term_id, '_rp_icon', $cat['icon'] );
                update_term_meta( $term_id, '_rp_heading', $cat['heading'] );
                update_term_meta( $term_id, '_rp_order', (int) $cat['order'] );
            }
        }
    }

    private static function item_exists( string $title ): bool {
        global $wpdb;
        $id = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'rp_menu_item' AND post_status NOT IN ( 'trash' ) LIMIT 1",
            $title
        ) );
        return ! empty( $id );
    }

    private static function insert_item( array $item, int $order ): void {
        $post_id = wp_insert_post( [
            'post_type'    => 'rp_menu_item',
            'post_title'   => $item['title'],
            'post_status'  => 'publish',
            'post_content' => $item['desc'] ?? '',
            'menu_order'   => $order,
        ], true );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            return;
        }

        update_post_meta( $post_id, '_rp_price', (float) $item['price'] );
        update_post_meta( $post_id, '_rp_is_available', '1' );
        update_post_meta( $post_id, '_rp_is_veg', ! empty( $item['veg'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_rp_is_popular', ! empty( $item['popular'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_rp_is_spicy', '0' );
        update_post_meta( $post_id, '_rp_section', $item['section'] ?? '' );

        $term = get_term_by( 'slug', $item['cat'], 'rp_menu_category' );
        if ( $term ) {
            wp_set_object_terms( $post_id, (int) $term->term_id, 'rp_menu_category', false );
        }
    }

    /**
     * Import one chunk of the catalogue starting at $offset.
     * Categories are (re)ensured on the first chunk only.
     */
    public static function import_chunk( int $offset, int $limit ): array {
        $catalog = self::catalog();

        if ( $offset === 0 ) {
            self::ensure_categories( $catalog['categories'] );
        }

        $items = $catalog['items'];
        $total = count( $items );
        $slice = array_slice( $items, $offset, $limit );

        $created = 0;
        $skipped = 0;
        foreach ( $slice as $i => $item ) {
            if ( self::item_exists( $item['title'] ) ) {
                $skipped++;
                continue;
            }
            self::insert_item( $item, ( $offset + $i ) * 10 );
            $created++;
        }

        $processed = min( $offset + $limit, $total );
        return [
            'processed' => $processed,
            'total'     => $total,
            'created'   => $created,
            'skipped'   => $skipped,
            'done'      => $processed >= $total,
        ];
    }

    public static function ajax_seed(): void {
        check_ajax_referer( 'rp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'rp_manage_menu' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
        }

        $offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
        $result = self::import_chunk( $offset, self::CHUNK );

        wp_send_json_success( $result );
    }
}
