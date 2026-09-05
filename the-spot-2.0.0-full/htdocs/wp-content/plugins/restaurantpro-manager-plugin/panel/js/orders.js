(function() {
    'use strict';

    // ========== ORDERS LIST — LIVE SYNC WITH POS ==========
    (function initOrdersSync() {
        var list = document.getElementById('rpOrdersList');
        var syncState = document.getElementById('rpOrdersSyncState');
        var od = window.rpOrdersData;
        if (!list || !od || !od.ajaxUrl) return;

        var busy = false;

        function esc(v) {
            return String(v == null ? '' : v).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
            });
        }

        function renderOrders(orders) {
            if (!orders.length) {
                list.innerHTML = '<div class="empty-state"><div class="empty-icon"><svg width="40" height="40" fill="none" stroke="#bbb" stroke-width="1.5"><rect x="6" y="6" width="14" height="14" rx="2"/><rect x="24" y="6" width="14" height="14" rx="2"/><rect x="6" y="24" width="14" height="14" rx="2"/><rect x="24" y="24" width="14" height="14" rx="2"/></svg></div><p>No orders found for this date</p></div>';
                return;
            }
            list.innerHTML = orders.map(function(o) {
                var statusLabel = o.status === 'cancelled' ? 'Cancelled' : esc(o.status_label);
                var paidClass = o.paid ? 'is-paid' : 'is-unpaid';
                var paidBadge = o.paid
                    ? '<span class="badge" style="background:#dcfce7;color:#166534">PAID</span>'
                    : '<span class="badge" style="background:#fee2e2;color:#991b1b">NOT PAID</span>';
                var tableBadge = o.table_number ? '<span class="order-table">T' + o.table_number + '</span>' : '';
                return '<a href="' + esc(o.order_url) + '" class="order-item rp-pay-order ' + paidClass + '" data-order-id="' + o.id + '">' +
                    '<span class="order-num">#' + o.id + '</span>' +
                    '<div class="order-meta"><div class="order-meta-top">' +
                        '<span class="badge badge-' + esc(o.status) + '">' + statusLabel + '</span>' +
                        paidBadge + tableBadge +
                    '</div><span class="text-sm text-muted">' + esc(o.time_label) + ' &bull; ' + esc(o.customer_name || 'Walk-in Customer') + '</span></div>' +
                    '<span style="display:flex;align-items:center;gap:8px">' +
                        '<span class="order-total">Rs.' + Math.round(o.total).toLocaleString('en-NP') + '</span>' +
                    '</span></a>';
            }).join('');
        }

        function syncOrders() {
            if (busy) return;
            busy = true;
            var body = new URLSearchParams();
            body.append('action', 'rp_orders_sync');
            body.append('nonce', od.nonce);
            body.append('date', od.date || 'all');
            body.append('status', od.status || '');

            fetch(od.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res || !res.success) return;
                    renderOrders(res.data.orders || []);
                    if (syncState) syncState.textContent = 'Synced • ' + (res.data.count || 0) + ' order(s)';
                })
                .catch(function() {
                    if (syncState) syncState.textContent = 'Sync paused — retrying…';
                })
                .finally(function() { busy = false; });
        }

        syncOrders();
        setInterval(syncOrders, 5000);
    })();

    // Order-view: Advance status / Cancel
    var advBtn = document.getElementById('advanceOrder');
    var cancelBtn = document.getElementById('cancelOrder');

    if (advBtn) {
        advBtn.addEventListener('click', function() {
            var data = window.rpOrderViewData;
            advBtn.disabled = true;
            advBtn.textContent = 'Updating...';

            var fd = new FormData();
            fd.append('action', 'rp_update_order_status');
            fd.append('nonce', data.nonce);
            fd.append('order_id', advBtn.dataset.order);
            fd.append('status', advBtn.dataset.status);

            fetch(data.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) location.reload();
                    else { alert((res.data && res.data.message) || 'Error'); advBtn.disabled = false; }
                });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (!confirm('Cancel this order?')) return;
            var data = window.rpOrderViewData;
            var fd = new FormData();
            fd.append('action', 'rp_update_order_status');
            fd.append('nonce', data.nonce);
            fd.append('order_id', cancelBtn.dataset.order);
            fd.append('status', 'cancelled');

            fetch(data.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function() { location.reload(); });
        });
    }

    // ========== NEW ORDER: SHARED ITEM MANAGEMENT & SUBMISSION ==========
    var form = document.getElementById('newOrderForm');
    if (!form) return;

    var items = {};
    var data = window.rpNewOrderData;
    var itemsCard = document.getElementById('orderItems');
    var selectedDiv = document.getElementById('selectedItems');
    var totalEl = document.getElementById('orderTotal');
    var submitBtn = document.getElementById('submitOrder');
    var countLabel = document.getElementById('orderItemCountLabel');

    // Mobile elements
    var mobileCountEl = document.getElementById('mobileOrderCount');
    var mobileTotalEl = document.getElementById('mobileOrderTotal');
    var mobileSubmitBtn = document.getElementById('mobileSubmitOrder');

    // Add item buttons — use event delegation for touch compatibility
    document.querySelectorAll('.add-item-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var row = btn.closest('.menu-pick-item');
            var id = row.dataset.id;
            var price = parseFloat(row.dataset.price);
            var name = row.dataset.name;

            if (!items[id]) {
                items[id] = { name: name, price: price, qty: 0 };
            }
            items[id].qty++;
            renderItems();
        });
    });

    // Also allow tapping the whole menu item card on mobile
    document.querySelectorAll('.menu-pick-item').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (e.target.closest('.add-item-btn') || e.target.closest('.qty-btn')) return;
            var id = el.dataset.id;
            var price = parseFloat(el.dataset.price);
            var name = el.dataset.name;

            if (!items[id]) {
                items[id] = { name: name, price: price, qty: 0 };
            }
            items[id].qty++;
            renderItems();
        });
    });

    // Search
    var search = document.getElementById('menuSearch');
    if (search) {
        search.addEventListener('input', function() {
            var q = search.value.toLowerCase();
            document.querySelectorAll('.menu-pick-item').forEach(function(el) {
                el.style.display = el.dataset.name.toLowerCase().indexOf(q) > -1 ? '' : 'none';
            });
        });
    }

    // Category filter
    var catFilter = document.getElementById('catFilter');
    if (catFilter) {
        catFilter.querySelectorAll('.filter-tab').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                catFilter.querySelectorAll('.filter-tab').forEach(function(t) { t.classList.remove('active'); });
                tab.classList.add('active');
                var cat = tab.dataset.cat;
                document.querySelectorAll('.menu-pick-item').forEach(function(el) {
                    if (cat === 'all') { el.style.display = ''; return; }
                    var cats = el.dataset.cats.split(',');
                    el.style.display = cats.indexOf(cat) > -1 ? '' : 'none';
                });
            });
        });
    }

    function renderItems() {
        var html = '';
        var total = 0;
        var count = 0;
        var itemCount = 0;

        Object.keys(items).forEach(function(id) {
            var item = items[id];
            if (item.qty < 1) { delete items[id]; return; }
            var sub = item.price * item.qty;
            total += sub;
            count++;
            itemCount += item.qty;
            html += '<div class="selected-item">' +
                '<div class="qty-controls">' +
                '<button type="button" class="qty-btn" data-id="' + id + '" data-dir="-1">−</button>' +
                '<span class="qty-val">' + item.qty + '</span>' +
                '<button type="button" class="qty-btn" data-id="' + id + '" data-dir="1">+</button>' +
                '</div>' +
                '<span class="item-name">' + item.name + '</span>' +
                '<span class="item-total">Rs.' + sub + '</span>' +
                '</div>';
        });

        selectedDiv.innerHTML = html;
        totalEl.textContent = 'Rs.' + total;
        itemsCard.style.display = count > 0 ? '' : 'none';
        submitBtn.disabled = count === 0;

        // Update count label on desktop
        if (countLabel) {
            countLabel.textContent = itemCount + ' item' + (itemCount !== 1 ? 's' : '');
        }

        // Update mobile order bar — always keep in sync
        if (mobileCountEl) {
            mobileCountEl.textContent = itemCount + ' item' + (itemCount !== 1 ? 's' : '');
        }
        if (mobileTotalEl) {
            mobileTotalEl.textContent = 'Rs.' + total;
        }
        if (mobileSubmitBtn) {
            mobileSubmitBtn.disabled = count === 0;
        }

        // Rebind qty buttons
        selectedDiv.querySelectorAll('.qty-btn').forEach(function(b) {
            b.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = b.dataset.id;
                var dir = parseInt(b.dataset.dir);
                if (items[id]) {
                    items[id].qty += dir;
                    if (items[id].qty < 1) delete items[id];
                }
                renderItems();
            });
        });
    }

    // ========== SHARED ORDER SUBMISSION FUNCTION ==========
    // Both desktop and mobile Create Order buttons call this exact same function.
    // Same AJAX endpoint, same nonce, same validation, same payload.
    function submitNewOrder() {
        var orderItems = [];
        Object.keys(items).forEach(function(id) {
            orderItems.push({ menu_item_id: id, quantity: items[id].qty });
        });

        if (orderItems.length === 0) {
            return;
        }

        // Disable both buttons during submission
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
        if (mobileSubmitBtn) {
            mobileSubmitBtn.disabled = true;
            mobileSubmitBtn.textContent = 'Creating...';
        }

        var fd = new FormData();
        fd.append('action', 'rp_create_order');
        fd.append('nonce', data.nonce);
        fd.append('table_number', form.querySelector('[name=table_number]').value);
        fd.append('notes', form.querySelector('[name=notes]').value);
        fd.append('customer_name', form.querySelector('[name=customer_name]') ? form.querySelector('[name=customer_name]').value : '');
        fd.append('customer_phone', form.querySelector('[name=customer_phone]') ? form.querySelector('[name=customer_phone]').value : '');
        fd.append('items', JSON.stringify(orderItems));

        fetch(data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    window.location.href = (res.data && res.data.redirect) ? res.data.redirect : data.redirectUrl;
                } else {
                    alert((res.data && res.data.message) || 'Error creating order');
                    resetButtons();
                }
            })
            .catch(function() {
                resetButtons();
            });
    }

    function resetButtons() {
        submitBtn.disabled = Object.keys(items).length === 0;
        submitBtn.textContent = 'Create Order';
        if (mobileSubmitBtn) {
            mobileSubmitBtn.disabled = Object.keys(items).length === 0;
            mobileSubmitBtn.textContent = 'Create Order';
        }
    }

    // Desktop: form submit event
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitNewOrder();
    });

    // Mobile: button click — calls the exact same submitNewOrder()
    if (mobileSubmitBtn) {
        mobileSubmitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            submitNewOrder();
        });

        // Also handle touch events for mobile reliability
        mobileSubmitBtn.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            submitNewOrder();
        });
    }
})();
