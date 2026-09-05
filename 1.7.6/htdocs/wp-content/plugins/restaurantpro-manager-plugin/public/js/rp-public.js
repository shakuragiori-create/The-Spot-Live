(function($) {
    'use strict';

    // Menu search
    var searchTimer;
    $(document).on('input', '#rp-menu-search', function() {
        var query = $(this).val();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            $.post(rpPublic.ajaxUrl, {
                action: 'rp_search_menu_items',
                nonce: rpPublic.nonce,
                query: query
            }, function(response) {
                if (response.success) {
                    $('#rp-menu-grid').html(response.data.html);
                }
            });
        }, 300);
    });

    // Category filter
    $(document).on('click', '.rp-filter-btn', function(e) {
        e.preventDefault();
        var catId = $(this).data('category');

        $('.rp-filter-btn').removeClass('btn-dark').addClass('btn-outline-dark');
        $(this).removeClass('btn-outline-dark').addClass('btn-dark');

        $.post(rpPublic.ajaxUrl, {
            action: 'rp_filter_menu',
            nonce: rpPublic.nonce,
            category: catId
        }, function(response) {
            if (response.success) {
                $('#rp-menu-grid').html(response.data.html);
            }
        });
    });

    // Reservation form
    $(document).on('submit', '#rp-reservation-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('[type="submit"]');
        var msgDiv = form.find('.rp-reservation-msg');

        btn.prop('disabled', true).text('Submitting...');
        msgDiv.html('');

        $.post(rpPublic.ajaxUrl, {
            action: 'rp_submit_reservation',
            nonce: rpPublic.nonce,
            guest_name: form.find('[name="guest_name"]').val(),
            phone: form.find('[name="phone"]').val(),
            email: form.find('[name="email"]').val(),
            reservation_date: form.find('[name="reservation_date"]').val(),
            reservation_time: form.find('[name="reservation_time"]').val(),
            guests: form.find('[name="guests"]').val(),
            notes: form.find('[name="notes"]').val()
        }, function(response) {
            if (response.success) {
                msgDiv.html('<div class="alert alert-success">' + response.data.message + '</div>');
                form[0].reset();
            } else {
                msgDiv.html('<div class="alert alert-danger">' + (response.data.message || 'Something went wrong.') + '</div>');
            }
            btn.prop('disabled', false).text('Reserve a Table');
        }).fail(function() {
            msgDiv.html('<div class="alert alert-danger">Network error. Please try again.</div>');
            btn.prop('disabled', false).text('Reserve a Table');
        });
    });

})(jQuery);
