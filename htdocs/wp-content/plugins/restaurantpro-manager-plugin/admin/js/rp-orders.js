(function($) {
    'use strict';

    var orderItems = [];
    var currencySymbol = 'Rs.';

    function updateTotal() {
        var total = 0;
        orderItems.forEach(function(item) {
            total += item.price * item.quantity;
        });
        $('#rp-order-total').text(currencySymbol + ' ' + total.toFixed(2));
    }

    function renderItems() {
        var tbody = $('#rp-order-items-table tbody');
        tbody.empty();

        if (orderItems.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted">No items added yet.</td></tr>');
            updateTotal();
            return;
        }

        orderItems.forEach(function(item, index) {
            var subtotal = (item.price * item.quantity).toFixed(2);
            tbody.append(
                '<tr>' +
                '<td>' + item.name + '</td>' +
                '<td>' + item.quantity + '</td>' +
                '<td>' + currencySymbol + ' ' + item.price.toFixed(2) + '</td>' +
                '<td>' + currencySymbol + ' ' + subtotal + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger rp-remove-item" data-index="' + index + '">×</button></td>' +
                '</tr>'
            );
        });

        updateTotal();
    }

    // Add item
    $('#rp-add-item-btn').on('click', function() {
        var select = $('#rp-item-select');
        var itemId = select.val();
        if (!itemId) return;

        var option = select.find(':selected');
        var price = parseFloat(option.data('price')) || 0;
        var name = option.text().split('—')[0].trim();
        var qty = parseInt($('#rp-item-qty').val()) || 1;

        var existing = orderItems.find(function(i) { return i.id == itemId; });
        if (existing) {
            existing.quantity += qty;
        } else {
            orderItems.push({ id: itemId, name: name, price: price, quantity: qty });
        }

        renderItems();
        select.val('');
        $('#rp-item-qty').val(1);
    });

    // Remove item
    $(document).on('click', '.rp-remove-item', function() {
        var index = $(this).data('index');
        orderItems.splice(index, 1);
        renderItems();
    });

    // Submit new order
    $('#rp-new-order-form').on('submit', function(e) {
        e.preventDefault();

        if (orderItems.length === 0) {
            alert('Please add at least one item.');
            return;
        }

        var btn = $(this).find('[type="submit"]');
        btn.prop('disabled', true).text('Creating...');

        $.post(rpAdmin.ajaxUrl, {
            action: 'rp_create_order',
            nonce: rpAdmin.nonce,
            table_number: $('#rp-order-table').val(),
            notes: $('#rp-order-notes').val(),
            items: JSON.stringify(orderItems)
        }, function(response) {
            if (response.success) {
                window.location.href = response.data.redirect;
            } else {
                alert(response.data.message || 'Failed to create order.');
                btn.prop('disabled', false).text('Create Order');
            }
        }).fail(function() {
            alert('Network error.');
            btn.prop('disabled', false).text('Create Order');
        });
    });

    renderItems();

})(jQuery);
