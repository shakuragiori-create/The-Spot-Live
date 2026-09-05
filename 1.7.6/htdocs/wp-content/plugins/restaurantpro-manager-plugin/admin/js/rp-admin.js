(function($) {
    'use strict';

    // Order status update (from list or detail page)
    $(document).on('click', '.rp-update-order-status', function() {
        var btn = $(this);
        var orderId = btn.data('order');
        var status = btn.data('status') || $('#rp-order-status-select').val();

        if (!status) return;

        btn.prop('disabled', true).text('Updating...');

        $.post(rpAdmin.ajaxUrl, {
            action: 'rp_update_order_status',
            nonce: rpAdmin.nonce,
            order_id: orderId,
            status: status
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || 'Failed to update order.');
                btn.prop('disabled', false).text('Update');
            }
        }).fail(function() {
            alert('Network error.');
            btn.prop('disabled', false).text('Update');
        });
    });

    // Table status change
    $(document).on('change', '.rp-table-status-select', function() {
        var select = $(this);
        var tableId = select.data('table');
        var status = select.val();

        $.post(rpAdmin.ajaxUrl, {
            action: 'rp_update_table_status',
            nonce: rpAdmin.nonce,
            table_id: tableId,
            status: status
        }, function(response) {
            if (!response.success) {
                alert(response.data.message || 'Failed to update table.');
            }
        });
    });

})(jQuery);
