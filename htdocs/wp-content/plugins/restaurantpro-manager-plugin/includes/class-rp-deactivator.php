<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Deactivator {

    public static function deactivate(): void {
        RP_Roles::remove();
        flush_rewrite_rules();
    }
}
