<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RP_Panel {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'add_rewrite_rules' ] );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_action( 'template_redirect', [ $this, 'handle_request' ] );
        // Also recognize panel URLs directly so new panel pages work without visiting WordPress Permalinks.
        add_action( 'parse_request', [ $this, 'detect_direct_panel_path' ] );
    }

    public function add_rewrite_rules(): void {
        add_rewrite_rule( '^panel/?$', 'index.php?rp_panel=dashboard', 'top' );
        add_rewrite_rule( '^panel/login/?$', 'index.php?rp_panel=login', 'top' );
        add_rewrite_rule( '^panel/logout/?$', 'index.php?rp_panel=logout', 'top' );
        add_rewrite_rule( '^panel/messages/?$', 'index.php?rp_panel=messages', 'top' );
        add_rewrite_rule( '^panel/orders/?$', 'index.php?rp_panel=orders', 'top' );
        add_rewrite_rule( '^panel/orders/new/?$', 'index.php?rp_panel=order-new', 'top' );
        add_rewrite_rule( '^panel/orders/([0-9]+)/?$', 'index.php?rp_panel=order-view&rp_order_id=$matches[1]', 'top' );
        add_rewrite_rule( '^panel/bill/([0-9]+)/?$', 'index.php?rp_panel=bill&rp_order_id=$matches[1]', 'top' );
        add_rewrite_rule( '^panel/bill/([0-9]+)/pdf/?$', 'index.php?rp_panel=bill-pdf&rp_order_id=$matches[1]', 'top' );
        add_rewrite_rule( '^panel/kitchen/?$', 'index.php?rp_panel=kitchen', 'top' );
        add_rewrite_rule( '^panel/tables/?$', 'index.php?rp_panel=tables', 'top' );
        add_rewrite_rule( '^panel/reservations/?$', 'index.php?rp_panel=reservations', 'top' );
        add_rewrite_rule( '^panel/pos/?$', 'index.php?rp_panel=pos', 'top' );
        add_rewrite_rule( '^panel/reports/?$', 'index.php?rp_panel=reports', 'top' );
        add_rewrite_rule( '^panel/accounts/?$', 'index.php?rp_panel=accounts', 'top' );
        add_rewrite_rule( '^panel/menu/?$', 'index.php?rp_panel=menu', 'top' );
        add_rewrite_rule( '^panel/users/?$', 'index.php?rp_panel=users', 'top' );
        add_rewrite_rule( '^panel/settings/?$', 'index.php?rp_panel=settings', 'top' );
        add_rewrite_rule( '^panel/gallery/?$', 'index.php?rp_panel=gallery', 'top' );
        add_rewrite_rule( '^panel/inventory/?$', 'index.php?rp_panel=inventory', 'top' );
        add_rewrite_rule( '^panel/expenses/?$', 'index.php?rp_panel=expenses', 'top' );
        add_rewrite_rule( '^panel/shifts/?$', 'index.php?rp_panel=shifts', 'top' );
        add_rewrite_rule( '^panel/activity/?$', 'index.php?rp_panel=activity', 'top' );
        add_rewrite_rule( '^panel/backup/?$', 'index.php?rp_panel=backup', 'top' );
        add_rewrite_rule( '^panel/backup/download/?$', 'index.php?rp_panel=backup-download', 'top' );
        // PWA install support: manifest + service worker must be served from
        // *inside* the /panel/ scope (see header.php) or Chrome on Android will
        // only ever offer "Add shortcut" instead of a real app install.
        add_rewrite_rule( '^panel/pwa-manifest/?$', 'index.php?rp_panel=manifest', 'top' );
        add_rewrite_rule( '^panel/pwa-sw/?$', 'index.php?rp_panel=sw', 'top' );
        add_rewrite_rule( '^panel/pwa-check/?$', 'index.php?rp_panel=pwa-check', 'top' );
    }

    public function add_query_vars( array $vars ): array {
        $vars[] = 'rp_panel';
        $vars[] = 'rp_order_id';
        return $vars;
    }

    /**
     * Detect /panel/gallery even when the site's saved rewrite rules predate this page.
     */
    public function detect_direct_panel_path( $wp ): void {
        $path = trim( (string) parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
        $home_path = trim( (string) parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
        if ( $home_path && str_starts_with( $path, $home_path . '/' ) ) {
            $path = substr( $path, strlen( $home_path ) + 1 );
        }

        // Direct-path fallback: makes the custom panel work even when the hosting
        // provider has stale/missing WordPress rewrite rules.
        $map = [
            'panel' => 'dashboard',
            'panel/login' => 'login',
            'panel/logout' => 'logout',
            'panel/messages' => 'messages',
            'panel/orders' => 'orders',
            'panel/orders/new' => 'order-new',
            'panel/kitchen' => 'kitchen',
            'panel/tables' => 'tables',
            'panel/reservations' => 'reservations',
            'panel/pos' => 'pos',
            'panel/reports' => 'reports',
            'panel/accounts' => 'accounts',
            'panel/menu' => 'menu',
            'panel/users' => 'users',
            'panel/settings' => 'settings',
            'panel/gallery' => 'gallery',
            'panel/inventory' => 'inventory',
            'panel/expenses' => 'expenses',
            'panel/shifts' => 'shifts',
            'panel/activity' => 'activity',
            'panel/backup' => 'backup',
            'panel/backup/download' => 'backup-download',
            'panel/pwa-manifest' => 'manifest',
            'panel/pwa-sw' => 'sw',
            'panel/pwa-check' => 'pwa-check',
        ];
        if ( isset( $map[ $path ] ) ) {
            $wp->query_vars['rp_panel'] = $map[ $path ];
            return;
        }

        if ( preg_match( '#^panel/orders/(\d+)$#', $path, $m ) ) {
            $wp->query_vars['rp_panel'] = 'order-view';
            $wp->query_vars['rp_order_id'] = absint( $m[1] );
            return;
        }
        if ( preg_match( '#^panel/bill/(\d+)/pdf$#', $path, $m ) ) {
            $wp->query_vars['rp_panel'] = 'bill-pdf';
            $wp->query_vars['rp_order_id'] = absint( $m[1] );
            return;
        }
        if ( preg_match( '#^panel/bill/(\d+)$#', $path, $m ) ) {
            $wp->query_vars['rp_panel'] = 'bill';
            $wp->query_vars['rp_order_id'] = absint( $m[1] );
        }
    }

    public function handle_request(): void {
        $page = get_query_var( 'rp_panel' );
        if ( ! $page ) return;

        if ( $page === 'backup-download' ) { RP_Backup::download_latest(); }

        // Manifest + service worker must be reachable without a login redirect,
        // otherwise the browser can never fetch them to evaluate installability.
        if ( $page === 'manifest' ) { $this->render_manifest(); exit; }
        if ( $page === 'sw' ) { $this->render_service_worker(); exit; }
        if ( $page === 'pwa-check' ) { $this->render_pwa_check(); exit; }

        if ( $page === 'login' ) {
            $this->render_login();
            exit;
        }

        if ( $page === 'logout' ) {
            wp_logout();
            wp_redirect( home_url( '/panel/login' ) );
            exit;
        }

        if ( ! is_user_logged_in() ) {
            wp_redirect( home_url( '/panel/login' ) );
            exit;
        }

        $role = $this->get_user_role();
        if ( ! $role ) {
            wp_redirect( home_url( '/' ) );
            exit;
        }

        if ( $page === 'bill' ) {
            $this->render_bill( absint( get_query_var( 'rp_order_id' ) ) );
            exit;
        }
        if ( $page === 'bill-pdf' ) {
            $this->render_bill_pdf( absint( get_query_var( 'rp_order_id' ) ) );
            exit;
        }

        $allowed = $this->get_allowed_pages( $role );
        $view_page = $page;

        if ( ! in_array( $view_page, $allowed, true ) ) {
            $view_page = $allowed[0] ?? 'dashboard';
        }

        $this->render_panel( $view_page, $role );
        exit;
    }

    private function get_user_role(): string {
        $user = wp_get_current_user();
        if ( in_array( 'administrator', $user->roles, true ) || in_array( 'rp_restaurant_admin', $user->roles, true ) ) {
            return 'admin';
        }
        if ( in_array( 'rp_manager', $user->roles, true ) || in_array( 'rp_cashier', $user->roles, true ) ) {
            return 'receptionist';
        }
        if ( in_array( 'rp_kitchen_staff', $user->roles, true ) ) {
            return 'kitchen';
        }
        if ( in_array( 'rp_waiter', $user->roles, true ) ) {
            return 'waiter';
        }
        if ( current_user_can( 'manage_options' ) ) {
            return 'admin';
        }
        return '';
    }

    private function maybe_set_default_staff_name(): void {
        if ( get_option( 'rp_staff_default_name_v1', false ) ) return;
        $users = get_users( [ 'search' => 'John Doe', 'search_columns' => [ 'display_name' ], 'number' => 50 ] );
        foreach ( $users as $u ) {
            if ( trim( (string) $u->display_name ) === 'John Doe' ) {
                wp_update_user( [ 'ID' => $u->ID, 'display_name' => 'Aakash Dhungana' ] );
            }
        }
        update_option( 'rp_staff_default_name_v1', 1, false );
    }

    private function get_allowed_pages( string $role ): array {
        return match ( $role ) {
            'admin' => [ 'dashboard', 'pos', 'orders', 'order-new', 'order-view', 'kitchen', 'tables', 'reservations', 'reports', 'accounts', 'menu', 'gallery', 'users', 'messages', 'settings', 'inventory', 'expenses', 'shifts', 'activity', 'backup' ],
            'receptionist' => [ 'dashboard', 'pos', 'orders', 'order-new', 'order-view', 'tables', 'reservations', 'messages' ],
            'kitchen' => [ 'kitchen', 'messages' ],
            'waiter' => [ 'pos', 'orders', 'order-new', 'order-view', 'tables', 'messages' ],
            default => [],
        };
    }

    public function get_nav_items( string $role ): array {
        $items = [];
        $base = home_url( '/panel' );

        // POS - First and most important!
        $items[] = [ 'url' => "$base/pos", 'icon' => 'dollar-sign', 'label' => 'POS', 'page' => 'pos' ];

        if ( in_array( $role, [ 'admin', 'receptionist' ], true ) ) {
            $items[] = [ 'url' => $base, 'icon' => 'grid', 'label' => 'Dashboard', 'page' => 'dashboard' ];
        }

        if ( in_array( $role, [ 'admin', 'receptionist', 'waiter' ], true ) ) {
            $items[] = [ 'url' => "$base/orders", 'icon' => 'clipboard', 'label' => 'Orders', 'page' => 'orders' ];
        }
        if ( in_array( $role, [ 'admin', 'receptionist', 'kitchen', 'waiter' ], true ) ) {
            $items[] = [ 'url' => "$base/messages", 'icon' => 'message', 'label' => 'Messages', 'page' => 'messages' ];
        }


        if ( in_array( $role, [ 'admin', 'kitchen' ], true ) ) {
            $items[] = [ 'url' => "$base/kitchen", 'icon' => 'flame', 'label' => 'Kitchen', 'page' => 'kitchen' ];
        }

        if ( in_array( $role, [ 'admin', 'receptionist', 'waiter' ], true ) ) {
            $items[] = [ 'url' => "$base/tables", 'icon' => 'layout', 'label' => 'Tables', 'page' => 'tables' ];
        }

        if ( in_array( $role, [ 'admin', 'receptionist' ], true ) ) {
            $items[] = [ 'url' => "$base/reservations", 'icon' => 'calendar', 'label' => 'Reservations', 'page' => 'reservations' ];
        }

        // Accounts - Admin only
        if ( $role === 'admin' ) {
            $items[] = [ 'url' => "$base/accounts", 'icon' => 'bar-chart', 'label' => 'Accounts', 'page' => 'accounts' ];
            $items[] = [ 'url' => "$base/reports", 'icon' => 'grid', 'label' => 'Reports', 'page' => 'reports' ];
            $items[] = [ 'url' => "$base/menu", 'icon' => 'book-open', 'label' => 'Menu', 'page' => 'menu' ];
            $items[] = [ 'url' => "$base/gallery", 'icon' => 'image', 'label' => 'Gallery', 'page' => 'gallery' ];
            $items[] = [ 'url' => "$base/users", 'icon' => 'users', 'label' => 'Staff', 'page' => 'users' ];
            $items[] = [ 'url' => "$base/settings", 'icon' => 'settings', 'label' => 'Settings', 'page' => 'settings' ];
            $items[] = [ 'url' => "$base/inventory", 'icon' => 'box', 'label' => 'Inventory', 'page' => 'inventory' ];
            $items[] = [ 'url' => "$base/expenses", 'icon' => 'receipt', 'label' => 'Expenses', 'page' => 'expenses' ];
            $items[] = [ 'url' => "$base/shifts", 'icon' => 'clock', 'label' => 'Cash Shift', 'page' => 'shifts' ];
            $items[] = [ 'url' => "$base/activity", 'icon' => 'activity', 'label' => 'Activity Log', 'page' => 'activity' ];
            $items[] = [ 'url' => "$base/backup", 'icon' => 'download', 'label' => 'Backup', 'page' => 'backup' ];
        }

        return $items;
    }

    private function pdf_escape( string $text ): string { return str_replace( [ '\\', '(', ')', "\r", "\n" ], [ '\\\\', '\\(', '\\)', ' ', ' ' ], $text ); }

    private function render_bill_pdf( int $order_id ): void {
        if ( ! $order_id ) wp_die( 'Invalid order.' );
        global $wpdb;
        $o = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rp_orders WHERE id=%d", $order_id ) );
        if ( ! $o ) wp_die( 'Order not found.' );
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rp_order_items WHERE order_id=%d ORDER BY id", $order_id ) );
        $lines = [ RP_Settings::get('restaurant_name','The Spot'), 'SALES RECEIPT', 'Bill: '.($o->invoice_no ?: RP_Billing::invoice_no($o->id)), 'Order #'.$o->id.'  '.wp_date('d M Y g:i A',strtotime($o->created_at)), str_repeat('-',42) ];
        foreach ( $items as $it ) $lines[] = substr((string)$it->item_name,0,24).'  x'.$it->quantity.'  Rs.'.number_format((float)$it->price*(int)$it->quantity,2);
        $lines[] = str_repeat('-',42); $lines[]='TOTAL: Rs.'.number_format((float)$o->total,2); $lines[]='Paid: Rs.'.number_format((float)$o->amount_paid,2); $lines[]='Payment: '.ucwords(str_replace('_',' ',(string)$o->payment_method)); $lines[]='Thank you!';
        $content="BT /F1 11 Tf 36 806 Td 14 TL "; $first=true;
        foreach($lines as $line){ if(!$first)$content.=" T* "; $content.='('.$this->pdf_escape($line).') Tj'; $first=false; } $content.=' ET';
        $objects=[]; $objects[]='<< /Type /Catalog /Pages 2 0 R >>'; $objects[]='<< /Type /Pages /Kids [3 0 R] /Count 1 >>'; $objects[]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>'; $objects[]='<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream"; $objects[]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $pdf="%PDF-1.4\n"; $offset=[0]; foreach($objects as $i=>$obj){$offset[] = strlen($pdf);$pdf.=($i+1)." 0 obj\n$obj\nendobj\n";} $xref=strlen($pdf); $pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n"; for($i=1;$i<=count($objects);$i++)$pdf.=sprintf("%010d 00000 n \n",$offset[$i]); $pdf.="trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        nocache_headers(); header('Content-Type: application/pdf'); header('Content-Disposition: inline; filename="'.sanitize_file_name(($o->invoice_no?:'bill-'.$o->id).'.pdf').'"'); header('Content-Length: '.strlen($pdf)); echo $pdf;
    }

    /**
     * Serves the PWA manifest from the extensionless /panel/pwa-manifest endpoint so it matches the
     * <link rel="manifest"> URL in header.php and the "scope"/"start_url"
     * declared inside the manifest itself. Previously this only existed as a
     * static /manifest.json at the site root, which the browser could never
     * find (404), so Android Chrome fell back to a plain bookmark shortcut
     * instead of a real "Install app" prompt.
     */
    private function render_manifest(): void {
        // Defensive: some other active plugin/theme code can echo stray
        // whitespace or a PHP notice before this runs, which would corrupt
        // the JSON and make Chrome's manifest parser silently reject the
        // site as installable (falling back to a plain shortcut) with no
        // visible error to the user. Discard anything already buffered so
        // this response is guaranteed to be pure JSON.
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        nocache_headers();
        status_header( 200 );
        header( 'Content-Type: application/manifest+json; charset=utf-8' );
        echo wp_json_encode( [
            'id'               => '/panel/',
            'name'             => RP_Settings::get( 'restaurant_name', 'The Spot' ) . ' Restaurant System',
            'short_name'       => RP_Settings::get( 'restaurant_name', 'The Spot' ),
            'description'      => 'The Spot restaurant management and POS system',
            'start_url'        => home_url( '/panel/' ),
            'scope'            => home_url( '/panel/' ),
            'display'          => 'standalone',
            'display_override' => [ 'standalone', 'minimal-ui' ],
            'background_color' => '#ffffff',
            'theme_color'      => '#111111',
            'orientation'      => 'portrait-primary',
            'icons'            => [
                [
                    'src'     => content_url( 'themes/the-spot-theme/assets/images/icon-192x192.png' ),
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src'     => content_url( 'themes/the-spot-theme/assets/images/icon-192x192.png' ),
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src'     => content_url( 'themes/the-spot-theme/assets/images/icon-512x512.png' ),
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src'     => content_url( 'themes/the-spot-theme/assets/images/icon-512x512.png' ),
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ] );
    }

    /**
     * Serves the service worker from the extensionless /panel/pwa-sw endpoint. It must be served from a
     * URL inside the scope it controls (/panel/) — the site previously only
     * had a static /sw.js at the domain root, which 404'd for this URL and
     * silently failed registration (see the .catch() in header.php), which
     * on Android is enough by itself to block "Add to Home Screen" from
     * ever becoming a real app install.
     */
    private function render_service_worker(): void {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        nocache_headers();
        status_header( 200 );
        header( 'Content-Type: application/javascript; charset=utf-8' );
        header( 'Service-Worker-Allowed: /panel/' );
        echo "const CACHE_NAME = 'the-spot-panel-v1-7-6';\n\n"
            . "self.addEventListener('install', event => {\n    self.skipWaiting();\n});\n\n"
            . "self.addEventListener('activate', event => {\n    event.waitUntil(self.clients.claim());\n});\n\n"
            . "self.addEventListener('fetch', event => {\n    const url = new URL(event.request.url);\n    if (url.pathname.startsWith('/panel/')) { event.respondWith(fetch(event.request, {cache:'no-store'})); }\n});\n";
    }

    /**
     * Plain-English self-check at /panel/pwa-check — open it on the phone
     * (or desktop) that's failing to install. It fetches the manifest and
     * service worker exactly like the browser would and reports what's
     * actually reachable, so a deployment problem (file not uploaded,
     * permalinks not flushed, host injecting extra content) is visible
     * immediately instead of guessing from a vague "Create shortcut" menu.
     */
    private function render_pwa_check(): void {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        nocache_headers();
        status_header( 200 );
        header( 'Content-Type: text/html; charset=utf-8' );

        // The live app uses extensionless /panel/ endpoints. This avoids hosting providers
        // that return HTTP 403 for direct .webmanifest or .js files.
        $manifest_url = home_url( '/panel/pwa-manifest' );
        $sw_url       = home_url( '/panel/pwa-sw' );

        $checks = [];

        $m = wp_remote_get( $manifest_url, [ 'timeout' => 10, 'sslverify' => false ] );
        if ( is_wp_error( $m ) ) {
            $checks[] = [ 'Manifest reachable', false, 'Request failed: ' . $m->get_error_message() ];
        } else {
            $code = wp_remote_retrieve_response_code( $m );
            $body = wp_remote_retrieve_body( $m );
            $ok   = ( 200 === $code );
            $checks[] = [ 'Manifest reachable (HTTP 200)', $ok, "Got HTTP $code from $manifest_url" ];
            if ( $ok ) {
                $decoded = json_decode( $body, true );
                $valid_json = ( null !== $decoded );
                $checks[] = [ 'Manifest is valid JSON', $valid_json, $valid_json ? '' : 'Response body is not pure JSON — something (a plugin, the host, or a stray notice) is adding extra output. First 200 chars: ' . esc_html( substr( $body, 0, 200 ) ) ];
                if ( $valid_json ) {
                    $scope_ok = isset( $decoded['scope'] ) && str_starts_with( $decoded['scope'], home_url( '/panel' ) );
                    $checks[] = [ 'Manifest scope matches /panel/', $scope_ok, $scope_ok ? '' : 'scope was: ' . esc_html( $decoded['scope'] ?? '(missing)' ) ];
                    $has_icons = ! empty( $decoded['icons'] ) && count( $decoded['icons'] ) >= 2;
                    $checks[] = [ 'Manifest lists icons', $has_icons, '' ];
                    foreach ( ( $decoded['icons'] ?? [] ) as $icon ) {
                        $icon_resp = wp_remote_get( $icon['src'], [ 'timeout' => 10, 'sslverify' => false ] );
                        $icon_ok = ! is_wp_error( $icon_resp ) && 200 === wp_remote_retrieve_response_code( $icon_resp );
                        $checks[] = [ 'Icon reachable: ' . esc_html( $icon['sizes'] ?? '' ), $icon_ok, esc_html( $icon['src'] ) ];
                    }
                }
            }
        }

        $sw = wp_remote_get( $sw_url, [ 'timeout' => 10, 'sslverify' => false ] );
        if ( is_wp_error( $sw ) ) {
            $checks[] = [ 'Service worker reachable', false, 'Request failed: ' . $sw->get_error_message() ];
        } else {
            $code = wp_remote_retrieve_response_code( $sw );
            $ctype = wp_remote_retrieve_header( $sw, 'content-type' );
            $ok = ( 200 === $code );
            $checks[] = [ 'Service worker reachable (HTTP 200)', $ok, "Got HTTP $code, Content-Type: " . esc_html( (string) $ctype ) . " from $sw_url" ];
        }

        $checks[] = [ 'Site is HTTPS', is_ssl() || str_starts_with( home_url(), 'https://' ), home_url() ];

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>PWA install check — The Spot</title>';
        echo '<style>body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;max-width:640px;margin:24px auto;padding:0 16px;line-height:1.5}
        .row{padding:12px;border-radius:10px;margin-bottom:8px;display:flex;gap:10px;align-items:flex-start}
        .ok{background:#dcfce7}.bad{background:#fee2e2}
        .mark{font-weight:800}
        .detail{font-size:12px;color:#555;word-break:break-all}
        h1{font-size:20px}</style></head><body>';
        echo '<h1>PWA install check</h1><p>This checks the exact URLs Chrome uses to decide whether "Install app" is offered. If anything below is red, that is why you are seeing "Create shortcut" instead of a real install.</p>';
        foreach ( $checks as $c ) {
            [ $label, $ok, $detail ] = $c;
            echo '<div class="row ' . ( $ok ? 'ok' : 'bad' ) . '"><span class="mark">' . ( $ok ? '✅' : '❌' ) . '</span><div><div>' . esc_html( $label ) . '</div>' . ( $detail ? '<div class="detail">' . $detail . '</div>' : '' ) . '</div></div>';
        }
        echo '</body></html>';
    }

    private function render_bill( int $order_id ): void {
        if ( ! $order_id ) {
            wp_die( 'Invalid order.' );
        }

        $bill_file = RP_PLUGIN_DIR . 'panel/views/bill.php';
        if ( ! file_exists( $bill_file ) ) {
            wp_die( 'Bill view is missing.' );
        }

        include $bill_file;
    }

    private function render_login(): void {
        $error = '';
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['rp_login_nonce'] ) ) {
            if ( wp_verify_nonce( $_POST['rp_login_nonce'], 'rp_panel_login' ) ) {
                $creds = [
                    'user_login'    => sanitize_text_field( $_POST['username'] ?? '' ),
                    'user_password' => $_POST['password'] ?? '',
                    'remember'      => ! empty( $_POST['remember'] ),
                ];
                $user = wp_signon( $creds, is_ssl() );
                if ( ! is_wp_error( $user ) ) {
                    wp_redirect( home_url( '/panel' ) );
                    exit;
                }
                $error = 'Invalid username or password.';
            } else {
                $error = 'Security check failed. Please try again.';
            }
        }

        if ( is_user_logged_in() ) {
            wp_redirect( home_url( '/panel' ) );
            exit;
        }

        include RP_PLUGIN_DIR . 'panel/views/login.php';
    }

    private function render_panel( string $page, string $role ): void {
        if ( $page === 'messages' ) { RP_Database::create_tables(); }
        $view_file = RP_PLUGIN_DIR . "panel/views/{$page}.php";
        if ( ! file_exists( $view_file ) ) {
            $view_file = RP_PLUGIN_DIR . 'panel/views/dashboard.php';
        }
        $nav_items = $this->get_nav_items( $role );
        $current_user = wp_get_current_user();
        $panel_url = home_url( '/panel' );

        // Process form submissions before any output (prevents "headers already sent")
        $this->process_form( $page, $role );

        include RP_PLUGIN_DIR . 'panel/views/partials/header.php';
        include $view_file;
        include RP_PLUGIN_DIR . 'panel/views/partials/footer.php';
    }

    private function process_form( string $page, string $role ): void {
        if ( $role === 'admin' ) { $this->maybe_set_default_staff_name(); }
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;

        global $wpdb;

        if ( isset($_POST['rp_language_save']) ) {
            if ( wp_verify_nonce($_POST['_wpnonce'] ?? '', 'rp_language_save') ) {
                $lang = sanitize_text_field($_POST['rp_language'] ?? 'en');
                if ( ! in_array($lang,['en','ne'],true) ) $lang='en';
                update_user_meta(get_current_user_id(),'rp_panel_language',$lang);
            }
            wp_safe_redirect( wp_get_referer() ?: home_url('/panel') ); exit;
        }

        if ( $page === 'messages' && isset( $_POST['rp_message_action'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_internal_message_action' ) ) wp_die( 'Security check failed.' );
            $action = sanitize_text_field( $_POST['rp_message_action'] );
            $me = get_current_user_id();
            $table = $wpdb->prefix . 'rp_internal_messages';
            if ( $action === 'send' ) {
                $recipient = absint( $_POST['recipient_id'] ?? 0 );
                $subject = sanitize_text_field( $_POST['subject'] ?? '' );
                $message = sanitize_textarea_field( $_POST['message'] ?? '' );
                $recipient_user = $recipient ? get_userdata( $recipient ) : false;
                if ( $recipient && $recipient !== $me && $recipient_user && $message !== '' ) {
                    $wpdb->insert( $table, [ 'sender_id' => $me, 'recipient_id' => $recipient, 'subject' => $subject ?: 'Message', 'message' => $message, 'is_read' => 0 ], [ '%d','%d','%s','%s','%d' ] );
                }
                wp_safe_redirect( home_url( '/panel/messages?sent=1' ) ); exit;
            }
            if ( $action === 'read' ) {
                $id = absint( $_POST['message_id'] ?? 0 );
                if ( $id ) $wpdb->update( $table, [ 'is_read' => 1 ], [ 'id' => $id, 'recipient_id' => $me ], [ '%d' ], [ '%d','%d' ] );
                wp_safe_redirect( home_url( '/panel/messages' ) ); exit;
            }
            if ( $action === 'delete' ) {
                $id = absint( $_POST['message_id'] ?? 0 );
                if ( $id ) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id=%d AND (sender_id=%d OR recipient_id=%d)", $id, $me, $me ) );
                wp_safe_redirect( home_url( '/panel/messages' ) ); exit;
            }
        }

        if ( $page === 'accounts' && $role === 'admin' && isset( $_POST['rp_account_action'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_accounts_action' ) ) wp_die( 'Security check failed.' );
            $action = sanitize_text_field( $_POST['rp_account_action'] );
            if ( $action === 'add_account' ) {
                $name = sanitize_text_field( $_POST['name'] ?? '' );
                $type = sanitize_text_field( $_POST['account_type'] ?? 'customer' );
                if ( ! in_array( $type, [ 'customer','party','staff','other' ], true ) ) $type = 'other';
                if ( $name ) $wpdb->insert( $wpdb->prefix . 'rp_ledger_accounts', [
                    'account_type' => $type, 'name' => $name,
                    'phone' => sanitize_text_field( $_POST['phone'] ?? '' ),
                    'email' => sanitize_email( $_POST['email'] ?? '' ),
                    'address' => sanitize_text_field( $_POST['address'] ?? '' ),
                    'opening_balance' => (float) ( $_POST['opening_balance'] ?? 0 ),
                    'notes' => sanitize_textarea_field( $_POST['notes'] ?? '' ),
                ] );
            } elseif ( $action === 'add_entry' ) {
                $account_id = absint( $_POST['account_id'] ?? 0 );
                if ( $account_id ) $wpdb->insert( $wpdb->prefix . 'rp_ledger_entries', [
                    'account_id' => $account_id,
                    'entry_date' => sanitize_text_field( $_POST['entry_date'] ?? current_time( 'Y-m-d' ) ),
                    'entry_type' => sanitize_text_field( $_POST['entry_type'] ?? 'general' ),
                    'reference' => sanitize_text_field( $_POST['reference'] ?? '' ),
                    'description' => sanitize_text_field( $_POST['description'] ?? '' ),
                    'debit' => max( 0, (float) ( $_POST['debit'] ?? 0 ) ),
                    'credit' => max( 0, (float) ( $_POST['credit'] ?? 0 ) ),
                    'created_by' => get_current_user_id(),
                ] );
            } elseif ( $action === 'add_salary' ) {
                $base = max( 0, (float) ( $_POST['base_salary'] ?? 0 ) );
                $bonus = max( 0, (float) ( $_POST['bonus'] ?? 0 ) );
                $deduction = max( 0, (float) ( $_POST['deduction'] ?? 0 ) );
                $net = max( 0, $base + $bonus - $deduction );
                $paid = min( $net, max( 0, (float) ( $_POST['amount_paid'] ?? 0 ) ) );
                $due = max( 0, $net - $paid );
                $wpdb->insert( $wpdb->prefix . 'rp_salary_records', [
                    'staff_user_id' => absint( $_POST['staff_user_id'] ?? 0 ),
                    'staff_name' => sanitize_text_field( $_POST['staff_name'] ?? '' ),
                    'salary_month' => sanitize_text_field( $_POST['salary_month'] ?? current_time( 'Y-m' ) ),
                    'base_salary' => $base, 'bonus' => $bonus, 'deduction' => $deduction,
                    'net_salary' => $net, 'amount_paid' => $paid, 'due_amount' => $due,
                    'status' => $due <= 0.009 ? 'paid' : ( $paid > 0 ? 'partial' : 'due' ),
                    'paid_at' => $paid > 0 ? current_time( 'mysql' ) : null,
                    'notes' => sanitize_textarea_field( $_POST['notes'] ?? '' ),
                ] );
            }
            wp_redirect( home_url( '/panel/accounts' ) );
            exit;
        }

        if ( $page === 'tables' && isset( $_POST['rp_table_action'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_tables_action' ) ) {
                wp_die( 'Security check failed.' );
            }
            $action = sanitize_text_field( $_POST['rp_table_action'] );

            if ( $action === 'add' && $role === 'admin' ) {
                $name = sanitize_text_field( $_POST['table_name'] ?? '' );
                $capacity = absint( $_POST['capacity'] ?? 4 );
                if ( $name ) {
                    $wpdb->insert( $wpdb->prefix . 'rp_tables', [
                        'table_name' => $name,
                        'capacity' => $capacity,
                        'status' => 'available',
                    ] );
                }
                $id = (int) $wpdb->insert_id;
                if ( $id && class_exists( 'RP_Notifications' ) ) {
                    RP_Notifications::add( 'table', 'Table Added', sprintf( 'Table #%d was added.', $id ) );
                }
            } elseif ( $action === 'delete' && $role === 'admin' ) {
                $id = absint( $_POST['table_id'] ?? 0 );
                if ( $id ) {
                    $wpdb->delete( $wpdb->prefix . 'rp_tables', [ 'id' => $id ] );
                }
            } elseif ( $action === 'status' ) {
                $id = absint( $_POST['table_id'] ?? 0 );
                $status = sanitize_text_field( $_POST['status'] ?? '' );
                if ( $id && in_array( $status, ['available','occupied','reserved'], true ) ) {
                    $wpdb->update( $wpdb->prefix . 'rp_tables', [ 'status' => $status ], [ 'id' => $id ] );
                }
            }
            wp_redirect( home_url( '/panel/tables' ) );
            exit;
        }

        if ( $page === 'reservations' && isset( $_POST['rp_res_action'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_reservations_action' ) ) {
                wp_die( 'Security check failed.' );
            }
            $action = sanitize_text_field( $_POST['rp_res_action'] );
            $id = absint( $_POST['reservation_id'] ?? 0 );
            if ( $id && in_array( $action, ['confirm','cancel'], true ) ) {
                $status = $action === 'confirm' ? 'confirmed' : 'cancelled';
                $wpdb->update( $wpdb->prefix . 'rp_reservations', [ 'status' => $status ], [ 'id' => $id ] );
            }
            wp_redirect( home_url( '/panel/reservations' ) );
            exit;
        }

        if ( $page === 'menu' && isset( $_POST['rp_menu_action'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_menu_action' ) ) {
                wp_die( 'Security check failed.' );
            }
            $action = sanitize_text_field( $_POST['rp_menu_action'] );

            if ( $action === 'add' ) {
                $title = sanitize_text_field( $_POST['item_name'] ?? '' );
                $price = floatval( $_POST['price'] ?? 0 );
                $cat_id = absint( $_POST['category'] ?? 0 );
                $is_veg = ! empty( $_POST['is_veg'] ) ? '1' : '0';
                if ( $title && $price > 0 ) {
                    $post_id = wp_insert_post([
                        'post_type' => 'rp_menu_item',
                        'post_title' => $title,
                        'post_status' => 'publish',
                    ]);
                    if ( $post_id && ! is_wp_error( $post_id ) ) {
                        update_post_meta( $post_id, '_rp_price', $price );
                        update_post_meta( $post_id, '_rp_is_available', '1' );
                        update_post_meta( $post_id, '_rp_is_veg', $is_veg );
                        if ( $cat_id ) {
                            wp_set_post_terms( $post_id, [ $cat_id ], 'rp_menu_category' );
                        }
                    }
                }
            } elseif ( $action === 'toggle' ) {
                $post_id = absint( $_POST['item_id'] ?? 0 );
                $current = get_post_meta( $post_id, '_rp_is_available', true );
                update_post_meta( $post_id, '_rp_is_available', $current === '1' ? '0' : '1' );
            } elseif ( $action === 'delete' ) {
                $post_id = absint( $_POST['item_id'] ?? 0 );
                if ( $post_id ) wp_delete_post( $post_id, true );
            } elseif ( $action === 'add_category' ) {
                $cat_name = sanitize_text_field( $_POST['cat_name'] ?? '' );
                if ( $cat_name ) {
                    wp_insert_term( $cat_name, 'rp_menu_category' );
                }
            } elseif ( $action === 'edit_category' ) {
                $cat_id = absint( $_POST['cat_id'] ?? 0 );
                $cat_name = sanitize_text_field( $_POST['cat_name'] ?? '' );
                if ( $cat_id && $cat_name ) {
                    wp_update_term( $cat_id, 'rp_menu_category', [ 'name' => $cat_name ] );
                }
            } elseif ( $action === 'delete_category' ) {
                $cat_id = absint( $_POST['cat_id'] ?? 0 );
                if ( $cat_id ) {
                    wp_delete_term( $cat_id, 'rp_menu_category' );
                }
            }
            wp_redirect( home_url( '/panel/menu' ) );
            exit;
        }

        if ( $page === 'gallery' && isset( $_POST['rp_gallery_action'] ) && $role === 'admin' ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_gallery_action' ) ) {
                wp_die( 'Security check failed.' );
            }

            $existing = json_decode( (string) RP_Settings::get( 'gallery_images', '[]' ), true );
            $existing = is_array( $existing )
                ? array_values( array_filter( array_map( 'absint', $existing ) ) )
                : [];

            $posted = isset( $_POST['gallery_images'] )
                ? sanitize_text_field( wp_unslash( $_POST['gallery_images'] ) )
                : '';

            $ids = array_values(
                array_filter(
                    array_map(
                        'absint',
                        preg_split( '/[,\s]+/', $posted ) ?: []
                    )
                )
            );

            $ids = array_values( array_unique( $ids ) );

            if ( 'save' === sanitize_text_field( $_POST['rp_gallery_action'] ) ) {
                if ( ! empty( $_FILES['gallery_files']['name'] )
                    && is_array( $_FILES['gallery_files']['name'] )
                ) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';

                    $upload_dir = wp_upload_dir();

                    if ( ! empty( $upload_dir['error'] ) ) {
                        wp_die( 'Upload directory error: ' . esc_html( $upload_dir['error'] ) );
                    }

                    $base_dir = trailingslashit( $upload_dir['basedir'] );
                    $base_url = trailingslashit( $upload_dir['baseurl'] );

                    if ( ! wp_mkdir_p( $base_dir ) ) {
                        wp_die( 'The WordPress uploads directory could not be created.' );
                    }

                    $allowed_extensions = [ 'jpg', 'jpeg', 'png', 'webp', 'gif' ];
                    $allowed_mimes = [
                        'jpg'  => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png'  => 'image/png',
                        'webp' => 'image/webp',
                        'gif'  => 'image/gif',
                    ];

                    $count = count( $_FILES['gallery_files']['name'] );

                    for ( $i = 0; $i < $count && count( $ids ) < 100; $i++ ) {
                        $original_name = sanitize_file_name(
                            $_FILES['gallery_files']['name'][ $i ] ?? ''
                        );

                        if ( '' === $original_name ) continue;

                        $error = (int) (
                            $_FILES['gallery_files']['error'][ $i ]
                            ?? UPLOAD_ERR_NO_FILE
                        );

                        if ( UPLOAD_ERR_OK !== $error ) continue;

                        $tmp_name = (string) (
                            $_FILES['gallery_files']['tmp_name'][ $i ] ?? ''
                        );

                        $size = (int) (
                            $_FILES['gallery_files']['size'][ $i ] ?? 0
                        );

                        if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) continue;
                        if ( $size <= 0 || $size > ( 10 * 1024 * 1024 ) ) continue;

                        $extension = strtolower(
                            pathinfo( $original_name, PATHINFO_EXTENSION )
                        );

                        if ( ! in_array( $extension, $allowed_extensions, true ) ) continue;

                        $image_info = @getimagesize( $tmp_name );
                        if ( false === $image_info ) continue;

                        $mime = '';
                        if ( ! empty( $image_info['mime'] ) ) {
                            $mime = strtolower( (string) $image_info['mime'] );
                        }

                        if ( ! isset( $allowed_mimes[ $extension ] ) ) continue;

                        if ( 'webp' !== $extension
                            && $mime !== $allowed_mimes[ $extension ]
                        ) continue;

                        $date_subdir = '';
                        if ( ! empty( $upload_dir['subdir'] ) ) {
                            $date_subdir = trim(
                                (string) $upload_dir['subdir'],
                                '/'
                            );
                        }

                        $target_dir = $base_dir;

                        if ( $date_subdir ) {
                            $target_dir .= $date_subdir . '/';
                            if ( ! wp_mkdir_p( $target_dir ) ) continue;
                        }

                        $target_name = wp_unique_filename(
                            $target_dir,
                            $original_name
                        );

                        $target_file = $target_dir . $target_name;

                        if ( ! @move_uploaded_file( $tmp_name, $target_file ) ) {
                            if ( ! @copy( $tmp_name, $target_file ) ) {
                                continue;
                            }
                            @unlink( $tmp_name );
                        }

                        if ( ! file_exists( $target_file ) || filesize( $target_file ) <= 0 ) {
                            @unlink( $target_file );
                            continue;
                        }

                        $relative_path = '';
                        if ( $date_subdir ) {
                            $relative_path .= $date_subdir . '/';
                        }

                        $file_url = $base_url . $relative_path . $target_name;

                        $attachment = [
                            'post_mime_type' => $mime ?: $allowed_mimes[ $extension ],
                            'post_title'     => sanitize_text_field(
                                pathinfo(
                                    $target_name,
                                    PATHINFO_FILENAME
                                )
                            ),
                            'post_content'   => '',
                            'post_status'    => 'inherit',
                            'guid'           => esc_url_raw( $file_url ),
                        ];

                        $attachment_id = wp_insert_attachment(
                            $attachment,
                            $target_file
                        );

                        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
                            @unlink( $target_file );
                            continue;
                        }

                        $metadata = wp_generate_attachment_metadata(
                            $attachment_id,
                            $target_file
                        );

                        if ( ! empty( $metadata ) && ! is_wp_error( $metadata ) ) {
                            wp_update_attachment_metadata(
                                $attachment_id,
                                $metadata
                            );
                        }

                        $ids[] = (int) $attachment_id;
                    }
                }

                $ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

                RP_Settings::set(
                    'gallery_images',
                    wp_json_encode( $ids )
                );
            }

            wp_redirect( home_url( '/panel/gallery?saved=1' ) );
            exit;
        }

        if ( $page === 'users' && isset( $_POST['rp_user_action'] ) ) {
            if ( $role !== 'admin' || ! current_user_can( 'rp_manage_staff' ) ) wp_die( 'Access denied.' );
            if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_users_action' ) ) wp_die( 'Security check failed.' );
            $action = sanitize_text_field( $_POST['rp_user_action'] );
            $rp_roles = [ 'rp_restaurant_admin', 'rp_manager', 'rp_cashier', 'rp_kitchen_staff', 'rp_waiter' ];

            if ( $action === 'add' ) {
                $username = sanitize_user( $_POST['username'] ?? '' );
                $display_name = sanitize_text_field( $_POST['display_name'] ?? 'Aakash Dhungana' );
                $password = (string) ( $_POST['password'] ?? '' );
                $user_role = sanitize_text_field( $_POST['role'] ?? '' );
                if ( $username && $password && in_array( $user_role, $rp_roles, true ) ) {
                    $user_id = wp_create_user( $username, $password );
                    if ( ! is_wp_error( $user_id ) ) {
                        $user = new \WP_User( $user_id ); $user->set_role( $user_role );
                        wp_update_user( [ 'ID' => $user_id, 'display_name' => $display_name ?: 'Aakash Dhungana', 'user_email' => sanitize_email( $_POST['email'] ?? '' ) ] );
                        update_user_meta( $user_id, 'rp_phone', sanitize_text_field( $_POST['phone'] ?? '' ) );
                        update_user_meta( $user_id, 'rp_address', sanitize_text_field( $_POST['address'] ?? '' ) );
                        update_user_meta( $user_id, 'rp_joining_date', sanitize_text_field( $_POST['joining_date'] ?? '' ) );
                        update_user_meta( $user_id, 'rp_salary', (float) ( $_POST['salary'] ?? 0 ) );
                        update_user_meta( $user_id, 'rp_designation', sanitize_text_field( $_POST['designation'] ?? '' ) );
                        update_user_meta( $user_id, 'rp_emergency_contact', sanitize_text_field( $_POST['emergency_contact'] ?? '' ) );
                    }
                }
            } elseif ( $action === 'update' ) {
                $uid = absint( $_POST['user_id'] ?? 0 );
                $user = $uid ? get_userdata( $uid ) : false;
                if ( $user ) {
                    $data = [ 'ID' => $uid, 'display_name' => sanitize_text_field( $_POST['display_name'] ?? $user->display_name ), 'user_email' => sanitize_email( $_POST['email'] ?? $user->user_email ) ];
                    wp_update_user( $data );
                    if ( isset( $_POST['role'] ) && in_array( $_POST['role'], $rp_roles, true ) && $uid !== get_current_user_id() ) ( new \WP_User( $uid ) )->set_role( sanitize_text_field( $_POST['role'] ) );
                    foreach ( [ 'phone','address','joining_date','designation','emergency_contact' ] as $meta ) update_user_meta( $uid, 'rp_'.$meta, sanitize_text_field( $_POST[$meta] ?? '' ) );
                    update_user_meta( $uid, 'rp_salary', (float) ( $_POST['salary'] ?? 0 ) );
                    if ( ! empty( $_FILES['profile_picture']['name'] ) && ! empty( $_FILES['profile_picture']['tmp_name'] ) ) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        $upload = wp_handle_upload( $_FILES['profile_picture'], [ 'test_form' => false, 'mimes' => [ 'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp' ] ] );
                        if ( ! isset( $upload['error'] ) && ! empty( $upload['url'] ) ) update_user_meta( $uid, 'rp_profile_picture', esc_url_raw( $upload['url'] ) );
                    }
                    if ( isset( $_POST['remove_profile_picture'] ) ) delete_user_meta( $uid, 'rp_profile_picture' );
                    if ( ! empty( $_POST['new_password'] ) ) {
                        $new_password = (string) $_POST['new_password'];
                        if ( strlen( $new_password ) >= 6 ) wp_set_password( $new_password, $uid );
                    }
                }
            } elseif ( $action === 'leave' ) {
                global $wpdb;
                $uid = absint( $_POST['user_id'] ?? 0 );
                $from = sanitize_text_field( $_POST['leave_from'] ?? '' ); $to = sanitize_text_field( $_POST['leave_to'] ?? '' );
                if ( $uid && $from && $to ) $wpdb->insert( $wpdb->prefix . 'rp_staff_leave', [ 'staff_user_id'=>$uid, 'leave_from'=>$from, 'leave_to'=>$to, 'leave_type'=>sanitize_text_field($_POST['leave_type']??'casual'), 'status'=>sanitize_text_field($_POST['leave_status']??'approved'), 'reason'=>sanitize_text_field($_POST['leave_reason']??''), 'notes'=>sanitize_textarea_field($_POST['leave_notes']??''), 'created_by'=>get_current_user_id() ], [ '%d','%s','%s','%s','%s','%s','%s','%d' ] );
            } elseif ( $action === 'delete' ) {
                $uid = absint( $_POST['user_id'] ?? 0 );
                if ( $uid && $uid !== get_current_user_id() ) {
                    require_once ABSPATH . 'wp-admin/includes/user.php';
                    wp_delete_user( $uid );
                }
            }
            wp_redirect( home_url( '/panel/users?saved=1' ) ); exit;
        }

        if ( $page === 'inventory' && isset($_POST['rp_inventory_action']) && $role === 'admin' ) {
            if(!wp_verify_nonce($_POST['_wpnonce']??'','rp_inventory_action')) wp_die('Security check failed.');
            $a=sanitize_text_field($_POST['rp_inventory_action']); $t=$wpdb->prefix;
            if($a==='add_item') $wpdb->insert($t.'rp_inventory_items',['name'=>sanitize_text_field($_POST['name']??''),'sku'=>sanitize_text_field($_POST['sku']??''),'unit'=>sanitize_text_field($_POST['unit']??'pcs'),'stock_qty'=>(float)($_POST['stock_qty']??0),'reorder_level'=>(float)($_POST['reorder_level']??0),'cost_price'=>(float)($_POST['cost_price']??0),'sell_price'=>(float)($_POST['sell_price']??0)]);
            if($a==='movement'){ $id=absint($_POST['item_id']??0);$qty=max(0,(float)($_POST['qty']??0));$type=in_array($_POST['movement_type']??'in',['in','out','adjust'],true)?$_POST['movement_type']:'in'; if($id&&$qty>0){$item=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t}rp_inventory_items WHERE id=%d",$id));if($item){$new=$type==='in'?(float)$item->stock_qty+$qty:($type==='out'?(float)$item->stock_qty-$qty:$qty);$new=max(0,$new);$wpdb->update($t.'rp_inventory_items',['stock_qty'=>$new],['id'=>$id]);$wpdb->insert($t.'rp_inventory_movements',['item_id'=>$id,'type'=>$type,'qty'=>$qty,'reference'=>sanitize_text_field($_POST['reference']??''),'notes'=>sanitize_text_field($_POST['notes']??''),'created_by'=>get_current_user_id()]);}} }
            RP_Activity::log('inventory_update','Inventory updated.','inventory'); wp_safe_redirect(home_url('/panel/inventory?saved=1')); exit;
        }
        if ( $page === 'expenses' && isset($_POST['rp_expense_action']) && $role === 'admin' ) {
            if(!wp_verify_nonce($_POST['_wpnonce']??'','rp_expense_action')) wp_die('Security check failed.');
            if(sanitize_text_field($_POST['rp_expense_action'])==='add') $wpdb->insert($wpdb->prefix.'rp_expenses',['expense_date'=>sanitize_text_field($_POST['expense_date']??current_time('Y-m-d')),'category'=>sanitize_text_field($_POST['category']??'Other'),'description'=>sanitize_text_field($_POST['description']??''),'amount'=>max(0,(float)($_POST['amount']??0)),'payment_method'=>sanitize_text_field($_POST['payment_method']??'cash'),'reference'=>sanitize_text_field($_POST['reference']??''),'created_by'=>get_current_user_id()]);
            RP_Activity::log('expense_added','Expense recorded.','expense'); wp_safe_redirect(home_url('/panel/expenses?saved=1')); exit;
        }
        if ( $page === 'shifts' && isset($_POST['rp_shift_action']) && $role === 'admin' ) {
            if(!wp_verify_nonce($_POST['_wpnonce']??'','rp_shift_action')) wp_die('Security check failed.'); $a=sanitize_text_field($_POST['rp_shift_action']);$t=$wpdb->prefix.'rp_cash_shifts';
            if($a==='open'){ $wpdb->insert($t,['user_id'=>get_current_user_id(),'shift_date'=>current_time('Y-m-d'),'opening_cash'=>max(0,(float)($_POST['opening_cash']??0)),'opened_at'=>current_time('mysql'),'status'=>'open']); }
            if($a==='close'){ $id=absint($_POST['shift_id']??0);$actual=max(0,(float)($_POST['actual_cash']??0));$sh=$wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d",$id));if($sh){$expected=(float)$sh->opening_cash; $paid=$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM {$wpdb->prefix}rp_orders WHERE paid_at IS NOT NULL AND DATE(paid_at)=%s AND payment_method='cash'",$sh->shift_date));$expected+=(float)$paid;$wpdb->update($t,['expected_cash'=>$expected,'actual_cash'=>$actual,'difference'=>$actual-$expected,'status'=>'closed','closed_at'=>current_time('mysql'),'notes'=>sanitize_textarea_field($_POST['notes']??'')],['id'=>$id]);}}
            RP_Activity::log('shift_update','Cash shift updated.','shift'); wp_safe_redirect(home_url('/panel/shifts')); exit;
        }
        if ( $page === 'backup' && isset($_POST['rp_backup_action']) && $role === 'admin' ) {
            if(!wp_verify_nonce($_POST['_wpnonce']??'','rp_backup_action')) wp_die('Security check failed.'); RP_Backup::maybe_daily(); RP_Activity::log('backup_created','Database backup created.','backup'); wp_safe_redirect(home_url('/panel/backup?saved=1')); exit;
        }
        if ( $page === 'settings' && isset( $_POST['rp_settings_save'] ) ) {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'rp_settings_save' ) ) {
                wp_die( 'Security check failed.' );
            }
            $fields = [ 'restaurant_name', 'restaurant_phone', 'restaurant_address', 'restaurant_email', 'whatsapp_number', 'facebook_url', 'tiktok_url', 'map_url' ];
            foreach ( $fields as $f ) {
                $val = sanitize_text_field( $_POST[ $f ] ?? '' );
                RP_Settings::set( $f, $val );
            }
            wp_redirect( home_url( '/panel/settings?saved=1' ) );
            exit;
        }
    }

    public static function flush_rules(): void {
        $panel = self::instance();
        $panel->add_rewrite_rules();
        flush_rewrite_rules();
    }
}
