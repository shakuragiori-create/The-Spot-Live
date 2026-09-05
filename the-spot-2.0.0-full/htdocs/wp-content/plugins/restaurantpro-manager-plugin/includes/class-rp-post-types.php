<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Post_Types {

    public static function register(): void {
        self::register_menu_item();
        self::register_testimonial();
        self::register_menu_category();
    }

    private static function register_menu_item(): void {
        register_post_type( 'rp_menu_item', [
            'labels' => [
                'name'               => 'Menu Items',
                'singular_name'      => 'Menu Item',
                'add_new_item'       => 'Add New Menu Item',
                'edit_item'          => 'Edit Menu Item',
                'search_items'       => 'Search Menu Items',
                'not_found'          => 'No menu items found',
            ],
            'public'             => true,
            'has_archive'        => true,
            'rewrite'            => [ 'slug' => 'menu-item' ],
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
            'menu_icon'          => 'dashicons-food',
            'show_in_rest'       => true,
            'capability_type'    => 'post',
        ] );

        register_post_meta( 'rp_menu_item', '_rp_price', [
            'type'         => 'number',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => 0,
        ] );
        register_post_meta( 'rp_menu_item', '_rp_is_available', [
            'type'         => 'boolean',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => true,
        ] );
        register_post_meta( 'rp_menu_item', '_rp_is_spicy', [
            'type'         => 'boolean',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => false,
        ] );
        register_post_meta( 'rp_menu_item', '_rp_is_veg', [
            'type'         => 'boolean',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => false,
        ] );
        register_post_meta( 'rp_menu_item', '_rp_is_popular', [
            'type'         => 'boolean',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => false,
        ] );
        register_post_meta( 'rp_menu_item', '_rp_section', [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => '',
        ] );

        add_action( 'add_meta_boxes', [ __CLASS__, 'add_menu_item_meta_box' ] );
        add_action( 'save_post_rp_menu_item', [ __CLASS__, 'save_menu_item_meta' ] );
    }

    public static function add_menu_item_meta_box(): void {
        add_meta_box(
            'rp_menu_item_details',
            'Menu Item Details',
            [ __CLASS__, 'render_menu_item_meta_box' ],
            'rp_menu_item',
            'side',
            'high'
        );
    }

    public static function render_menu_item_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'rp_menu_item_meta', 'rp_menu_item_nonce' );
        $price     = get_post_meta( $post->ID, '_rp_price', true );
        $available = get_post_meta( $post->ID, '_rp_is_available', true );
        $spicy     = get_post_meta( $post->ID, '_rp_is_spicy', true );
        $veg       = get_post_meta( $post->ID, '_rp_is_veg', true );
        $popular   = get_post_meta( $post->ID, '_rp_is_popular', true );
        $section   = get_post_meta( $post->ID, '_rp_section', true );

        if ( $available === '' ) $available = '1';
        ?>
        <p>
            <label for="rp_price"><strong>Price:</strong></label><br>
            <input type="number" id="rp_price" name="rp_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0" style="width:100%">
        </p>
        <p>
            <label for="rp_section"><strong>Section (optional):</strong></label><br>
            <input type="text" id="rp_section" name="rp_section" value="<?php echo esc_attr( $section ); ?>" placeholder="e.g. Coffee, Cold Drinks" style="width:100%">
            <span class="description">Sub-heading within the category (leave blank for none).</span>
        </p>
        <p>
            <label><input type="checkbox" name="rp_is_available" value="1" <?php checked( $available, '1' ); ?>> Available</label>
        </p>
        <p>
            <label><input type="checkbox" name="rp_is_veg" value="1" <?php checked( $veg, '1' ); ?>> Vegetarian</label>
        </p>
        <p>
            <label><input type="checkbox" name="rp_is_spicy" value="1" <?php checked( $spicy, '1' ); ?>> Spicy</label>
        </p>
        <p>
            <label><input type="checkbox" name="rp_is_popular" value="1" <?php checked( $popular, '1' ); ?>> Popular</label>
        </p>
        <?php
    }

    public static function save_menu_item_meta( int $post_id ): void {
        if ( ! isset( $_POST['rp_menu_item_nonce'] ) || ! wp_verify_nonce( $_POST['rp_menu_item_nonce'], 'rp_menu_item_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        update_post_meta( $post_id, '_rp_price', floatval( $_POST['rp_price'] ?? 0 ) );
        update_post_meta( $post_id, '_rp_is_available', isset( $_POST['rp_is_available'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_rp_is_veg', isset( $_POST['rp_is_veg'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_rp_is_spicy', isset( $_POST['rp_is_spicy'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_rp_is_popular', isset( $_POST['rp_is_popular'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_rp_section', sanitize_text_field( $_POST['rp_section'] ?? '' ) );
    }

    private static function register_testimonial(): void {
        register_post_type( 'rp_testimonial', [
            'labels' => [
                'name'               => 'Testimonials',
                'singular_name'      => 'Testimonial',
                'add_new_item'       => 'Add New Testimonial',
                'edit_item'          => 'Edit Testimonial',
            ],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'supports'           => [ 'editor', 'thumbnail' ],
            'menu_icon'          => 'dashicons-format-quote',
            'show_in_rest'       => true,
        ] );

        register_post_meta( 'rp_testimonial', '_rp_author_name', [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => '',
        ] );
        register_post_meta( 'rp_testimonial', '_rp_rating', [
            'type'         => 'integer',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => 5,
        ] );

        add_action( 'add_meta_boxes', [ __CLASS__, 'add_testimonial_meta_box' ] );
        add_action( 'save_post_rp_testimonial', [ __CLASS__, 'save_testimonial_meta' ] );
    }

    public static function add_testimonial_meta_box(): void {
        add_meta_box(
            'rp_testimonial_details',
            'Testimonial Details',
            [ __CLASS__, 'render_testimonial_meta_box' ],
            'rp_testimonial',
            'side',
            'high'
        );
    }

    public static function render_testimonial_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'rp_testimonial_meta', 'rp_testimonial_nonce' );
        $author = get_post_meta( $post->ID, '_rp_author_name', true );
        $rating = get_post_meta( $post->ID, '_rp_rating', true ) ?: 5;
        ?>
        <p>
            <label for="rp_author_name"><strong>Author Name:</strong></label><br>
            <input type="text" id="rp_author_name" name="rp_author_name" value="<?php echo esc_attr( $author ); ?>" style="width:100%">
        </p>
        <p>
            <label for="rp_rating"><strong>Rating (1-5):</strong></label><br>
            <input type="number" id="rp_rating" name="rp_rating" value="<?php echo esc_attr( $rating ); ?>" min="1" max="5" style="width:60px">
        </p>
        <?php
    }

    public static function save_testimonial_meta( int $post_id ): void {
        if ( ! isset( $_POST['rp_testimonial_nonce'] ) || ! wp_verify_nonce( $_POST['rp_testimonial_nonce'], 'rp_testimonial_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        update_post_meta( $post_id, '_rp_author_name', sanitize_text_field( $_POST['rp_author_name'] ?? '' ) );
        $rating = intval( $_POST['rp_rating'] ?? 5 );
        $rating = max( 1, min( 5, $rating ) );
        update_post_meta( $post_id, '_rp_rating', $rating );
    }

    private static function register_menu_category(): void {
        register_taxonomy( 'rp_menu_category', 'rp_menu_item', [
            'labels' => [
                'name'          => 'Menu Categories',
                'singular_name' => 'Menu Category',
                'add_new_item'  => 'Add New Category',
            ],
            'hierarchical'  => true,
            'public'        => true,
            'show_in_rest'  => true,
            'rewrite'       => [ 'slug' => 'menu-category' ],
            'show_admin_column' => true,
        ] );

        add_action( 'rp_menu_category_add_form_fields', [ __CLASS__, 'category_add_fields' ] );
        add_action( 'rp_menu_category_edit_form_fields', [ __CLASS__, 'category_edit_fields' ] );
        add_action( 'created_rp_menu_category', [ __CLASS__, 'save_category_fields' ] );
        add_action( 'edited_rp_menu_category', [ __CLASS__, 'save_category_fields' ] );
    }

    public static function category_add_fields(): void {
        ?>
        <div class="form-field">
            <label for="rp_cat_icon">Icon (emoji)</label>
            <input type="text" name="rp_cat_icon" id="rp_cat_icon" value="">
            <p>Shown on the menu tab and section title, e.g. 🍔</p>
        </div>
        <div class="form-field">
            <label for="rp_cat_heading">Section Heading</label>
            <input type="text" name="rp_cat_heading" id="rp_cat_heading" value="">
            <p>Big title above the items (defaults to the category name).</p>
        </div>
        <div class="form-field">
            <label for="rp_cat_order">Display Order</label>
            <input type="number" name="rp_cat_order" id="rp_cat_order" value="0" step="1">
        </div>
        <?php
    }

    public static function category_edit_fields( \WP_Term $term ): void {
        $icon    = get_term_meta( $term->term_id, '_rp_icon', true );
        $heading = get_term_meta( $term->term_id, '_rp_heading', true );
        $order   = (int) get_term_meta( $term->term_id, '_rp_order', true );
        ?>
        <tr class="form-field">
            <th scope="row"><label for="rp_cat_icon">Icon (emoji)</label></th>
            <td>
                <input type="text" name="rp_cat_icon" id="rp_cat_icon" value="<?php echo esc_attr( $icon ); ?>">
                <p class="description">Shown on the menu tab and section title, e.g. 🍔</p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rp_cat_heading">Section Heading</label></th>
            <td>
                <input type="text" name="rp_cat_heading" id="rp_cat_heading" value="<?php echo esc_attr( $heading ); ?>">
                <p class="description">Big title above the items (defaults to the category name).</p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rp_cat_order">Display Order</label></th>
            <td><input type="number" name="rp_cat_order" id="rp_cat_order" value="<?php echo esc_attr( $order ); ?>" step="1"></td>
        </tr>
        <?php
    }

    public static function save_category_fields( int $term_id ): void {
        if ( isset( $_POST['rp_cat_icon'] ) ) {
            update_term_meta( $term_id, '_rp_icon', sanitize_text_field( wp_unslash( $_POST['rp_cat_icon'] ) ) );
        }
        if ( isset( $_POST['rp_cat_heading'] ) ) {
            update_term_meta( $term_id, '_rp_heading', sanitize_text_field( wp_unslash( $_POST['rp_cat_heading'] ) ) );
        }
        if ( isset( $_POST['rp_cat_order'] ) ) {
            update_term_meta( $term_id, '_rp_order', (int) $_POST['rp_cat_order'] );
        }
    }
}
