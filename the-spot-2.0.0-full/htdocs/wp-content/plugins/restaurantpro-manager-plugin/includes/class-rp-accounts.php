<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Accounts {
    public static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$wpdb->prefix}rp_ledger_accounts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            account_type varchar(20) NOT NULL DEFAULT 'customer',
            name varchar(200) NOT NULL,
            phone varchar(50) NOT NULL DEFAULT '',
            email varchar(200) NOT NULL DEFAULT '',
            address varchar(255) NOT NULL DEFAULT '',
            opening_balance decimal(12,2) NOT NULL DEFAULT 0.00,
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY account_type (account_type),
            KEY name (name)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}rp_ledger_entries (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            account_id bigint(20) unsigned NOT NULL,
            entry_date date NOT NULL,
            entry_type varchar(30) NOT NULL DEFAULT 'general',
            reference varchar(100) NOT NULL DEFAULT '',
            description varchar(255) NOT NULL DEFAULT '',
            debit decimal(12,2) NOT NULL DEFAULT 0.00,
            credit decimal(12,2) NOT NULL DEFAULT 0.00,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY entry_date (entry_date)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}rp_staff_leave (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            staff_user_id bigint(20) unsigned NOT NULL,
            leave_from date NOT NULL,
            leave_to date NOT NULL,
            leave_type varchar(30) NOT NULL DEFAULT 'casual',
            status varchar(20) NOT NULL DEFAULT 'approved',
            reason varchar(255) NOT NULL DEFAULT '',
            notes text,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY staff_user_id (staff_user_id),
            KEY leave_from (leave_from),
            KEY status (status)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}rp_salary_records (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            staff_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            staff_name varchar(200) NOT NULL,
            salary_month char(7) NOT NULL,
            base_salary decimal(12,2) NOT NULL DEFAULT 0.00,
            bonus decimal(12,2) NOT NULL DEFAULT 0.00,
            deduction decimal(12,2) NOT NULL DEFAULT 0.00,
            net_salary decimal(12,2) NOT NULL DEFAULT 0.00,
            amount_paid decimal(12,2) NOT NULL DEFAULT 0.00,
            due_amount decimal(12,2) NOT NULL DEFAULT 0.00,
            status varchar(20) NOT NULL DEFAULT 'due',
            paid_at datetime DEFAULT NULL,
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY salary_month (salary_month),
            KEY status (status)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function is_paid( $order ): bool {
        $total = (float) ( $order->total ?? 0 );
        $paid  = (float) ( $order->amount_paid ?? 0 );
        return ! empty( $order->paid_at ) || ( $total > 0 && $paid + 0.009 >= $total );
    }
}
