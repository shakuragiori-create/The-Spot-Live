<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Settings {

    private static array $cache = [];

    public static function get( string $key, mixed $default = '' ): mixed {
        if ( isset( self::$cache[ $key ] ) ) {
            return self::$cache[ $key ];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rp_settings';

        $value = $wpdb->get_var( $wpdb->prepare(
            "SELECT option_value FROM $table WHERE option_key = %s",
            $key
        ) );

        if ( null === $value ) {
            return $default;
        }

        self::$cache[ $key ] = $value;
        return $value;
    }

    public static function set( string $key, mixed $value ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'rp_settings';

        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE option_key = %s",
            $key
        ) );

        if ( $exists ) {
            $result = $wpdb->update(
                $table,
                [ 'option_value' => $value ],
                [ 'option_key' => $key ],
                [ '%s' ],
                [ '%s' ]
            );
        } else {
            $result = $wpdb->insert(
                $table,
                [ 'option_key' => $key, 'option_value' => $value ],
                [ '%s', '%s' ]
            );
        }

        if ( false !== $result ) {
            self::$cache[ $key ] = $value;
            return true;
        }
        return false;
    }

    public static function set_if_empty( string $key, mixed $value ): void {
        $current = self::get( $key, null );
        if ( null === $current ) {
            self::set( $key, $value );
        }
    }

    public static function delete( string $key ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'rp_settings';
        $wpdb->delete( $table, [ 'option_key' => $key ], [ '%s' ] );
        unset( self::$cache[ $key ] );
    }

    public static function get_all(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'rp_settings';
        $rows = $wpdb->get_results( "SELECT option_key, option_value FROM $table", ARRAY_A );
        $settings = [];
        foreach ( $rows as $row ) {
            $settings[ $row['option_key'] ] = $row['option_value'];
            self::$cache[ $row['option_key'] ] = $row['option_value'];
        }
        return $settings;
    }
}
