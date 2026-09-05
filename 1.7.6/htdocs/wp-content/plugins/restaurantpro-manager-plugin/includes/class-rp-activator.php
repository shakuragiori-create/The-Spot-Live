<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Activator {

    public static function activate(): void {
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            deactivate_plugins( plugin_basename( RP_PLUGIN_FILE ) );
            wp_die( 'RestaurantPro Manager requires PHP 8.0 or higher.' );
        }

        RP_Database::create_tables();
        RP_Roles::create();
        self::seed_defaults();

        update_option( 'rp_db_version', RP_DB_VERSION );

        require_once RP_PLUGIN_DIR . 'panel/class-rp-panel.php';
        RP_Panel::flush_rules();
    }

    public static function seed_defaults(): void {
        $defaults = [
            'restaurant_name'  => 'The Spot Fast Food and Tea',
            'restaurant_phone' => '+9779845423522',
            'restaurant_email' => get_option( 'admin_email' ),
            'restaurant_address' => 'Khairahani-08, Parsa, Chitwan',
            'whatsapp_number'  => '9779845423522',
            'facebook_url'     => 'https://www.facebook.com/aakashi.dhungana',
            'tiktok_url'       => 'https://www.tiktok.com/@the.spot.fastfood',
            'map_url'          => 'https://maps.app.goo.gl/fZP9an6p6Z25uME18',
            'currency_symbol'  => 'Rs.',
            'currency_position' => 'before',
            'contact_name_placeholder' => 'Aakash Dhungana',
            'vat_enabled'      => '0',
            'vat_rate'         => '13',
            'service_enabled'  => '0',
            'service_rate'     => '10',
            'invoice_prefix'   => 'INV-',
            'bill_footer_note' => 'Thank you for visiting The Spot! Please come again.',
            'opening_hours'    => wp_json_encode( [
                'monday'    => [ 'open' => '10:00', 'close' => '22:00', 'closed' => false ],
                'tuesday'   => [ 'open' => '10:00', 'close' => '22:00', 'closed' => false ],
                'wednesday' => [ 'open' => '10:00', 'close' => '22:00', 'closed' => false ],
                'thursday'  => [ 'open' => '10:00', 'close' => '22:00', 'closed' => false ],
                'friday'    => [ 'open' => '10:00', 'close' => '22:00', 'closed' => false ],
                'saturday'  => [ 'open' => '10:00', 'close' => '23:00', 'closed' => false ],
                'sunday'    => [ 'open' => '10:00', 'close' => '22:00', 'closed' => false ],
            ] ),
            'gallery_images'   => wp_json_encode( [] ),
        ];

        foreach ( $defaults as $key => $value ) {
            RP_Settings::set_if_empty( $key, $value );
        }
    }
}
