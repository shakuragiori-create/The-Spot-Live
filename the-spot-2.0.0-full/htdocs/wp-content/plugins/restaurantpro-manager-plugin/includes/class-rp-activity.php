
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class RP_Activity {
    public static function create_table(): void {
        global $wpdb; $charset=$wpdb->get_charset_collate();
        $sql="CREATE TABLE {$wpdb->prefix}rp_activity_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            action varchar(50) NOT NULL DEFAULT '', object_type varchar(50) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NOT NULL DEFAULT 0, description varchar(255) NOT NULL DEFAULT '',
            ip_address varchar(64) NOT NULL DEFAULT '', created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY user_id(user_id), KEY created_at(created_at), KEY action(action)
        ) $charset;";
        require_once ABSPATH.'wp-admin/includes/upgrade.php'; dbDelta($sql);
    }
    public static function log(string $action,string $description='',string $object_type='',int $object_id=0): void {
        global $wpdb; $wpdb->insert($wpdb->prefix.'rp_activity_log',[
            'user_id'=>get_current_user_id(),'action'=>sanitize_text_field($action),'object_type'=>sanitize_text_field($object_type),
            'object_id'=>$object_id,'description'=>sanitize_text_field($description),'ip_address'=>sanitize_text_field($_SERVER['REMOTE_ADDR']??'')
        ]);
    }
}
