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

            navigator.serviceWorker.register(swUrl + '?v=' + <?php echo wp_json_encode( RP_PLUGIN_VERSION ); ?>, {
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
</script>
<?php if ( (get_user_meta($current_user->ID,'rp_panel_language',true) ?: 'en') !== 'en' ) : ?>
<script src="<?php echo esc_url( RP_PLUGIN_URL . 'panel/js/translations.js' ); ?>?v=<?php echo RP_PLUGIN_VERSION; ?>"></script>
<?php endif; ?>

<!-- Main Content -->
<main class="panel-main">