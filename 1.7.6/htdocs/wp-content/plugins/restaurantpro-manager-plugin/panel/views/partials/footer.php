</main>

<script>
(function(){
    var toggle = document.getElementById('sidebarToggle');
    var close = document.getElementById('sidebarClose');
    var sidebar = document.getElementById('panelSidebar');
    var overlay = document.getElementById('sidebarOverlay');

    function open() { sidebar.classList.add('open'); overlay.classList.add('open'); document.body.classList.add('sidebar-open'); }
    function shut() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.classList.remove('sidebar-open'); }

    toggle.addEventListener('click', open);
    close.addEventListener('click', shut);
    overlay.addEventListener('click', shut);
})();
</script>
<script src="<?php echo esc_url( RP_PLUGIN_URL . 'panel/js/panel.js' ); ?>?v=<?php echo RP_PLUGIN_VERSION; ?>"></script>
<?php if ( in_array( $page, ['kitchen'], true ) ) : ?>
<script src="<?php echo esc_url( RP_PLUGIN_URL . 'panel/js/kitchen.js' ); ?>?v=<?php echo RP_PLUGIN_VERSION; ?>"></script>
<?php endif; ?>
<?php if ( $page === 'pos' ) : ?>
<script src="<?php echo esc_url( RP_PLUGIN_URL . 'panel/js/pos.js' ); ?>?v=<?php echo RP_PLUGIN_VERSION; ?>"></script>
<?php endif; ?>
<?php if ( in_array( $page, ['orders', 'order-new', 'order-view'], true ) ) : ?>
<script src="<?php echo esc_url( RP_PLUGIN_URL . 'panel/js/orders.js' ); ?>?v=<?php echo RP_PLUGIN_VERSION; ?>"></script>
<?php endif; ?>
<?php if ( $page === 'dashboard' ) : ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="<?php echo esc_url( RP_PLUGIN_URL . 'panel/js/dashboard.js' ); ?>?v=<?php echo RP_PLUGIN_VERSION; ?>"></script>
<?php endif; ?>
<link rel="stylesheet" href="<?php echo esc_url(RP_PLUGIN_URL.'panel/css/order-sync-fix.css'); ?>?v=<?php echo RP_PLUGIN_VERSION; ?>">
</body>
</html>
