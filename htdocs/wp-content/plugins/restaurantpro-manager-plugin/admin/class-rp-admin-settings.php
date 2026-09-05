<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Admin_Settings {

    public function render(): void {
        $tab = sanitize_text_field( $_GET['tab'] ?? 'general' );

        if ( isset( $_POST['rp_save_settings'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'rp_save_settings' ) && current_user_can( 'rp_manage_settings' ) ) {
            $this->save( $tab );
        }

        if ( $tab === 'gallery' ) {
            wp_enqueue_media();
            include RP_PLUGIN_DIR . 'admin/views/gallery-settings.php';
            return;
        }

        include RP_PLUGIN_DIR . 'admin/views/settings.php';
    }

    private function save( string $tab ): void {
        if ( $tab === 'general' ) {
            $fields = [ 'restaurant_name', 'restaurant_phone', 'restaurant_email', 'restaurant_address', 'currency_symbol', 'currency_position', 'contact_name_placeholder', 'whatsapp_number', 'hero_badge', 'hero_tagline' ];
            foreach ( $fields as $field ) {
                if ( isset( $_POST[ $field ] ) ) {
                    RP_Settings::set( $field, sanitize_text_field( $_POST[ $field ] ) );
                }
            }

            // Links shown on the website. Saved raw-but-escaped so an empty box
            // simply hides the link instead of printing a broken one.
            foreach ( [ 'facebook_url', 'tiktok_url', 'instagram_url', 'map_url' ] as $field ) {
                if ( isset( $_POST[ $field ] ) ) {
                    $url = trim( (string) $_POST[ $field ] );
                    RP_Settings::set( $field, $url === '' ? '' : esc_url_raw( $url ) );
                }
            }

            if ( isset( $_POST['about_text'] ) ) {
                RP_Settings::set( 'about_text', sanitize_textarea_field( $_POST['about_text'] ) );
            }
        }

        if ( $tab === 'hours' ) {
            $days = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
            $hours = [];
            foreach ( $days as $day ) {
                $hours[ $day ] = [
                    'open'   => sanitize_text_field( $_POST["hours_{$day}_open"] ?? '10:00' ),
                    'close'  => sanitize_text_field( $_POST["hours_{$day}_close"] ?? '22:00' ),
                    'closed' => isset( $_POST["hours_{$day}_closed"] ),
                ];
            }
            RP_Settings::set( 'opening_hours', wp_json_encode( $hours ) );
        }

        if ( $tab === 'billing' ) {
            RP_Settings::set( 'vat_enabled', isset( $_POST['vat_enabled'] ) ? '1' : '0' );
            RP_Settings::set( 'service_enabled', isset( $_POST['service_enabled'] ) ? '1' : '0' );
            RP_Settings::set( 'vat_rate', max( 0, min( 100, floatval( $_POST['vat_rate'] ?? 0 ) ) ) );
            RP_Settings::set( 'service_rate', max( 0, min( 100, floatval( $_POST['service_rate'] ?? 0 ) ) ) );
            RP_Settings::set( 'invoice_prefix', sanitize_text_field( $_POST['invoice_prefix'] ?? 'INV-' ) );
            RP_Settings::set( 'bill_footer_note', sanitize_textarea_field( $_POST['bill_footer_note'] ?? '' ) );
        }

        if ( $tab === 'gallery' ) {
            $ids = array_map( 'absint', explode( ',', sanitize_text_field( $_POST['gallery_images'] ?? '' ) ) );
            $ids = array_filter( $ids );
            RP_Settings::set( 'gallery_images', wp_json_encode( array_values( $ids ) ) );
        }
    }
}
