<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Database {

    public static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$wpdb->prefix}rp_orders (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            table_number int unsigned NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'new',
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            discount_type varchar(10) NOT NULL DEFAULT 'none',
            discount_value decimal(10,2) NOT NULL DEFAULT 0.00,
            discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            vat_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            vat_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            service_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            service_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            amount_paid decimal(10,2) NOT NULL DEFAULT 0.00,
            change_due decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(20) NOT NULL DEFAULT 'cash',
            paid_at datetime DEFAULT NULL,
            invoice_no varchar(32) NOT NULL DEFAULT '',
            customer_name varchar(200) NOT NULL DEFAULT '',
            customer_phone varchar(50) NOT NULL DEFAULT '',
            order_type varchar(20) NOT NULL DEFAULT 'dine_in',
            notes text,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at),
            KEY invoice_no (invoice_no)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}rp_order_items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            menu_item_id bigint(20) unsigned NOT NULL,
            quantity int unsigned NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            notes varchar(255) DEFAULT '',
            item_name varchar(200) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY order_id (order_id)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}rp_tables (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            table_name varchar(100) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'available',
            capacity int unsigned NOT NULL DEFAULT 4,
            PRIMARY KEY  (id)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}rp_kot (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}rp_reservations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            guest_name varchar(200) NOT NULL,
            phone varchar(50) DEFAULT '',
            email varchar(200) DEFAULT '',
            reservation_date date NOT NULL,
            reservation_time time NOT NULL,
            guests int unsigned NOT NULL DEFAULT 2,
            status varchar(20) NOT NULL DEFAULT 'pending',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY reservation_date (reservation_date)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}rp_settings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            option_key varchar(191) NOT NULL,
            option_value longtext,
            PRIMARY KEY  (id),
            UNIQUE KEY option_key (option_key)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}rp_messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL DEFAULT '',
            phone varchar(50) NOT NULL DEFAULT '',
            email varchar(200) NOT NULL DEFAULT '',
            message text,
            status varchar(20) NOT NULL DEFAULT 'unread',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}rp_internal_messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) unsigned NOT NULL DEFAULT 0,
            recipient_id bigint(20) unsigned NOT NULL DEFAULT 0,
            subject varchar(200) NOT NULL DEFAULT '',
            message text NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY recipient_id (recipient_id),
            KEY sender_id (sender_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset;";

        $sql .= "CREATE TABLE {$wpdb->prefix}rp_staff_permissions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            page_slug varchar(50) NOT NULL,
            allowed tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY user_page (user_id, page_slug),
            KEY user_id (user_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        if ( class_exists( 'RP_Accounts' ) ) { RP_Accounts::create_tables(); }
        if ( class_exists( 'RP_Activity' ) ) { RP_Activity::create_table(); }
        if ( class_exists( 'RP_Backup' ) ) { RP_Backup::create_tables(); }

    }

    public static function drop_tables(): void {
        global $wpdb;
        $tables = [
            'rp_orders',
            'rp_order_items',
            'rp_tables',
            'rp_kot',
            'rp_reservations',
            'rp_settings',
            'rp_messages',
            'rp_internal_messages',
            'rp_ledger_entries',
            'rp_ledger_accounts',
            'rp_salary_records',
            'rp_staff_leave',
            'rp_activity_log',
            'rp_cash_shifts',
            'rp_inventory_movements',
            'rp_inventory_items',
            'rp_expenses',
            'rp_staff_permissions',
        ];
        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
        }
    }
}
