<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Public {

    public function enqueue_assets(): void {
        wp_enqueue_style(
            'rp-public',
            RP_PLUGIN_URL . 'public/css/rp-public.css',
            [],
            RP_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'rp-public-js',
            RP_PLUGIN_URL . 'public/js/rp-public.js',
            [ 'jquery' ],
            RP_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'rp-public-js', 'rpPublic', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'rp_public_nonce' ),
        ] );
    }
}
