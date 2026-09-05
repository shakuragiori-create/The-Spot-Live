
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class RP_Backup {
    public static function init(): void { add_action('init',[__CLASS__,'maybe_daily']); }
    public static function create_tables(): void {
        global $wpdb; $charset=$wpdb->get_charset_collate();
        $sql="CREATE TABLE {$wpdb->prefix}rp_cash_shifts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,user_id bigint(20) unsigned NOT NULL DEFAULT 0,shift_date date NOT NULL,
            opening_cash decimal(12,2) NOT NULL DEFAULT 0,expected_cash decimal(12,2) NOT NULL DEFAULT 0,actual_cash decimal(12,2) NOT NULL DEFAULT 0,
            difference decimal(12,2) NOT NULL DEFAULT 0,status varchar(20) NOT NULL DEFAULT 'open',opened_at datetime DEFAULT NULL,closed_at datetime DEFAULT NULL,notes text,
            PRIMARY KEY(id),KEY shift_date(shift_date),KEY status(status)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}rp_inventory_items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,name varchar(200) NOT NULL,sku varchar(100) NOT NULL DEFAULT '',unit varchar(30) NOT NULL DEFAULT 'pcs',
            stock_qty decimal(12,3) NOT NULL DEFAULT 0,reorder_level decimal(12,3) NOT NULL DEFAULT 0,cost_price decimal(12,2) NOT NULL DEFAULT 0,sell_price decimal(12,2) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'active',created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),KEY sku(sku),KEY status(status)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}rp_inventory_movements (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,item_id bigint(20) unsigned NOT NULL,type varchar(20) NOT NULL DEFAULT 'in',qty decimal(12,3) NOT NULL DEFAULT 0,
            reference varchar(100) NOT NULL DEFAULT '',notes varchar(255) NOT NULL DEFAULT '',created_by bigint(20) unsigned NOT NULL DEFAULT 0,created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),KEY item_id(item_id),KEY created_at(created_at)
        ) $charset;
        CREATE TABLE {$wpdb->prefix}rp_expenses (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,expense_date date NOT NULL,category varchar(100) NOT NULL DEFAULT 'Other',description varchar(255) NOT NULL DEFAULT '',amount decimal(12,2) NOT NULL DEFAULT 0,
            payment_method varchar(30) NOT NULL DEFAULT 'cash',reference varchar(100) NOT NULL DEFAULT '',created_by bigint(20) unsigned NOT NULL DEFAULT 0,created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),KEY expense_date(expense_date),KEY category(category)
        ) $charset;";
        require_once ABSPATH.'wp-admin/includes/upgrade.php'; dbDelta($sql);
    }
    public static function maybe_daily(): void {
        if ( get_option('rp_backup_last_date') === current_time('Y-m-d') ) return;
        if ( ! function_exists('wp_mkdir_p') ) require_once ABSPATH.'wp-admin/includes/file.php';
        $u=wp_upload_dir(); $dir=trailingslashit($u['basedir']).'restaurantpro-backups/'; wp_mkdir_p($dir);
        $file=$dir.'backup-'.current_time('Y-m-d-H-i-s').'.sql'; $sql=self::dump_sql();
        if ( $sql ) { file_put_contents($file,$sql); update_option('rp_backup_last_date',current_time('Y-m-d'),false); self::cleanup($dir); }
    }
    public static function dump_sql(): string {
        global $wpdb; $tables=$wpdb->get_col('SHOW TABLES'); $out="-- The Spot RestaurantPro backup
-- ".current_time('mysql')."
SET FOREIGN_KEY_CHECKS=0;
";
        foreach($tables as $table){ if(strpos($table,$wpdb->prefix)===false) continue; $create=$wpdb->get_row("SHOW CREATE TABLE `$table`",ARRAY_N); if(!$create)continue; $out.="
DROP TABLE IF EXISTS `$table`;
".$create[1].";
"; $rows=$wpdb->get_results("SELECT * FROM `$table`",ARRAY_A); foreach($rows as $row){$vals=[];foreach($row as $v)$vals[]=$v===null?'NULL':"'".esc_sql((string)$v)."'";$out.="INSERT INTO `$table` (`".implode('`,`',array_keys($row))."`) VALUES (".implode(',',$vals).");
";}}
        return $out."
SET FOREIGN_KEY_CHECKS=1;
";
    }
    private static function cleanup(string $dir): void { $files=glob($dir.'backup-*.sql')?:[]; rsort($files); foreach(array_slice($files,14) as $f)@unlink($f); }
    public static function download_latest(): void { if(!current_user_can('manage_options'))wp_die('Access denied.'); $sql=self::dump_sql(); nocache_headers(); header('Content-Type: application/sql'); header('Content-Disposition: attachment; filename="the-spot-backup-'.current_time('Y-m-d-H-i-s').'.sql"'); echo $sql; exit; }
}
