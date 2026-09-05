<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Ajax_Public {

    public function register(): void {
        add_action( 'wp_ajax_rp_search_menu_items', [ $this, 'search_menu' ] );
        add_action( 'wp_ajax_nopriv_rp_search_menu_items', [ $this, 'search_menu' ] );
        add_action( 'wp_ajax_rp_filter_menu', [ $this, 'filter_menu' ] );
        add_action( 'wp_ajax_nopriv_rp_filter_menu', [ $this, 'filter_menu' ] );
        add_action( 'wp_ajax_rp_submit_reservation', [ $this, 'submit_reservation' ] );
        add_action( 'wp_ajax_nopriv_rp_submit_reservation', [ $this, 'submit_reservation' ] );
        add_action( 'wp_ajax_rp_submit_enquiry', [ $this, 'submit_enquiry' ] );
        add_action( 'wp_ajax_nopriv_rp_submit_enquiry', [ $this, 'submit_enquiry' ] );
        add_action( 'wp_ajax_rp_refresh_nonce', [ $this, 'refresh_nonce' ] );
        add_action( 'wp_ajax_nopriv_rp_refresh_nonce', [ $this, 'refresh_nonce' ] );
    }

    /**
     * Hand out a fresh public nonce.
     *
     * The homepage can be served from the page cache long after its nonce has
     * expired, which would make every form submission fail. The front-end asks
     * for a new token here and retries once. Deliberately returns nothing but a
     * nonce, so it is safe to leave open.
     */
    public function refresh_nonce(): void {
        nocache_headers();
        wp_send_json_success( [ 'nonce' => wp_create_nonce( 'rp_public_nonce' ) ] );
    }

    public function search_menu(): void {
        $query = sanitize_text_field( $_POST['query'] ?? '' );

        $args = [
            'post_type'      => 'rp_menu_item',
            'posts_per_page' => 30,
            'post_status'    => 'publish',
            's'              => $query,
            'meta_query'     => [
                [ 'key' => '_rp_is_available', 'value' => '1' ],
            ],
        ];

        $this->send_menu_response( $args );
    }

    public function filter_menu(): void {
        $category = absint( $_POST['category'] ?? 0 );

        $args = [
            'post_type'      => 'rp_menu_item',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'meta_query'     => [
                [ 'key' => '_rp_is_available', 'value' => '1' ],
            ],
        ];

        if ( $category ) {
            $args['tax_query'] = [
                [ 'taxonomy' => 'rp_menu_category', 'terms' => $category ],
            ];
        }

        $this->send_menu_response( $args );
    }

    private function send_menu_response( array $args ): void {
        $posts = get_posts( $args );
        $html = '';

        foreach ( $posts as $post ) {
            $price   = (float) get_post_meta( $post->ID, '_rp_price', true );
            $is_veg  = get_post_meta( $post->ID, '_rp_is_veg', true );
            $is_spicy = get_post_meta( $post->ID, '_rp_is_spicy', true );
            $is_popular = get_post_meta( $post->ID, '_rp_is_popular', true );
            $image   = get_the_post_thumbnail_url( $post->ID, 'rp-menu-card' ) ?: '';

            $html .= '<div class="col-md-6 col-lg-4 col-xl-3 mb-4">';
            $html .= '<div class="card rp-menu-card h-100 border-0 shadow-sm">';
            if ( $image ) {
                $html .= '<img src="' . esc_url( $image ) . '" class="card-img-top" alt="' . esc_attr( $post->post_title ) . '" loading="lazy">';
            }
            $html .= '<div class="card-body">';
            $html .= '<h5 class="card-title">' . esc_html( $post->post_title ) . '</h5>';
            if ( $post->post_excerpt ) {
                $html .= '<p class="card-text text-muted small">' . esc_html( wp_trim_words( $post->post_excerpt, 12 ) ) . '</p>';
            }
            $html .= '<div class="d-flex justify-content-between align-items-center">';
            $html .= '<span class="rp-menu-price fw-bold">' . esc_html( RP_Helpers::format_price( $price ) ) . '</span>';
            $html .= '<div class="rp-menu-tags">';
            if ( $is_veg === '1' ) $html .= '<span class="badge bg-success">Veg</span> ';
            if ( $is_spicy === '1' ) $html .= '<span class="badge bg-danger">Spicy</span> ';
            if ( $is_popular === '1' ) $html .= '<span class="badge bg-warning text-dark">Popular</span>';
            $html .= '</div></div></div></div></div>';
        }

        if ( empty( $posts ) ) {
            $html = '<div class="col-12"><p class="text-center text-muted py-4">No items found.</p></div>';
        }

        wp_send_json_success( [ 'html' => $html, 'count' => count( $posts ) ] );
    }

    public function submit_reservation(): void {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_public_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed. Please refresh the page and try again.' ], 403 );
        }

        // Hidden field only bots fill in.
        if ( ! empty( $_POST['rp_hp'] ) ) {
            wp_send_json_success( [ 'message' => 'Thank you!' ] );
        }

        if ( ! $this->throttle( 'res' ) ) {
            wp_send_json_error( [ 'message' => 'Too many requests. Please try again in a few minutes, or call us directly.' ], 429 );
        }

        $name   = sanitize_text_field( $_POST['guest_name'] ?? '' );
        $phone  = sanitize_text_field( $_POST['phone'] ?? '' );
        $email  = sanitize_email( $_POST['email'] ?? '' );
        $date   = sanitize_text_field( $_POST['reservation_date'] ?? '' );
        $time   = sanitize_text_field( $_POST['reservation_time'] ?? '' );
        $guests = absint( $_POST['guests'] ?? 2 );
        $notes  = sanitize_textarea_field( $_POST['notes'] ?? '' );

        if ( ! $name || ! $date || ! $time ) {
            wp_send_json_error( [ 'message' => 'Name, date, and time are required.' ], 400 );
        }

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( [ 'message' => 'Invalid date format.' ], 400 );
        }

        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'rp_reservations',
            [
                'guest_name'       => $name,
                'phone'            => $phone,
                'email'            => $email,
                'reservation_date' => $date,
                'reservation_time' => $time,
                'guests'           => $guests,
                'status'           => 'pending',
                'notes'            => $notes,
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
        );

        // Wake up the panel bell (with sound) the same way a new POS order does —
        // reservations previously never raised a notification at all.
        if ( class_exists( 'RP_Notifications' ) ) {
            RP_Notifications::add(
                'reservation',
                'New Reservation',
                sprintf( '%s requested a table for %d on %s at %s.', $name, $guests, $date, $time )
            );
        }

        // Send email notification
        $restaurant_email = RP_Settings::get( 'restaurant_email', get_option( 'admin_email' ) );
        $restaurant_name  = RP_Settings::get( 'restaurant_name', 'RestaurantPro' );

        $subject = "New Reservation: {$name} on {$date}";
        $body = "New reservation received:\n\n";
        $body .= "Name: {$name}\nPhone: {$phone}\nEmail: {$email}\n";
        $body .= "Date: {$date}\nTime: {$time}\nGuests: {$guests}\n";
        if ( $notes ) $body .= "Notes: {$notes}\n";

        wp_mail( $restaurant_email, $subject, $body, [ "From: {$restaurant_name} <{$restaurant_email}>" ] );

        wp_send_json_success( [ 'message' => 'Reservation submitted successfully! We will confirm shortly.' ] );
    }

    /**
     * Website contact / enquiry form. Saved to the database first (so nothing is
     * ever lost on a host with unreliable mail), then emailed best-effort.
     */
    public function submit_enquiry(): void {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'rp_public_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed. Please refresh the page and try again.' ], 403 );
        }

        // Honeypot: a hidden field that only bots fill in.
        if ( ! empty( $_POST['rp_hp'] ) ) {
            wp_send_json_success( [ 'message' => 'Thank you! Your message has been sent.' ] );
        }

        if ( ! $this->throttle( 'msg' ) ) {
            wp_send_json_error( [ 'message' => 'Too many messages from this connection. Please try again later, or call us directly.' ], 429 );
        }

        $name    = sanitize_text_field( $_POST['name'] ?? '' );
        $phone   = sanitize_text_field( $_POST['phone'] ?? '' );
        $email   = sanitize_email( $_POST['email'] ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( '' === $name || '' === $message ) {
            wp_send_json_error( [ 'message' => 'Please tell us your name and your message.' ], 400 );
        }
        if ( '' === $phone && '' === $email ) {
            wp_send_json_error( [ 'message' => 'Please leave a phone number or an email so we can reply.' ], 400 );
        }
        if ( mb_strlen( $message ) > 3000 ) {
            $message = mb_substr( $message, 0, 3000 );
        }

        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'rp_messages',
            [
                'name'       => $name,
                'phone'      => $phone,
                'email'      => $email,
                'message'    => $message,
                'status'     => 'unread',
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            wp_send_json_error( [ 'message' => 'Sorry, we could not save your message. Please call or WhatsApp us instead.' ], 500 );
        }

        // Same live bell + sound treatment as new POS orders — website enquiries
        // previously never raised a notification at all.
        if ( class_exists( 'RP_Notifications' ) ) {
            RP_Notifications::add( 'message', 'New Website Message', sprintf( '%s sent a message from the website.', $name ) );
        }

        $to        = RP_Settings::get( 'restaurant_email', get_option( 'admin_email' ) );
        $shop_name = RP_Settings::get( 'restaurant_name', get_bloginfo( 'name' ) );

        $body  = "New enquiry from the website:\n\n";
        $body .= "Name: {$name}\n";
        if ( $phone ) { $body .= "Phone: {$phone}\n"; }
        if ( $email ) { $body .= "Email: {$email}\n"; }
        $body .= "\nMessage:\n{$message}\n";
        $body .= "\nRead it in the dashboard: " . admin_url( 'admin.php?page=rp-messages' ) . "\n";

        $headers = [ "From: {$shop_name} <{$to}>" ];
        if ( $email ) {
            $headers[] = "Reply-To: {$name} <{$email}>";
        }

        wp_mail( $to, "Website enquiry from {$name}", $body, $headers );

        wp_send_json_success( [ 'message' => 'Thank you! Your message has been sent — we will get back to you soon.' ] );
    }

    /**
     * Very light per-IP rate limit using transients. Returns false when the
     * caller has exceeded the allowance.
     */
    private function throttle( string $bucket, int $max = 5, int $window = 600 ): bool {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
        $key = 'rp_thr_' . $bucket . '_' . md5( $ip );

        $hits = (int) get_transient( $key );
        if ( $hits >= $max ) {
            return false;
        }

        set_transient( $key, $hits + 1, $window );
        return true;
    }
}
