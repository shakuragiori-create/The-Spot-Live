/* RestaurantPro — POS cart (vanilla JS, no jQuery needed) */
(function () {
    'use strict';

    var grid = document.getElementById('rp-pos-grid');
    if (!grid) { return; }

    var cfg = window.rpPos || {};
    var symbol = cfg.symbol || 'Rs.';
    var symbolAfter = cfg.position === 'after';

    var cart = [];   // { id, name, price, qty, custom }

    /* ---------- helpers ---------- */

    function money(n) {
        var v = (Math.round((Number(n) || 0) * 100) / 100).toFixed(2);
        return symbolAfter ? v + ' ' + symbol : symbol + ' ' + v;
    }

    function num(el, fallback) {
        var v = parseFloat(el && el.value);
        return isNaN(v) ? (fallback || 0) : v;
    }

    function el(id) { return document.getElementById(id); }

    /* ---------- item picker: filter client-side, no AJAX ---------- */

    var searchInput = el('rp-pos-search');
    var vegToggle   = el('rp-pos-veg');
    var emptyNote   = el('rp-pos-empty');
    var activeCat   = '';

    function applyFilter() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var vegOnly = vegToggle.checked;
        var shown = 0;

        grid.querySelectorAll('.rp-pos-item').forEach(function (btn) {
            var name = (btn.getAttribute('data-name') || '').toLowerCase();
            var cats = ' ' + (btn.getAttribute('data-cats') || '') + ' ';
            var ok = true;

            if (q && name.indexOf(q) === -1) { ok = false; }
            if (activeCat && cats.indexOf(' ' + activeCat + ' ') === -1) { ok = false; }
            if (vegOnly && btn.getAttribute('data-veg') !== '1') { ok = false; }

            btn.style.display = ok ? '' : 'none';
            if (ok) { shown++; }
        });

        emptyNote.style.display = shown ? 'none' : '';
    }

    searchInput.addEventListener('input', applyFilter);
    vegToggle.addEventListener('change', applyFilter);

    document.querySelectorAll('.rp-pos-cat').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeCat = btn.getAttribute('data-cat') || '';
            document.querySelectorAll('.rp-pos-cat').forEach(function (b) {
                b.classList.remove('btn-dark');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-dark');
            applyFilter();
        });
    });

    grid.addEventListener('click', function (e) {
        var btn = e.target.closest('.rp-pos-item');
        if (!btn) { return; }
        addLine({
            id: parseInt(btn.getAttribute('data-id'), 10) || 0,
            name: btn.getAttribute('data-name') || '',
            price: parseFloat(btn.getAttribute('data-price')) || 0,
            custom: false
        });
        btn.classList.add('rp-pos-item--hit');
        setTimeout(function () { btn.classList.remove('rp-pos-item--hit'); }, 180);
    });

    /* ---------- cart ---------- */

    function addLine(item) {
        var existing = null;
        if (!item.custom) {
            for (var i = 0; i < cart.length; i++) {
                if (cart[i].id === item.id && !cart[i].custom) { existing = cart[i]; break; }
            }
        }
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({ id: item.id, name: item.name, price: item.price, qty: 1, custom: !!item.custom });
        }
        renderCart();
    }

    var rowsWrap = el('rp-cart-rows');

    function renderCart() {
        if (!cart.length) {
            rowsWrap.innerHTML = '<p class="text-muted small text-center py-4 mb-0">Tap an item on the left to start a bill.</p>';
            recalc();
            return;
        }

        var html = '';
        cart.forEach(function (line, i) {
            html += '<div class="rp-cart-row" data-i="' + i + '">'
                 +    '<div class="rp-cart-top">'
                 +      '<input type="text" class="form-control form-control-sm rp-cart-name" value="' + escapeAttr(line.name) + '">'
                 +      '<button type="button" class="btn-close rp-cart-del" aria-label="Remove"></button>'
                 +    '</div>'
                 +    '<div class="rp-cart-bot">'
                 +      '<div class="rp-qty">'
                 +        '<button type="button" class="btn btn-sm btn-outline-secondary rp-qty-down">&minus;</button>'
                 +        '<input type="number" class="form-control form-control-sm rp-cart-qty" min="1" step="1" value="' + line.qty + '">'
                 +        '<button type="button" class="btn btn-sm btn-outline-secondary rp-qty-up">+</button>'
                 +      '</div>'
                 +      '<div class="input-group input-group-sm rp-price-wrap">'
                 +        '<span class="input-group-text">' + escapeAttr(symbol) + '</span>'
                 +        '<input type="number" class="form-control rp-cart-price" min="0" step="0.01" value="' + line.price.toFixed(2) + '">'
                 +      '</div>'
                 +      '<span class="rp-cart-line-total">' + money(line.price * line.qty) + '</span>'
                 +    '</div>'
                 +  '</div>';
        });
        rowsWrap.innerHTML = html;
        recalc();
    }

    function escapeAttr(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    rowsWrap.addEventListener('click', function (e) {
        var row = e.target.closest('.rp-cart-row');
        if (!row) { return; }
        var i = parseInt(row.getAttribute('data-i'), 10);

        if (e.target.closest('.rp-cart-del')) {
            cart.splice(i, 1);
            renderCart();
        } else if (e.target.closest('.rp-qty-up')) {
            cart[i].qty += 1;
            renderCart();
        } else if (e.target.closest('.rp-qty-down')) {
            if (cart[i].qty > 1) { cart[i].qty -= 1; renderCart(); }
            else { cart.splice(i, 1); renderCart(); }
        }
    });

    rowsWrap.addEventListener('input', function (e) {
        var row = e.target.closest('.rp-cart-row');
        if (!row) { return; }
        var i = parseInt(row.getAttribute('data-i'), 10);

        if (e.target.classList.contains('rp-cart-qty')) {
            cart[i].qty = Math.max(1, parseInt(e.target.value, 10) || 1);
        } else if (e.target.classList.contains('rp-cart-price')) {
            cart[i].price = Math.max(0, parseFloat(e.target.value) || 0);
        } else if (e.target.classList.contains('rp-cart-name')) {
            cart[i].name = e.target.value;
        }
        var lt = row.querySelector('.rp-cart-line-total');
        if (lt) { lt.textContent = money(cart[i].price * cart[i].qty); }
        recalc();
    });

    el('rp-cart-custom').addEventListener('click', function () {
        addLine({ id: 0, name: 'Custom item', price: 0, custom: true });
        var last = rowsWrap.querySelector('.rp-cart-row:last-child .rp-cart-name');
        if (last) { last.focus(); last.select(); }
    });

    el('rp-pos-clear').addEventListener('click', function () {
        if (cart.length && !window.confirm('Clear the whole bill?')) { return; }
        cart = [];
        el('rp-pos-tendered').value = '';
        el('rp-pos-customer').value = '';
        el('rp-pos-phone').value = '';
        el('rp-pos-discount-type').value = 'none';
        el('rp-pos-discount-value').value = 0;
        el('rp-pos-discount-value').disabled = true;
        el('rp-pos-msg').textContent = '';
        renderCart();
    });

    /* ---------- totals preview (server recomputes on save) ---------- */

    var dType = el('rp-pos-discount-type');
    var dVal  = el('rp-pos-discount-value');

    dType.addEventListener('change', function () {
        dVal.disabled = dType.value === 'none';
        if (dType.value === 'none') { dVal.value = 0; }
        recalc();
    });

    ['rp-pos-discount-value', 'rp-pos-service', 'rp-pos-service-rate',
     'rp-pos-vat', 'rp-pos-vat-rate', 'rp-pos-tendered'].forEach(function (id) {
        var node = el(id);
        if (node) {
            node.addEventListener('input', recalc);
            node.addEventListener('change', recalc);
        }
    });

    function totals() {
        var subtotal = 0;
        cart.forEach(function (l) { subtotal += Math.round(l.price * l.qty * 100) / 100; });
        subtotal = Math.round(subtotal * 100) / 100;

        var discount = 0;
        if (dType.value === 'percent') {
            discount = Math.round(subtotal * Math.min(100, num(dVal)) / 100 * 100) / 100;
        } else if (dType.value === 'amount') {
            discount = Math.min(subtotal, num(dVal));
        }

        var base = Math.round((subtotal - discount) * 100) / 100;

        var svcRate = el('rp-pos-service').checked ? Math.min(100, num(el('rp-pos-service-rate'))) : 0;
        var svc = Math.round(base * svcRate / 100 * 100) / 100;

        var vatRate = el('rp-pos-vat').checked ? Math.min(100, num(el('rp-pos-vat-rate'))) : 0;
        var vat = Math.round((base + svc) * vatRate / 100 * 100) / 100;

        var total = Math.round((base + svc + vat) * 100) / 100;
        var paid = num(el('rp-pos-tendered'));

        return {
            subtotal: subtotal, discount: discount, svcRate: svcRate, svc: svc,
            vatRate: vatRate, vat: vat, total: total,
            change: paid > total ? Math.round((paid - total) * 100) / 100 : 0
        };
    }

    function recalc() {
        var t = totals();

        el('rp-t-subtotal').textContent = money(t.subtotal);
        el('rp-t-total').textContent = money(t.total);

        el('rp-t-discount-row').style.display = t.discount > 0 ? 'flex' : 'none';
        el('rp-t-discount').textContent = '− ' + money(t.discount);

        el('rp-t-service-row').style.display = t.svc > 0 ? 'flex' : 'none';
        el('rp-t-service-rate').textContent = '(' + t.svcRate + '%)';
        el('rp-t-service').textContent = money(t.svc);

        el('rp-t-vat-row').style.display = t.vat > 0 ? 'flex' : 'none';
        el('rp-t-vat-rate').textContent = '(' + t.vatRate + '%)';
        el('rp-t-vat').textContent = money(t.vat);

        el('rp-t-change-row').style.display = t.change > 0 ? 'block' : 'none';
        el('rp-t-change').textContent = money(t.change);
    }

    /* ---------- submit ---------- */

    function submit(payNow, btn) {
        if (!cart.length) {
            el('rp-pos-msg').innerHTML = '<span class="text-danger">Add at least one item first.</span>';
            return;
        }

        var original = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Saving…';
        el('rp-pos-msg').textContent = '';

        var body = new URLSearchParams();
        body.set('action', 'rp_pos_create_order');
        body.set('nonce', (window.rpAdmin && window.rpAdmin.nonce) || cfg.nonce || '');
        body.set('items', JSON.stringify(cart.map(function (l) {
            return { menu_item_id: l.id, item_name: l.name, quantity: l.qty, price: l.price };
        })));
        body.set('pay_now', payNow ? '1' : '');
        body.set('discount_type', dType.value);
        body.set('discount_value', String(num(dVal)));
        body.set('vat_enabled', el('rp-pos-vat').checked ? '1' : '');
        body.set('vat_rate', String(num(el('rp-pos-vat-rate'))));
        body.set('service_enabled', el('rp-pos-service').checked ? '1' : '');
        body.set('service_rate', String(num(el('rp-pos-service-rate'))));
        body.set('amount_paid', String(num(el('rp-pos-tendered'))));
        body.set('payment_method', el('rp-pos-payment').value);
        body.set('order_type', el('rp-pos-order-type').value);
        body.set('table_id', el('rp-pos-table').value);
        body.set('customer_name', el('rp-pos-customer').value);
        body.set('customer_phone', el('rp-pos-phone').value);
        body.set('send_kot', el('rp-pos-kot').checked ? '1' : '');

        fetch((window.rpAdmin && window.rpAdmin.ajaxUrl) || cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res || !res.success) {
                throw new Error((res && res.data && res.data.message) || 'Could not save the order.');
            }
            el('rp-pos-msg').innerHTML = '<span class="text-success">' + escapeAttr(res.data.message) + '</span>';
            if (payNow) {
                window.location.href = res.data.bill_url;
            } else {
                cart = [];
                renderCart();
                btn.disabled = false;
                btn.textContent = original;
            }
        })
        .catch(function (err) {
            el('rp-pos-msg').innerHTML = '<span class="text-danger">' + escapeAttr(err.message) + '</span>';
            btn.disabled = false;
            btn.textContent = original;
        });
    }

    el('rp-pos-charge').addEventListener('click', function () { submit(true, this); });
    el('rp-pos-hold').addEventListener('click', function () { submit(false, this); });

    /* keyboard: focus search with "/" */
    document.addEventListener('keydown', function (e) {
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if (e.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
            e.preventDefault();
            searchInput.focus();
        }
    });

    recalc();
})();
