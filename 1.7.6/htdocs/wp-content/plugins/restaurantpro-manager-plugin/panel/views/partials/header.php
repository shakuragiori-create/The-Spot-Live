<?php
if ( ! function_exists( 'rp_panel_icon' ) ) {
    function rp_panel_icon( string $name ): string {
        $icons = [
            'grid' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
            'clipboard' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M16 4h2a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V5a1 1 0 011-1h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>',
            'flame' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 22c4-4 8-7 8-12a8 8 0 00-16 0c0 5 4 8 8 12z"/><path d="M12 22c-2-2-4-4-4-7a4 4 0 018 0c0 3-2 5-4 7z"/></svg>',
            'layout' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
            'calendar' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
            'book-open' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2zM22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
            'users' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
            'settings' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09a1.65 1.65 0 00-1.08-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09a1.65 1.65 0 001.51-1.08 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001.08 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1.08z"/></svg>',
            'log-out' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>',
            'dollar-sign' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>',
            'image' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
            'box' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 16V8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>',
            'receipt' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2z"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>',
            'clock' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
            'activity' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12h4l2-7 4 14 2-7h6"/></svg>',
            'download' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>',
            'bar-chart' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>',
            'bell' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>',
            'message' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 013.8-4.7 8.5 8.5 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>',
            'check' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
            'search' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>',
            'plus' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
            'trash' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>',
            'edit' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
            'print' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>',
            'wallet' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><circle cx="16" cy="15" r="1"/></svg>',
            'utensils' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7a4 4 0 004 4h0a4 4 0 004-4V2M7 2v20M21 15V2c-2.5 0-5 2-5 5v3a4 4 0 004 4h1v8"/></svg>',
            'chair' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 11V5a2 2 0 012-2h10a2 2 0 012 2v6"/><rect x="3" y="11" width="18" height="4" rx="1"/><path d="M6 15v5M18 15v5M6 20h12"/></svg>',
            'chart-line' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20l5-5 4 4 8-12"/></svg>',
            'file-text' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8M8 9h2"/></svg>',
            'refresh' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>',
            'alert' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
            'star' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
            'camera' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>',
            'send' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>',
            'eye' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
            'package' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4l-9-5.19M21 16V8l-9-5-9 5v8l9 5 9-5z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>',
            'note' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>',
        ];

        return $icons[ $name ] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- The Spot PWA -->
    <link rel="manifest" href="<?php echo esc_url( home_url( '/panel/pwa-manifest' ) ); ?>">
    <link rel="apple-touch-icon" href="<?php echo esc_url( content_url( 'themes/the-spot-theme/assets/images/icon-192x192.png' ) ); ?>">
    <meta name="theme-color" content="#111111">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="The Spot">

    <title><?php echo esc_html( ucfirst( $page ) ); ?> — The Spot Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo esc_url( RP_PLUGIN_URL . 'panel/css/panel.css' ); ?>?v=<?php echo RP_PLUGIN_VERSION; ?>">

</head>

<body class="rp-panel" data-page="<?php echo esc_attr( $page ); ?>">

<!-- Mobile Header -->
<header class="panel-topbar">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>

    <a class="topbar-brand" href="<?php echo esc_url( home_url( '/panel/' ) ); ?>" aria-label="The Spot Dashboard">The Spot</a>

    <form method="post" class="rp-language-form" style="margin-left:auto;margin-right:10px">
        <?php wp_nonce_field('rp_language_save'); ?>
        <input type="hidden" name="rp_language_save" value="1">
        <select name="rp_language" onchange="this.form.submit()" aria-label="Language" style="padding:7px 9px;border:1px solid #ddd;border-radius:8px;background:#fff">
            <option value="en" <?php selected(get_user_meta($current_user->ID,'rp_panel_language',true),'en'); ?>>English</option>
            <option value="ne" <?php selected(get_user_meta($current_user->ID,'rp_panel_language',true),'ne'); ?>>नेपाली</option>
        </select>
    </form>

    <div class="topbar-user">
        <span class="topbar-avatar"><?php echo esc_html( mb_substr( $current_user->display_name, 0, 1 ) ); ?></span>
    </div>
</header>

<!-- Sidebar -->
<aside class="panel-sidebar" id="panelSidebar">
    <div class="sidebar-header">
        <a href="<?php echo esc_url( home_url( '/panel/' ) ); ?>" class="sidebar-logo-link" aria-label="Refresh The Spot Panel"><img src="<?php echo esc_url( RP_PLUGIN_URL . 'admin/images/logo.jpg' ); ?>" alt="The Spot" class="sidebar-logo"></a>
        <span class="sidebar-title">The Spot</span>
        <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">×</button>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ( $nav_items as $item ) : ?>
            <a href="<?php echo esc_url( $item['url'] ); ?>" class="nav-item <?php echo ( $item['page'] === $page || ( $page === 'order-new' && $item['page'] === 'orders' ) || ( $page === 'order-view' && $item['page'] === 'orders' ) ) ? 'active' : ''; ?>">
                <?php echo rp_panel_icon( $item['icon'] ); ?>
                <span><?php echo esc_html( $item['label'] ); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">

        <div class="sidebar-user-info">
            <span class="sidebar-user-avatar"><?php echo esc_html( mb_substr( $current_user->display_name, 0, 1 ) ); ?></span>
            <div>
                <div class="sidebar-user-name"><?php echo esc_html( $current_user->display_name ); ?></div>
                <div class="sidebar-user-role"><?php echo esc_html( ucfirst( $role ) ); ?></div>
            </div>
        </div>

        <button type="button"
                class="nav-item"
                id="rpInstallApp"
                style="display:none;width:100%;border:0;background:transparent;text-align:left;cursor:pointer">
            <?php echo rp_panel_icon( 'download' ); ?> <span>Install App</span>
        </button>

        <a href="<?php echo esc_url( home_url( '/panel/logout' ) ); ?>" class="nav-item logout">
            <?php echo rp_panel_icon( 'log-out' ); ?>
            <span>Logout</span>
        </a>

    </div>
</aside>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- POS Notifications -->
<div class="rp-notification-wrap">

    <button type="button"
            class="rp-notification-bell"
            id="rpNotificationBell"
            aria-label="Notifications">

        <?php echo rp_panel_icon( 'bell' ); ?>

        <span class="rp-notification-badge"
              id="rpNotificationBadge"
              style="display:none">0</span>

    </button>

    <div class="rp-notification-panel" id="rpNotificationPanel">

        <div class="rp-notification-head">
            <strong>POS Notifications</strong>
            <span>LIVE</span>
        </div>

        <div class="rp-notification-list" id="rpNotificationList">
            <div class="rp-notification-empty">
                Waiting for POS updates…
            </div>
        </div>

    </div>

</div>

<audio id="rpNotificationSound"
       preload="auto"
       src="<?php echo esc_url( RP_PLUGIN_URL . 'panel/assests/sounds/notification.mp3' ); ?>"></audio>

<script>
window.RPNotifications = {
    ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
    nonce: <?php echo wp_json_encode(wp_create_nonce('rp_admin_nonce')); ?>,
    panelUrl: <?php echo wp_json_encode(home_url('/panel')); ?>
};
</script>

<style>
/* Fix notification bell position */
.rp-notification-wrap {
    position: fixed !important;
    top: 18px !important;
    right: 22px !important;
    left: auto !important;
    bottom: auto !important;
    z-index: 999999 !important;
    display: none;
}

.rp-notification-wrap.has-unread {
    display: block !important;
}

.rp-notification-bell {
    position: relative !important;
    margin: 0 !important;
}

.rp-notification-bell {
    width:50px;
    height:50px;
    border:2px solid #dc2626;
    border-radius:50%;
    background:#ef4444;
    color:#fff;
    box-shadow:0 0 0 0 rgba(239,68,68,.7),0 8px 22px rgba(239,68,68,.35);
    cursor:pointer;
    position:relative;
    font-size:22px;
    display:flex;
    align-items:center;
    justify-content:center;
    animation:rpBellPop 1s infinite;
    transform-origin:center
}

.rp-notification-bell::after {
    content:'';
    position:absolute;
    inset:-6px;
    border:3px solid rgba(239,68,68,.45);
    border-radius:50%;
    animation:rpBellRing 1s infinite
}

.rp-notification-bell.is-read {
    animation:none;
    background:#fff;
    color:#ef4444;
    border-color:#ef4444;
    box-shadow:0 5px 18px rgba(0,0,0,.12)
}

.rp-notification-bell.is-read::after {
    display:none
}

.rp-notification-badge {
    position:absolute;
    top:-5px;
    right:-5px;
    min-width:21px;
    height:21px;
    padding:0 5px;
    border-radius:20px;
    background:#991b1b;
    color:#fff;
    font-size:11px;
    font-weight:800;
    align-items:center;
    justify-content:center;
    border:2px solid #fff;
    z-index:2
}

.rp-notification-panel {
    display:none;
    position:absolute;
    right:0;
    top:58px;
    width:360px;
    max-width:calc(100vw - 30px);
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    box-shadow:0 18px 45px rgba(0,0,0,.18);
    overflow:hidden
}

.rp-notification-panel.open {
    display:block
}

.rp-notification-head {
    padding:14px 16px;
    border-bottom:1px solid #eee;
    display:flex;
    justify-content:space-between
}

.rp-notification-head span {
    font-size:11px;
    color:#16a34a;
    background:#dcfce7;
    padding:3px 7px;
    border-radius:20px;
    font-weight:700
}

.rp-notification-list {
    max-height:420px;
    overflow:auto
}

.rp-notification-empty {
    text-align:center;
    padding:30px 18px;
    color:#888;
    font-size:13px
}

.rp-notification-item {
    display:flex;
    gap:10px;
    padding:12px 14px;
    border-bottom:1px solid #f1f1f1
}

.rp-notification-icon {
    font-size:19px
}

.rp-notification-copy {
    font-size:12px;
    line-height:1.4;
    flex:1
}

.rp-notification-copy strong {
    display:block;
    font-size:13px
}

.rp-notification-foot {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-top:4px;
    flex-wrap:wrap
}

.rp-notification-copy small {
    display:inline-block;
    color:#999;
    margin:0
}

.rp-notification-copy a {
    font-size:11px;
    color:#2563eb;
    text-decoration:none;
    white-space:nowrap
}

@keyframes rpBellPop {
    0%,100% { transform:scale(1) }
    50% { transform:scale(1.16) }
}

@keyframes rpBellRing {
    0% { transform:scale(.85);opacity:.9 }
    70%,100% { transform:scale(1.35);opacity:0 }
}

@media(max-width:700px) {
    .rp-notification-wrap {
        top:10px;
        right:12px
    }

    .rp-notification-panel {
        width:calc(100vw - 24px)
    }
}

/* Spot 1.7.2 order synchronization visibility */
.rp-pay-order {
    position:relative;
    border:2px solid transparent!important;
    margin:5px 0;
    border-radius:10px;
    overflow:hidden
}

.rp-pay-order.is-unpaid {
    border-color:#ef4444!important;
    animation:rpOrderRed 1.4s infinite
}

.rp-pay-order.is-paid {
    border-color:#22c55e!important;
    animation:rpOrderGreen 1.8s infinite
}

@keyframes rpOrderRed {
    0%,100% { box-shadow:0 0 0 0 rgba(239,68,68,.18) }
    50% { box-shadow:0 0 0 4px rgba(239,68,68,.08) }
}

@keyframes rpOrderGreen {
    0%,100% { box-shadow:0 0 0 0 rgba(34,197,94,.14) }
    50% { box-shadow:0 0 0 4px rgba(34,197,94,.07) }
}
</style>

<style>
.rp-pay-order {
    position:relative;
    border:2px solid transparent!important;
    margin:5px 0;
    border-radius:10px;
    overflow:hidden
}

.rp-pay-order.is-unpaid {
    border-color:#ef4444!important;
    animation:rpOrderRed 1.4s infinite
}

.rp-pay-order.is-paid {
    border-color:#22c55e!important;
    animation:rpOrderGreen 1.8s infinite
}

@keyframes rpOrderRed {
    0%,100% { box-shadow:0 0 0 0 rgba(239,68,68,.10) }
    50% { box-shadow:0 0 0 5px rgba(239,68,68,.24) }
}

@keyframes rpOrderGreen {
    0%,100% { box-shadow:0 0 0 0 rgba(34,197,94,.08) }
    50% { box-shadow:0 0 0 5px rgba(34,197,94,.18) }
}
</style>

<!-- The Spot PWA -->
<script>
(function () {

    /* Service Worker */
    if ('serviceWorker' in navigator) {

        window.addEventListener('load', function () {

            var swUrl = <?php echo wp_json_encode( home_url( '/panel/pwa-sw' ) ); ?>;
            var panelUrl = <?php echo wp_json_encode( home_url( '/panel/' ) ); ?>;

            navigator.serviceWorker.register(swUrl + '?v=1.7.6', {
                scope: panelUrl
            }).then(function (registration) {

                console.log(
                    'The Spot PWA registered:',
                    registration.scope
                );

            }).catch(function (error) {

                console.error(
                    'The Spot PWA registration failed:',
                    error
                );

            });

        });

    }

    /* Install button */
    var promptEvent = null;

    window.addEventListener('beforeinstallprompt', function (event) {

        event.preventDefault();

        promptEvent = event;

        var button = document.getElementById('rpInstallApp');

        if (button) {
            button.style.display = 'flex';
        }

    });

    document.addEventListener('click', function (event) {

        var button = event.target.closest &&
                     event.target.closest('#rpInstallApp');

        if (!button || !promptEvent) {
            return;
        }

        promptEvent.prompt();

        promptEvent.userChoice.then(function () {

            promptEvent = null;

            button.style.display = 'none';

        }).catch(function () {

            promptEvent = null;

        });

    });

    window.addEventListener('appinstalled', function () {

        var button = document.getElementById('rpInstallApp');

        if (button) {
            button.style.display = 'none';
        }

    });

})();
</script>

<script>
window.RPLanguage=<?php echo wp_json_encode(get_user_meta($current_user->ID,'rp_panel_language',true) ?: 'en'); ?>;

(function(){

    var lang=window.RPLanguage||'en';

    if(lang==='en') return;

    var d={
        /* ── Sidebar navigation ── */
        'POS':'POS',
        'Dashboard':'ड्यासबोर्ड',
        'Orders':'अर्डरहरू',
        'Kitchen':'किचेन',
        'Tables':'टेबलहरू',
        'Reservations':'आरक्षण',
        'Accounts':'खाता',
        'Reports':'रिपोर्टहरू',
        'Menu':'मेनु',
        'Gallery':'ग्यालरी',
        'Staff':'कर्मचारी',
        'Settings':'सेटिङहरू',
        'Inventory':'स्टक व्यवस्थापन',
        'Expenses':'खर्च',
        'Cash Shift':'क्यास सिफ्ट',
        'Activity Log':'गतिविधि लग',
        'Backup':'ब्याकअप',
        'Logout':'लगआउट',
        'Install App':'एप इन्स्टल गर्नुहोस्',
        'Messages':'सन्देशहरू',
        'English':'अंग्रेजी',
        'नेपाली':'नेपाली',

        /* ── Dashboard ── */
        'Orders Today':'आजका अर्डरहरू',
        'Revenue Today':'आजको आम्दानी',
        'Pending KOT':'बाँकी KOT',
        'Tables Occupied':'टेबल प्रयोगमा',
        'Orders by Hour':'घण्टा अनुसार अर्डर',
        'Recent Orders':'हालका अर्डरहरू',
        'View All':'सबै हेर्नुहोस्',
        'No orders yet today':'आज अहिलेसम्म अर्डर छैन',
        'System':'सिस्टम',
        'ago':'अगाडि',

        /* ── Orders list ── */
        'Today':'आज',
        'All dates':'सबै मिति',
        'Loading…':'लोड हुँदैछ…',
        'All':'सबै',
        'New':'नयाँ',
        'Accepted':'स्वीकृत',
        'Preparing':'तयारी हुँदै',
        'Ready':'तयार',
        'Done':'सम्पन्न',
        'Completed':'सम्पन्न',
        'Cancelled':'रद्द',
        'No orders found for this date':'यो मितिमा अर्डर भेटिएन',
        'New Order':'नयाँ अर्डर',
        'Go':'जानुहोस्',
        'PAID':'भुक्तानी भएको',
        'NOT PAID':'भुक्तानी बाँकी',
        'Walk-in Customer':'सिधा ग्राहक',

        /* ── Order new ── */
        'QUICK ORDER':'द्रुत अर्डर',
        'Tap items to add them — designed for fast phone ordering.':'वस्तुहरू थप्न ट्याप गर्नुहोस् — फोनबाट छिटो अर्डरका लागि।',
        '← Orders':'← अर्डरहरू',
        'Order details':'अर्डर विवरण',
        'Customer, table and special instructions':'ग्राहक, टेबल र विशेष निर्देशन',
        'Customer':'ग्राहक',
        'Customer name':'ग्राहकको नाम',
        'Phone number':'फोन नम्बर',
        'Table':'टेबल',
        'No Table (Takeaway)':'टेबल छैन (टेकअवे)',
        'Notes':'नोट',
        'Notes:':'नोट:',
        'Special requests...':'विशेष अनुरोध...',
        'Choose items':'वस्तुहरू छान्नुहोस्',
        'available items':'उपलब्ध वस्तुहरू',
        'Search menu...':'मेनुमा खोज्नुहोस्...',
        'Current order':'हालको अर्डर',
        '0 items':'० वस्तु',
        'items':'वस्तुहरू',
        'Create Order':'अर्डर बनाउनुहोस्',

        /* ── Order view ── */
        'Order not found.':'अर्डर भेटिएन।',
        'Print Bill':'बिल प्रिन्ट गर्नुहोस्',
        '🖨 Print Bill':'🖨 बिल प्रिन्ट गर्नुहोस्',
        '← Back':'← पछाडि',
        'Items':'वस्तुहरू',
        'Total':'जम्मा',
        'Takeaway':'टेकअवे',
        'Created By':'बनाउने',
        'Payment Pending':'भुक्तानी बाँकी',
        'Mark this order paid to synchronize POS, Orders and Dashboard.':'POS, अर्डर र ड्यासबोर्डमा मिलाउन यो अर्डर भुक्तानी भएको चिन्ह लगाउनुहोस्।',
        'Mark Paid':'भुक्तानी भएको चिन्ह लगाउनुहोस्',
        'Accept Order':'अर्डर स्वीकार गर्नुहोस्',
        'Start Preparing':'तयारी सुरु गर्नुहोस्',
        'Mark Ready':'तयार चिन्ह लगाउनुहोस्',
        'Complete Order':'अर्डर सम्पन्न गर्नुहोस्',
        'Cancel':'रद्द गर्नुहोस्',

        /* ── Kitchen ── */
        'Kitchen Display':'किचेन डिस्प्ले',
        'Pending':'बाँकी',
        'All caught up! No pending orders.':'सबै सकियो! बाँकी अर्डर छैन।',

        /* ── POS ── */
        'Point of Sale':'बिक्री केन्द्र',
        'Ongoing Orders':'जारी अर्डरहरू',
        'Live POS status':'लाइभ POS स्थिति',
        'No ongoing orders.':'जारी अर्डर छैन।',
        'POS Notifications':'POS सूचनाहरू',
        'Clear View':'हटाउनुहोस्',
        'Waiting for order updates…':'अर्डर अपडेटको प्रतीक्षा...',
        'Visual Bill':'भिजुअल बिल',
        'Search items...':'वस्तु खोज्नुहोस्...',
        'No items found.':'वस्तु भेटिएन।',
        'Current Bill':'हालको बिल',
        'Clear':'खाली गर्नुहोस्',
        'Tap items to add to bill':'बिलमा थप्न वस्तुहरू ट्याप गर्नुहोस्',
        '+ Custom Line':'+ कस्टम लाइन',
        'Order Type':'अर्डर प्रकार',
        'Dine In':'डाइन इन',
        'Takeout':'टेकअवे',
        'Delivery':'डेलिभरी',
        '— None —':'— छैन —',
        'Name':'नाम',
        'Discount':'छुट',
        'None':'छैन',
        'Percent %':'प्रतिशत %',
        'Value':'मान',
        'Service':'सेवा शुल्क',
        'VAT':'भ्याट',
        'Subtotal':'उपजम्मा',
        'GRAND TOTAL':'कुल जम्मा',
        'Payment':'भुक्तानी',
        'Tendered':'तिरेको',
        'Change:':'फिर्ता:',
        'Send kitchen ticket (KOT)':'किचेन टिकट (KOT) पठाउनुहोस्',
        '💰 Charge & Print Bill':'💰 भुक्तानी लिनुहोस् र बिल प्रिन्ट गर्नुहोस्',
        'Send to Kitchen (Unpaid)':'किचेनमा पठाउनुहोस् (भुक्तानी बिना)',

        /* ── Payment methods ── */
        'Cash':'नगद',
        'eSewa':'इसेवा',
        'Khalti':'खल्ती',
        'FonePay / QR':'फोनपे / QR',
        'FonePay/QR':'फोनपे/QR',
        'Card':'कार्ड',
        'Credit (Due)':'उधारो (बाँकी)',
        'Credit':'उधारो',
        'QR':'QR',
        'Payment Method':'भुक्तानी विधि',

        /* ── Tables ── */
        'Add Table':'टेबल थप्नुहोस्',
        'Table Name':'टेबलको नाम',
        'Capacity':'क्षमता',
        'seats':'सिटहरू',
        'Available':'उपलब्ध',
        'Occupied':'प्रयोगमा',
        'Reserved':'आरक्षित',
        'Delete':'मेटाउनुहोस्',
        'Delete this table?':'यो टेबल मेटाउने?',

        /* ── Reservations ── */
        'guests':'जना',
        'Confirm':'पुष्टि गर्नुहोस्',
        'Confirmed':'पुष्टि भएको',
        'No reservations yet':'अहिलेसम्म आरक्षण छैन',

        /* ── Reports ── */
        '📊 Accounts & Reports':'📊 खाता र रिपोर्ट',
        'Accounts & Reports':'खाता र रिपोर्ट',
        '⬇ Download CSV':'⬇ CSV डाउनलोड गर्नुहोस्',
        'Download CSV':'CSV डाउनलोड गर्नुहोस्',
        'All Tables':'सबै टेबलहरू',
        'Show':'देखाउनुहोस्',
        'Yesterday':'हिजो',
        'Last 7 Days':'गत ७ दिन',
        'This Month':'यो महिना',
        'Net Revenue':'खुद आम्दानी',
        'Average Bill':'औसत बिल',
        'Gross Sales':'कुल बिक्री',
        'Discounts':'छुटहरू',
        'Service Charge':'सेवा शुल्क',
        'VAT Collected':'भ्याट सङ्कलन',
        '🍽️ Table-wise Earnings':'🍽️ टेबल अनुसार आम्दानी',
        'Table-wise Earnings':'टेबल अनुसार आम्दानी',
        'Revenue':'आम्दानी',
        'No orders in this period.':'यो अवधिमा अर्डर छैन।',
        '💳 Payment Methods':'💳 भुक्तानी विधिहरू',
        'Payment Methods':'भुक्तानी विधिहरू',
        'Method':'विधि',
        '💵 Cash':'💵 नगद',
        '📱 eSewa':'📱 इसेवा',
        '📱 Khalti':'📱 खल्ती',
        '💳 Card':'💳 कार्ड',
        '📷 QR':'📷 QR',
        'No payments in this period.':'यो अवधिमा भुक्तानी छैन।',
        '⏰ Hourly Breakdown':'⏰ घण्टा अनुसार विवरण',
        'Hourly Breakdown':'घण्टा अनुसार विवरण',
        'Time':'समय',
        'No data.':'डाटा छैन।',
        '📋 Transaction Log':'📋 कारोबार लग',
        'Transaction Log':'कारोबार लग',
        'Invoice':'बिल नं.',
        'Date & Time':'मिति र समय',
        'Date':'मिति',
        'Action':'कार्य',
        'No transactions in this period.':'यो अवधिमा कारोबार छैन।',
        'View Bill':'बिल हेर्नुहोस्',
        '🧾 View Bill':'🧾 बिल हेर्नुहोस्',

        /* ── Accounts & Ledgers ── */
        'Accounts & Ledgers':'खाता र खाता-बही',
        'Customers • Parties • Salary • Other':'ग्राहक • पार्टी • तलब • अन्य',
        'Net Ledger Balance':'खुद खाता-बही शेष',
        'Ledger Accounts':'खाता-बही खाताहरू',
        'Salary Due':'बाँकी तलब',
        'Add Ledger Account':'खाता-बही खाता थप्नुहोस्',
        'Add Ledger Entry':'खाता-बही प्रविष्टि थप्नुहोस्',
        'Salary Record':'तलब रेकर्ड',
        'Opening Balance (Debit + / Credit -)':'शुरु शेष (डेबिट + / क्रेडिट -)',
        'Save Account':'खाता सुरक्षित गर्नुहोस्',
        'Account':'खाता',
        'Select':'छान्नुहोस्',
        'Sale / Receivable':'बिक्री / प्राप्य',
        'Payment Received':'भुक्तानी प्राप्त',
        'Purchase / Payable':'खरिद / भुक्तानीयोग्य',
        'Expense':'खर्च',
        'Advance':'अग्रिम',
        'General':'सामान्य',
        'Invoice / voucher':'बिल / भौचर',
        'Debit':'डेबिट',
        'Add Entry':'प्रविष्टि थप्नुहोस्',
        'Month':'महिना',
        'Base Salary':'आधार तलब',
        'Bonus':'बोनस',
        'Deduction':'कटौती',
        'Amount Paid':'भुक्तानी गरिएको रकम',
        'Save Salary':'तलब सुरक्षित गर्नुहोस्',
        'Ledger Balances':'खाता-बही शेषहरू',
        'No ledger accounts yet.':'अहिलेसम्म खाता-बही खाता छैन।',
        'Recent Ledger Entries':'हालका खाता-बही प्रविष्टिहरू',
        'No entries yet.':'अहिलेसम्म प्रविष्टि छैन।',
        'Salary History':'तलब इतिहास',
        'No salary records yet.':'अहिलेसम्म तलब रेकर्ड छैन।',
        'Customer':'ग्राहक',
        'Party / Supplier':'पार्टी / आपूर्तिकर्ता',
        'Other':'अन्य',

        /* ── Menu Management ── */
        'Menu Management':'मेनु व्यवस्थापन',
        'Add Menu Item':'मेनु वस्तु थप्नुहोस्',
        'Item Name':'वस्तुको नाम',
        'Price (Rs.)':'मूल्य (रु.)',
        'Price':'मूल्य',
        'Veg':'शाकाहारी',
        'Vegetarian':'शाकाहारी',
        'Categories':'श्रेणीहरू',
        'New category name':'नयाँ श्रेणीको नाम',
        'Add':'थप्नुहोस्',
        'All Items':'सबै वस्तुहरू',
        'Uncategorized':'श्रेणी नभएको',
        'Enable':'सक्रिय गर्नुहोस्',
        'Disable':'निष्क्रिय गर्नुहोस्',
        'OFF':'बन्द',
        'No menu items yet':'अहिलेसम्म मेनु वस्तु छैन',
        'No categories yet. Add one above.':'अहिलेसम्म श्रेणी छैन। माथि एउटा थप्नुहोस्।',

        /* ── Gallery ── */
        'Upload restaurant photos directly from The Spot panel. No WordPress Admin or Media Library visit is required.':'The Spot प्यानलबाट सिधै रेस्टुरेन्ट फोटो अपलोड गर्नुहोस्। WordPress Admin वा Media Library जानु पर्दैन।',
        '✓ Gallery saved successfully.':'✓ ग्यालरी सफलतापूर्वक सुरक्षित भयो।',
        'Gallery saved successfully.':'ग्यालरी सफलतापूर्वक सुरक्षित भयो।',
        'Add Photos':'फोटो थप्नुहोस्',
        'Choose photos':'फोटो छान्नुहोस्',
        'JPG, PNG, WEBP or GIF · up to 10 MB each':'JPG, PNG, WEBP वा GIF · प्रत्येक १० MB सम्म',
        'Your Gallery':'तपाईंको ग्यालरी',
        'Drag photos to change their order. Remove only removes them from the public gallery.':'क्रम बदल्न फोटो तान्नुहोस्। हटाउँदा सार्वजनिक ग्यालरीबाट मात्र हट्छ।',
        'Clear All':'सबै हटाउनुहोस्',
        'No photos yet. Choose photos above and click Save Gallery.':'अहिलेसम्म फोटो छैन। माथि फोटो छानेर Save Gallery क्लिक गर्नुहोस्।',
        'Save Gallery':'ग्यालरी सुरक्षित गर्नुहोस्',
        'Remove photo':'फोटो हटाउनुहोस्',
        'Remove':'हटाउनुहोस्',

        /* ── Staff / Users ── */
        'Staff Management':'कर्मचारी व्यवस्थापन',
        'Staff & Security':'कर्मचारी र सुरक्षा',
        'Add Staff Member':'कर्मचारी थप्नुहोस्',
        'Display Name':'प्रदर्शन नाम',
        'Username':'युजरनेम',
        'Password':'पासवर्ड',
        'Role':'भूमिका',
        'Phone':'फोन',
        'Email':'इमेल',
        'Designation':'पद',
        'Monthly Salary':'मासिक तलब',
        'Monthly Salary (Rs.)':'मासिक तलब (रु.)',
        'Joining Date':'नियुक्ति मिति',
        'Emergency Contact':'आपतकालीन सम्पर्क',
        'Address':'ठेगाना',
        'Select role...':'भूमिका छान्नुहोस्...',
        'Add Staff':'कर्मचारी थप्नुहोस्',
        'Set New Password':'नयाँ पासवर्ड सेट गर्नुहोस्',
        'Leave blank to keep current':'हालको राख्न खाली छोड्नुहोस्',
        'Save Staff Details':'कर्मचारी विवरण सुरक्षित गर्नुहोस्',
        'Profile picture':'प्रोफाइल तस्विर',
        'Upload JPG, PNG or WebP. The image is stored directly for the panel profile.':'JPG, PNG वा WebP अपलोड गर्नुहोस्। तस्विर प्यानल प्रोफाइलका लागि सीधा सुरक्षित हुन्छ।',
        'Remove current picture':'हालको तस्विर हटाउनुहोस्',
        'Record Leave':'बिदा दर्ता गर्नुहोस्',
        'From':'देखि',
        'To':'सम्म',
        'Type':'प्रकार',
        'Status':'स्थिति',
        'Reason':'कारण',
        'Save Leave':'बिदा सुरक्षित गर्नुहोस्',
        'Leave History':'बिदा इतिहास',
        'No leave records yet.':'अहिलेसम्म बिदा रेकर्ड छैन।',
        'Remove Staff':'कर्मचारी हटाउनुहोस्',
        'Remove this staff member?':'यो कर्मचारी हटाउने?',
        'No staff members added yet':'अहिलेसम्म कर्मचारी थपिएको छैन',
        'Admin':'एडमिन',
        'Manager':'म्यानेजर',
        'Cashier':'क्यासियर',
        'Waiter':'वेटर',
        'Casual':'आकस्मिक',
        'Sick':'बिरामी',
        'Annual':'वार्षिक',
        'Unpaid':'बिना तलब',
        'Approved':'स्वीकृत',
        'Rejected':'अस्वीकृत',

        /* ── Settings ── */
        'Restaurant Info':'रेस्टुरेन्ट जानकारी',
        'Restaurant Name':'रेस्टुरेन्ट नाम',
        'Phone Number':'फोन नम्बर',
        'Social & Contact':'सामाजिक र सम्पर्क',
        'WhatsApp Number (without +)':'WhatsApp नम्बर (+ बिना)',
        'Facebook URL':'Facebook URL',
        'TikTok URL':'TikTok URL',
        'Google Maps URL':'Google Maps URL',
        '✓ Settings saved successfully.':'✓ सेटिङहरू सफलतापूर्वक सुरक्षित भयो।',
        'Settings saved successfully.':'सेटिङहरू सफलतापूर्वक सुरक्षित भयो।',
        'Save Settings':'सेटिङ सुरक्षित गर्नुहोस्',

        /* ── Inventory ── */
        'Add Inventory Item':'स्टक वस्तु थप्नुहोस्',
        'Item name':'वस्तुको नाम',
        'SKU':'SKU',
        'Unit':'एकाइ',
        'Opening stock':'सुरुवाती स्टक',
        'Reorder level':'पुनः अर्डर स्तर',
        'Cost price':'लागत मूल्य',
        'Sell price':'बिक्री मूल्य',
        'Add Item':'वस्तु थप्नुहोस्',
        'Stock Movement':'स्टक परिवर्तन',
        'Stock In':'स्टक भित्र',
        'Stock Out':'स्टक बाहिर',
        'Set Stock':'स्टक सेट गर्नुहोस्',
        'Quantity':'परिमाण',
        'Save Movement':'परिवर्तन सुरक्षित गर्नुहोस्',
        'Current Stock':'हालको स्टक',
        'LOW':'कम',
        'Item':'वस्तु',
        'Stock':'स्टक',
        'Reorder':'पुनः अर्डर',
        'Cost':'लागत',
        'No inventory items yet.':'अहिलेसम्म स्टक वस्तु छैन।',

        /* ── Expenses ── */
        'Total recent expenses':'हालका कुल खर्च',
        'Record Expense':'खर्च दर्ता गर्नुहोस्',
        'Category':'श्रेणी',
        'Description':'विवरण',
        'Amount':'रकम',
        'Reference':'सन्दर्भ',
        'Save Expense':'खर्च सुरक्षित गर्नुहोस्',
        'Recent Expenses':'हालका खर्च',

        /* ── Cash Shift ── */
        'Cash Shift & Day Closing':'क्यास सिफ्ट र दिन बन्द',
        'Open Cash Shift':'क्यास सिफ्ट खोल्नुहोस्',
        'Opening cash (Rs.)':'सुरुवाती नगद (रु.)',
        'Open Shift':'सिफ्ट खोल्नुहोस्',
        'Current Open Shift':'हालको खुला सिफ्ट',
        'Actual cash counted (Rs.)':'गनिएको नगद (रु.)',
        'Closing notes':'बन्द गर्ने नोट',
        'Close Day':'दिन बन्द गर्नुहोस्',
        'Shift History':'सिफ्ट इतिहास',

        /* ── Activity Log ── */
        'Important actions performed inside the Spot panel are recorded here.':'Spot प्यानलमा गरिएका महत्त्वपूर्ण कार्यहरू यहाँ रेकर्ड हुन्छन्।',

        /* ── Backup ── */
        'Backup & Restore':'ब्याकअप र रिस्टोर',
        'Database Backup':'डाटाबेस ब्याकअप',
        'Download Backup Now':'अहिले ब्याकअप डाउनलोड गर्नुहोस्',
        'Create Server Backup':'सर्भर ब्याकअप बनाउनुहोस्',
        'Important':'महत्त्वपूर्ण',
        'Backup is database-only in this version. Your uploaded gallery files remain on the hosting server. Before major changes, download a backup to your computer.':'यो संस्करणमा ब्याकअप डाटाबेस मात्र हो। तपाईंका अपलोड गरिएका ग्यालरी फाइलहरू होस्टिङ सर्भरमा रहन्छन्। ठूला परिवर्तन गर्नुअघि, आफ्नो कम्प्युटरमा ब्याकअप डाउनलोड गर्नुहोस्।',

        /* ── Messages / Chat ── */
        'Staff Chat':'कर्मचारी च्याट',
        'Chat privately with your restaurant team.':'तपाईंको रेस्टुरेन्ट टोलीसँग निजी च्याट गर्नुहोस्।',
        'unread':'नपढेको',
        'team members':'टोली सदस्य',
        'Search people...':'मानिस खोज्नुहोस्...',
        'Start a conversation':'कुराकानी सुरु गर्नुहोस्',
        'New Chat':'नयाँ च्याट',
        'Choose someone from your team.':'आफ्नो टोलीबाट कसैलाई छान्नुहोस्।',
        'Write a message...':'सन्देश लेख्नुहोस्...',
        'Conversation':'कुराकानी',
        'Restaurant team member':'रेस्टुरेन्ट टोली सदस्य',
        'Send a quick message to your teammate.':'आफ्नो साथीलाई छिटो सन्देश पठाउनुहोस्।',
        'Your messages':'तपाईंका सन्देशहरू',
        'Choose a teammate to start chatting.':'च्याट सुरु गर्न साथी छान्नुहोस्।',
        'Seen':'देखिएको',
        'Sent':'पठाइएको',
        'Message':'सन्देश',
        'Send':'पठाउनुहोस्',

        /* ── Login ── */
        'The Spot Admin':'The Spot एडमिन',
        'Staff Panel — Sign in to continue':'कर्मचारी प्यानल — जारी राख्न साइन इन गर्नुहोस्',
        'Remember me':'मलाई सम्झनुहोस्',
        'Sign In':'साइन इन',
        '← Back to website':'← वेबसाइटमा फर्कनुहोस्',

        /* ── Common / shared ── */
        'Save':'सुरक्षित गर्नुहोस्',
        'Edit':'सम्पादन गर्नुहोस्',
        'Close':'बन्द गर्नुहोस्',
        'Search':'खोज्नुहोस्',
        'Filter':'फिल्टर',
        'Loading...':'लोड हुँदैछ...',
        'Error':'त्रुटि',
        'Success':'सफल',
        'Yes':'हो',
        'No':'होइन',
        'View':'हेर्नुहोस्',
        'Print':'प्रिन्ट गर्नुहोस्',
        'Download':'डाउनलोड गर्नुहोस्',
        'Submit':'पेश गर्नुहोस्',
        'Update':'अपडेट गर्नुहोस्',
        'Create':'बनाउनुहोस्',
        'Back':'पछाडि',
        'Next':'अर्को',
        'Previous':'अघिल्लो',
        'Access denied.':'पहुँच अस्वीकृत।',
        'Deleted Item':'मेटिएको वस्तु',
        'No Table':'टेबल छैन',
        'Connection error.':'जडान त्रुटि।',
        'Could not save payment.':'भुक्तानी सुरक्षित गर्न सकिएन।'
    };

    function walk(n){

        if(n.nodeType===3){

            var t=n.nodeValue.trim();

            if(d[t]){
                n.nodeValue=n.nodeValue.replace(t,d[t]);
            }

            return;
        }

        if(n.nodeType!==1) return;

        ['placeholder','title','aria-label'].forEach(function(a){

            if(
                n.hasAttribute(a) &&
                d[n.getAttribute(a)]
            ){
                n.setAttribute(
                    a,
                    d[n.getAttribute(a)]
                );
            }

        });

        Array.prototype.forEach.call(
            n.childNodes,
            walk
        );

    }

    walk(document.body);

})();
</script>

<!-- Main Content -->
<main class="panel-main">