<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Single source of truth for all bill money maths.
 *
 * Used by the POS (creating an order) and by the bill screen (editing an
 * existing order) so both paths always produce identical numbers. Every amount
 * is computed here on the server — the browser only ever shows a preview.
 *
 * Order of operations (standard Nepali restaurant practice):
 *   subtotal → minus discount → plus service charge → plus VAT on (base + service)
 */
class RP_Billing {

    /** Charge defaults from Settings → Billing & Tax. */
    public static function defaults(): array {
        return [
            'vat_enabled'     => RP_Settings::get( 'vat_enabled', '0' ) === '1',
            'vat_rate'        => (float) RP_Settings::get( 'vat_rate', 13 ),
            'service_enabled' => RP_Settings::get( 'service_enabled', '0' ) === '1',
            'service_rate'    => (float) RP_Settings::get( 'service_rate', 10 ),
        ];
    }

    public static function money( mixed $value ): float {
        return round( max( 0, (float) $value ), 2 );
    }

    public static function rate( mixed $value ): float {
        return round( min( 100, max( 0, (float) $value ) ), 2 );
    }

    /**
     * @param array $lines Each: menu_item_id, item_name, quantity, price, notes.
     * @param array $opts  discount_type, discount_value, vat_enabled, vat_rate,
     *                     service_enabled, service_rate, amount_paid.
     */
    public static function calculate( array $lines, array $opts ): array {
        $items    = [];
        $subtotal = 0.0;

        foreach ( $lines as $line ) {
            $item_id = absint( $line['menu_item_id'] ?? 0 );
            $qty     = max( 1, (int) ( $line['quantity'] ?? 1 ) );
            $price   = self::money( $line['price'] ?? 0 );
            $name    = sanitize_text_field( (string) ( $line['item_name'] ?? '' ) );

            // Fall back to the live menu title, then skip anything unnameable.
            if ( '' === $name && $item_id ) {
                $name = (string) get_the_title( $item_id );
            }
            if ( '' === $name ) {
                continue;
            }

            $line_total = round( $price * $qty, 2 );
            $subtotal  += $line_total;

            $items[] = [
                'menu_item_id' => $item_id,
                'item_name'    => $name,
                'quantity'     => $qty,
                'price'        => $price,
                'line_total'   => $line_total,
                'notes'        => sanitize_text_field( (string) ( $line['notes'] ?? '' ) ),
            ];
        }

        $subtotal = round( $subtotal, 2 );

        // Discount
        $discount_type = (string) ( $opts['discount_type'] ?? 'none' );
        if ( ! in_array( $discount_type, [ 'none', 'percent', 'amount' ], true ) ) {
            $discount_type = 'none';
        }
        $discount_value  = self::money( $opts['discount_value'] ?? 0 );
        $discount_amount = 0.0;

        if ( 'percent' === $discount_type ) {
            $discount_value  = self::rate( $discount_value );
            $discount_amount = round( $subtotal * $discount_value / 100, 2 );
        } elseif ( 'amount' === $discount_type ) {
            $discount_amount = min( $subtotal, $discount_value );
        } else {
            $discount_value = 0.0;
        }

        $base = round( $subtotal - $discount_amount, 2 );

        // Service charge on the discounted base
        $service_rate   = empty( $opts['service_enabled'] ) ? 0.0 : self::rate( $opts['service_rate'] ?? 0 );
        $service_amount = round( $base * $service_rate / 100, 2 );

        // VAT on (discounted base + service charge)
        $vat_rate   = empty( $opts['vat_enabled'] ) ? 0.0 : self::rate( $opts['vat_rate'] ?? 0 );
        $vat_amount = round( ( $base + $service_amount ) * $vat_rate / 100, 2 );

        $total       = round( $base + $service_amount + $vat_amount, 2 );
        $amount_paid = self::money( $opts['amount_paid'] ?? 0 );
        $change_due  = $amount_paid > $total ? round( $amount_paid - $total, 2 ) : 0.0;

        return [
            'items'           => $items,
            'subtotal'        => $subtotal,
            'discount_type'   => $discount_type,
            'discount_value'  => $discount_value,
            'discount_amount' => $discount_amount,
            'service_rate'    => $service_rate,
            'service_amount'  => $service_amount,
            'vat_rate'        => $vat_rate,
            'vat_amount'      => $vat_amount,
            'total'           => $total,
            'amount_paid'     => $amount_paid,
            'change_due'      => $change_due,
        ];
    }

    /**
     * Invoice numbers are derived from the order's row id, so two cashiers
     * ringing up at the same moment can never collide.
     */
    public static function invoice_no( int $order_id ): string {
        $prefix = (string) RP_Settings::get( 'invoice_prefix', 'INV-' );
        return $prefix . str_pad( (string) $order_id, 6, '0', STR_PAD_LEFT );
    }

    public static function payment_methods(): array {
        return [
            'cash'   => 'Cash',
            'esewa'  => 'eSewa',
            'khalti' => 'Khalti',
            'fonepay' => 'FonePay / QR',
            'card'   => 'Card',
            'credit' => 'Credit (Due)',
        ];
    }

    public static function order_types(): array {
        return [
            'dine_in'  => 'Dine In',
            'takeaway' => 'Takeaway',
            'delivery' => 'Delivery',
        ];
    }

    /** Write the computed totals of an existing order back to the database. */
    public static function persist( int $order_id, array $calc, array $extra = [] ): void {
        global $wpdb;

        $data = [
            'subtotal'        => $calc['subtotal'],
            'discount_type'   => $calc['discount_type'],
            'discount_value'  => $calc['discount_value'],
            'discount_amount' => $calc['discount_amount'],
            'vat_rate'        => $calc['vat_rate'],
            'vat_amount'      => $calc['vat_amount'],
            'service_rate'    => $calc['service_rate'],
            'service_amount'  => $calc['service_amount'],
            'total'           => $calc['total'],
            'amount_paid'     => $calc['amount_paid'],
            'change_due'      => $calc['change_due'],
        ];
        $format = [ '%f', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f' ];

        foreach ( $extra as $key => $value ) {
            $data[ $key ] = $value;
            $format[]     = '%s';
        }

        $wpdb->update( $wpdb->prefix . 'rp_orders', $data, [ 'id' => $order_id ], $format, [ '%d' ] );

        // Replace the line items wholesale — simplest correct behaviour for an edit.
        $wpdb->delete( $wpdb->prefix . 'rp_order_items', [ 'order_id' => $order_id ], [ '%d' ] );
        foreach ( $calc['items'] as $item ) {
            $wpdb->insert(
                $wpdb->prefix . 'rp_order_items',
                [
                    'order_id'     => $order_id,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'item_name'    => $item['item_name'],
                    'notes'        => $item['notes'],
                ],
                [ '%d', '%d', '%d', '%f', '%s', '%s' ]
            );
        }
    }
}
