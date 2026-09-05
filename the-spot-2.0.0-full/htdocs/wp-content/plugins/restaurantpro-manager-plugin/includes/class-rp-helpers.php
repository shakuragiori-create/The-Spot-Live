<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Helpers {

    public static function format_price( float $price ): string {
        $symbol   = RP_Settings::get( 'currency_symbol', 'Rs.' );
        $position = RP_Settings::get( 'currency_position', 'before' );
        $formatted = number_format( $price, 2 );

        return $position === 'before'
            ? $symbol . ' ' . $formatted
            : $formatted . ' ' . $symbol;
    }

    public static function get_order_statuses(): array {
        return [
            'new'       => 'New',
            'accepted'  => 'Accepted',
            'preparing' => 'Preparing',
            'ready'     => 'Ready',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function get_kot_statuses(): array {
        return [
            'pending'   => 'Pending',
            'preparing' => 'Preparing',
            'ready'     => 'Ready',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function get_table_statuses(): array {
        return [
            'available'   => 'Available',
            'occupied'    => 'Occupied',
            'reserved'    => 'Reserved',
            'maintenance' => 'Maintenance',
        ];
    }

    public static function get_reservation_statuses(): array {
        return [
            'pending'   => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function get_opening_hours(): array {
        $raw = RP_Settings::get( 'opening_hours', '' );
        $hours = json_decode( $raw, true );
        return is_array( $hours ) ? $hours : [];
    }

    public static function status_badge( string $status, string $type = 'order' ): string {
        $colors = [
            'new'       => 'primary',
            'pending'   => 'warning',
            'accepted'  => 'info',
            'preparing' => 'secondary',
            'ready'     => 'success',
            'completed' => 'dark',
            'cancelled' => 'danger',
            'available' => 'success',
            'occupied'  => 'danger',
            'reserved'  => 'warning',
            'maintenance' => 'secondary',
            'confirmed' => 'success',
        ];
        $color = $colors[ $status ] ?? 'secondary';
        $label = ucfirst( $status );
        return "<span class=\"badge bg-{$color}\">{$label}</span>";
    }

    public static function time_ago( string $datetime ): string {
        $diff = time() - strtotime( $datetime );
        if ( $diff < 60 ) return 'just now';
        if ( $diff < 3600 ) return floor( $diff / 60 ) . 'm ago';
        if ( $diff < 86400 ) return floor( $diff / 3600 ) . 'h ago';
        return wp_date( 'M j, g:i A', strtotime( $datetime ) );
    }
}
