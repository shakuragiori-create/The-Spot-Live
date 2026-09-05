(function($) {
    'use strict';

    var pollInterval = 10000;
    var timer;

    // KOT status update
    $(document).on('click', '.rp-kot-action', function() {
        var btn = $(this);
        var kotId = btn.data('kot');
        var status = btn.data('status');

        btn.prop('disabled', true).text('Updating...');

        $.post(rpAdmin.ajaxUrl, {
            action: 'rp_update_kot_status',
            nonce: rpAdmin.nonce,
            kot_id: kotId,
            status: status
        }, function(response) {
            if (response.success) {
                refreshKitchen();
            } else {
                alert(response.data.message || 'Failed to update KOT.');
                btn.prop('disabled', false);
            }
        }).fail(function() {
            alert('Network error.');
            btn.prop('disabled', false);
        });
    });

    // Filter buttons
    $(document).on('click', '.rp-kitchen-filter', function() {
        var filter = $(this).data('filter');
        $('.rp-kitchen-filter').removeClass('active btn-warning').addClass('btn-outline-warning');
        $(this).addClass('active btn-warning').removeClass('btn-outline-warning');

        if (filter === 'all') {
            $('.rp-kot-card').show();
        } else {
            $('.rp-kot-card').hide();
            $('.rp-kot-card[data-status="' + filter + '"]').show();
        }
    });

    // Auto-refresh
    function refreshKitchen() {
        $.post(rpAdmin.ajaxUrl, {
            action: 'rp_get_kitchen_orders',
            nonce: rpAdmin.nonce
        }, function(response) {
            if (response.success) {
                $('#rp-kitchen-board').html(response.data.html);
            }
        });
    }

    function startPolling() {
        timer = setInterval(refreshKitchen, pollInterval);
    }

    // Only poll when tab is visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(timer);
        } else {
            refreshKitchen();
            startPolling();
        }
    });

    startPolling();

})(jQuery);
