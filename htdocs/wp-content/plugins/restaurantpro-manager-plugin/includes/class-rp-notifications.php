<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Notifications {
    public static function table(): string { global $wpdb; return $wpdb->prefix . 'rp_notifications'; }
    public static function install(): void {
        global $wpdb;
        if ( get_option( 'rp_notifications_db_version' ) === '1' && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::table() ) ) === self::table() ) return;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(60) NOT NULL,
            title varchar(190) NOT NULL,
            message text NOT NULL,
            order_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id), KEY event_type (event_type), KEY order_id (order_id), KEY created_at (created_at)
        ) " . $wpdb->get_charset_collate() . ";";
        dbDelta( $sql );
        update_option( 'rp_notifications_db_version', '1', false );
    }
    public static function add( string $event_type, string $title, string $message, int $order_id = 0 ): int {
        global $wpdb;
        self::install();
        $ok = $wpdb->insert( self::table(), [
            'event_type' => sanitize_key( $event_type ),
            'title' => sanitize_text_field( $title ),
            'message' => sanitize_textarea_field( $message ),
            'order_id' => absint( $order_id ),
            'user_id' => get_current_user_id(),
            'created_at' => current_time( 'mysql' ),
        ], [ '%s','%s','%s','%d','%d','%s' ] );
        return $ok ? (int) $wpdb->insert_id : 0;
    }
    public static function latest( int $after_id = 0, int $limit = 30 ): array {
        global $wpdb;
        self::install();
        $limit = max( 1, min( 50, $limit ) );
        if ( $after_id > 0 ) {
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,event_type,title,message,order_id,created_at FROM " . self::table() . " WHERE id > %d ORDER BY id ASC LIMIT %d", $after_id, $limit ), ARRAY_A );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,event_type,title,message,order_id,created_at FROM " . self::table() . " ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A );
            $rows = array_reverse( $rows ?: [] );
        }
        return array_map( static function( $row ) { return [
            'id'=>(int)$row['id'], 'event_type'=>(string)$row['event_type'], 'title'=>(string)$row['title'],
            'message'=>(string)$row['message'], 'order_id'=>(int)$row['order_id'], 'created_at'=>(string)$row['created_at'],
        ]; }, $rows ?: [] );
    }
    public static function newest_id(): int { global $wpdb; self::install(); return (int)$wpdb->get_var( 'SELECT MAX(id) FROM ' . self::table() ); }
}
