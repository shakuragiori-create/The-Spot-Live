(function() {
    'use strict';

    var data = window.rpKitchenData;
    if (!data) return;

    // KOT action buttons
    document.querySelectorAll('.kot-action').forEach(function(btn) {
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.textContent = 'Updating...';

            var fd = new FormData();
            fd.append('action', 'rp_update_kot_status');
            fd.append('nonce', data.nonce);
            fd.append('kot_id', btn.dataset.kot);
            fd.append('status', btn.dataset.status);

            fetch(data.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert(res.data || 'Error');
                        btn.disabled = false;
                    }
                });
        });
    });

    // Filter tabs
    document.querySelectorAll('.filter-tabs .filter-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tabs .filter-tab').forEach(function(t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var filter = tab.dataset.filter;

            document.querySelectorAll('.kot-card').forEach(function(card) {
                if (filter === 'all') { card.style.display = ''; return; }
                card.style.display = card.dataset.status === filter ? '' : 'none';
            });
        });
    });

    // Auto-refresh every 10s (pause when hidden)
    var interval;
    function startPolling() {
        interval = setInterval(function() { location.reload(); }, 10000);
    }
    function stopPolling() { clearInterval(interval); }

    if (!document.hidden) startPolling();
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) stopPolling();
        else startPolling();
    });
})();
