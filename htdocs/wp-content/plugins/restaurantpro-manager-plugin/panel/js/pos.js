/**
 * RestaurantPro Panel - Point of Sale
 * Cart management, calculations, and checkout
 */
(function() {
    'use strict';

    var data = window.rpPosData || {};
    var cart = [];

    // DOM elements
    var $ = function(sel) { return document.querySelector(sel); };
    var $$ = function(sel) { return document.querySelectorAll(sel); };

    var searchInput = $('#posSearch');
    var catFilter = $('#catFilter');
    var itemGrid = $('#posItemGrid');
    var noItems = $('#posNoItems');
    var cartItems = $('#cartItems');
    var cartEmpty = $('#cartEmpty');
    var clearBtn = $('#clearCart');
    var customLineBtn = $('#addCustomLine');

    // Totals
    var totalSubtotal = $('#totalSubtotal');
    var totalDiscount = $('#totalDiscount');
    var discountRow = $('#discountRow');
    var totalService = $('#totalService');
    var serviceRow = $('#serviceRow');
    var serviceRateLabel = $('#serviceRateLabel');
    var totalVat = $('#totalVat');
    var vatRow = $('#vatRow');
    var vatRateLabel = $('#vatRateLabel');
    var totalGrand = $('#totalGrand');
    var changeRow = $('#changeRow');
    var changeAmount = $('#changeAmount');

    // Inputs
    var discountType = $('#discountType');
    var discountValue = $('#discountValue');
    var serviceEnabled = $('#serviceEnabled');
    var serviceRate = $('#serviceRate');
    var vatEnabled = $('#vatEnabled');
    var vatRate = $('#vatRate');
    var amountTendered = $('#amountTendered');
    var paymentMethod = $('#paymentMethod');
    var customerName = $('#customerName');
    var customerPhone = $('#customerPhone');
    var orderType = $('#orderType');
    var orderTable = $('#orderTable');
    var sendKot = $('#sendKot');

    // Buttons
    var chargeBtn = $('#chargeBtn');
    var holdBtn = $('#holdBtn');
    var posMsg = $('#posMsg');

    // ========== LIVE POS ORDERS + BILL PREVIEW ==========
    var ongoingWrap = $('#posOngoingOrders'), ongoingCount = $('#posOngoingCount'), activityList = $('#posActivityList'), activityClear = $('#posActivityClear');
    var billModal = $('#posBillModal'), billFrame = $('#posBillFrame'), billClose = $('#posBillClose');
    var liveBusy = false, previousOrderState = {};
    function escLive(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
    function moneyLive(v){return 'Rs.'+Math.round(parseFloat(v)||0).toLocaleString('en-NP');}
    function statusClass(s){return 'pos-status-'+String(s||'new').replace(/[^a-z0-9_-]/gi,'');}
    function typeLabel(s){return ({dine_in:'Dine In',takeaway:'Takeaway',delivery:'Delivery'})[s]||s;}
    function openVisualBill(url){if(!billModal||!billFrame)return;billFrame.src=url+(url.indexOf('?')===-1?'?embedded=1':'&embedded=1');billModal.classList.add('open');billModal.setAttribute('aria-hidden','false');}
    function closeVisualBill(){if(!billModal)return;billModal.classList.remove('open');billModal.setAttribute('aria-hidden','true');if(billFrame)billFrame.src='about:blank';}
    function renderOngoing(orders){if(!ongoingWrap||!ongoingCount)return;ongoingCount.textContent=orders.length;if(!orders.length){ongoingWrap.innerHTML='<div class="pos-live-empty">No orders yet today.</div>';return;}ongoingWrap.innerHTML=orders.map(function(o){var place=o.table_number?'Table '+o.table_number:typeLabel(o.order_type);var customer=o.customer_name?' • '+o.customer_name:'';var paid=o.paid?'PAID':'NOT PAID';return '<div class="pos-live-order '+(o.paid?'is-paid':'is-unpaid')+'"><div class="pos-live-top"><span class="pos-live-invoice">'+escLive(o.invoice_no||('Order #'+o.id))+'</span><span class="pos-status-pill '+statusClass(o.status)+'">'+escLive(o.status_label)+'</span></div><div class="pos-live-meta">'+escLive(o.time_label||'')+' • '+escLive(place)+escLive(customer)+'<br>'+o.item_count+' item(s) • <strong>'+paid+'</strong></div><div class="pos-live-total">'+moneyLive(o.total)+'</div><div class="pos-live-actions"><button type="button" class="btn btn-sm btn-outline" data-live-order="'+o.id+'">View Order</button><button type="button" class="btn btn-sm btn-primary" data-live-bill="'+o.id+'" data-bill-url="'+escLive(o.bill_url)+'">🧾 Bill</button></div></div>';}).join('');ongoingWrap.querySelectorAll('[data-live-bill]').forEach(function(btn){btn.addEventListener('click',function(){openVisualBill(btn.getAttribute('data-bill-url'));});});ongoingWrap.querySelectorAll('[data-live-order]').forEach(function(btn){btn.addEventListener('click',function(){window.location.href=data.panelUrl+'/orders/'+btn.getAttribute('data-live-order');});});}
    function loadOngoingOrders(){if(liveBusy||!data.ajaxUrl)return;liveBusy=true;var body=new URLSearchParams();body.append('action','rp_pos_current_orders');body.append('nonce',data.nonce);fetch(data.ajaxUrl,{method:'POST',credentials:'same-origin',body:body}).then(function(r){return r.json();}).then(function(res){if(!res||!res.success)return;var orders=res.data.orders||[];orders.forEach(function(o){var old=previousOrderState[o.id];if(old&&old!==o.status)addPosActivity({event_type:'status',title:'Order Status Updated',message:(o.invoice_no||('Order #'+o.id))+' is now '+o.status_label+'.',order_id:o.id});previousOrderState[o.id]=o.status;});renderOngoing(orders);}).catch(function(){}).finally(function(){liveBusy=false;});}
    function addPosActivity(ev){if(!activityList||!ev)return;var empty=activityList.querySelector('.pos-live-empty');if(empty)empty.remove();var icons={payment:'dollar-sign',kitchen:'flame',order_kot:'flame',status:'activity',table:'layout',new_order:'receipt'};var row=document.createElement('div');row.className='pos-activity-item';row.innerHTML='<div style="font-size:18px">'+(icons[ev.event_type]||'🔔')+'</div><div><strong>'+escLive(ev.title)+'</strong><div>'+escLive(ev.message)+'</div><small>'+escLive(ev.created_at||'Just now')+'</small></div>';activityList.insertBefore(row,activityList.firstChild);while(activityList.children.length>20)activityList.removeChild(activityList.lastChild);}
    if(billClose)billClose.addEventListener('click',closeVisualBill);if(billModal)billModal.querySelectorAll('[data-close-bill]').forEach(function(el){el.addEventListener('click',closeVisualBill);});document.addEventListener('keydown',function(e){if(e.key==='Escape')closeVisualBill();});if(activityClear)activityClear.addEventListener('click',function(){if(activityList)activityList.innerHTML='<div class="pos-live-empty">Waiting for order updates…</div>';});document.addEventListener('rp:notification',function(e){if(e.detail){addPosActivity(e.detail);setTimeout(loadOngoingOrders,100);}});loadOngoingOrders();setInterval(loadOngoingOrders,5000);

    // ========== ITEM FILTERING ==========

    function filterItems() {
        var search = (searchInput.value || '').toLowerCase().trim();
        var activeCat = catFilter.querySelector('.filter-tab.active');
        var catSlug = activeCat ? activeCat.getAttribute('data-cat') : 'all';

        var visible = 0;
        $$('.pos-item-btn').forEach(function(btn) {
            var name = (btn.getAttribute('data-name') || '').toLowerCase();
            var cats = ' ' + btn.getAttribute('data-cats') + ' ';
            var matchSearch = !search || name.indexOf(search) !== -1;
            var matchCat = catSlug === 'all' || cats.indexOf(' ' + catSlug + ' ') !== -1;
            var show = matchSearch && matchCat;
            btn.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        noItems.style.display = visible === 0 ? '' : 'none';
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterItems);
    }

    if (catFilter) {
        catFilter.addEventListener('click', function(e) {
            var tab = e.target.closest('.filter-tab');
            if (!tab) return;
            $$('.filter-tab').forEach(function(t) { t.classList.remove('active'); });
            tab.classList.add('active');
            filterItems();
        });
    }

    // ========== CART MANAGEMENT ==========

    function addToCart(id, name, price) {
        var found = null;
        cart.forEach(function(item) {
            if (item.id === id && !item.custom) found = item;
        });

        if (found) {
            found.qty++;
        } else {
            cart.push({ id: id, name: name, price: parseFloat(price), qty: 1, custom: false });
        }
        renderCart();
    }

    function addCustomLine() {
        var name = prompt('Item name:');
        if (!name) return;
        var price = parseFloat(prompt('Price:', '0')) || 0;
        cart.push({ id: 'custom-' + Date.now(), name: name, price: price, qty: 1, custom: true });
        renderCart();
    }

    function updateQty(index, delta) {
        cart[index].qty += delta;
        if (cart[index].qty <= 0) {
            cart.splice(index, 1);
        }
        renderCart();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        cart = [];
        renderCart();
    }

    function renderCart() {
        if (cart.length === 0) {
            cartItems.innerHTML = '<p class="text-muted small text-center py-20" id="cartEmpty">Tap items to add to bill</p>';
            updateTotals();
            return;
        }

        var html = '';
        cart.forEach(function(item, i) {
            html += '<div class="cart-item">' +
                '<span class="cart-item-name">' + escapeHtml(item.name) + '</span>' +
                '<span class="cart-item-qty">' +
                    '<button type="button" onclick="window.rpPos.updateQty(' + i + ', -1)">−</button>' +
                    '<span>' + item.qty + '</span>' +
                    '<button type="button" onclick="window.rpPos.updateQty(' + i + ', 1)">+</button>' +
                '</span>' +
                '<span class="cart-item-price">Rs.' + formatNum(item.price * item.qty) + '</span>' +
                '<span class="cart-item-remove" onclick="window.rpPos.removeItem(' + i + ')">×</span>' +
            '</div>';
        });

        cartItems.innerHTML = html;
        updateTotals();
    }

    function updateTotals() {
        var subtotal = 0;
        cart.forEach(function(item) { subtotal += item.price * item.qty; });

        var discountAmt = 0;
        var dType = discountType.value;
        var dVal = parseFloat(discountValue.value) || 0;

        if (dType === 'percent' && dVal > 0) {
            discountAmt = subtotal * (dVal / 100);
        } else if (dType === 'amount' && dVal > 0) {
            discountAmt = Math.min(dVal, subtotal);
        }

        var afterDiscount = subtotal - discountAmt;
        var serviceAmt = 0;
        var sRate = parseFloat(serviceRate.value) || 0;
        if (serviceEnabled.checked && sRate > 0) {
            serviceAmt = afterDiscount * (sRate / 100);
        }

        var afterService = afterDiscount + serviceAmt;
        var vatAmt = 0;
        var vRate = parseFloat(vatRate.value) || 0;
        if (vatEnabled.checked && vRate > 0) {
            vatAmt = afterService * (vRate / 100);
        }

        var grand = afterService + vatAmt;
        var tendered = parseFloat(amountTendered.value) || 0;
        var change = tendered > grand ? tendered - grand : 0;

        // Update display
        totalSubtotal.textContent = 'Rs.' + formatNum(subtotal);

        if (discountAmt > 0) {
            discountRow.style.display = '';
            totalDiscount.textContent = '-Rs.' + formatNum(discountAmt);
        } else {
            discountRow.style.display = 'none';
        }

        if (serviceAmt > 0) {
            serviceRow.style.display = '';
            serviceRateLabel.textContent = '(' + sRate + '%)';
            totalService.textContent = 'Rs.' + formatNum(serviceAmt);
        } else {
            serviceRow.style.display = 'none';
        }

        if (vatAmt > 0) {
            vatRow.style.display = '';
            vatRateLabel.textContent = '(' + vRate + '%)';
            totalVat.textContent = 'Rs.' + formatNum(vatAmt);
        } else {
            vatRow.style.display = 'none';
        }

        totalGrand.textContent = 'Rs.' + formatNum(grand);

        if (change > 0) {
            changeRow.style.display = '';
            changeAmount.textContent = 'Rs.' + formatNum(change);
        } else {
            changeRow.style.display = 'none';
        }
    }

    // ========== CHECKOUT ==========

    function submitOrder(payNow) {
        if (cart.length === 0) {
            showMessage('Add items to the cart first.', 'error');
            return;
        }

        var items = cart.map(function(item) {
            return {
                menu_item_id: item.custom ? 0 : item.id,
                item_name: item.name,
                quantity: item.qty,
                price: item.price,
                notes: ''
            };
        });

        var payload = {
            action: 'rp_pos_create_order',
            nonce: data.nonce,
            items: JSON.stringify(items),
            order_type: orderType.value,
            table_id: orderTable.value,
            customer_name: customerName.value,
            customer_phone: customerPhone.value,
            discount_type: discountType.value,
            discount_value: discountValue.value,
            service_enabled: serviceEnabled.checked ? '1' : '0',
            service_rate: serviceRate.value,
            vat_enabled: vatEnabled.checked ? '1' : '0',
            vat_rate: vatRate.value,
            payment_method: paymentMethod.value,
            amount_paid: payNow ? amountTendered.value : '0',
            pay_now: payNow ? '1' : '0',
            send_kot: sendKot.checked ? '1' : '0',
            notes: ''
        };

        chargeBtn.disabled = true;
        holdBtn.disabled = true;
        chargeBtn.textContent = 'Processing...';
        posMsg.textContent = '';

        fetch(data.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: encodePayload(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                showMessage(res.data.message, 'success');
                cart = [];
                renderCart();

                if (payNow && res.data.bill_url) {
                    // Open bill in new tab for printing
                    window.open(res.data.bill_url, '_blank');
                }

                setTimeout(function() {
                    window.location.href = data.panelUrl + '/orders';
                }, 1500);
            } else {
                var msg = res.data && res.data.message ? res.data.message : 'Could not save order.';
                showMessage(msg, 'error');
                // A stale security token (very common when the POS tab has
                // been left open since the previous shift) looks identical
                // to a broken "Charge" button from the cashier's side. Reload
                // automatically so the page grabs a fresh token instead of
                // leaving staff stuck on a screen that silently keeps failing.
                if (/security check failed/i.test(msg)) {
                    showMessage(msg + ' Reloading…', 'error');
                    setTimeout(function() { window.location.reload(); }, 1500);
                }
            }
        })
        .catch(function() {
            showMessage('Connection error. Please try again.', 'error');
        })
        .then(function() {
            chargeBtn.disabled = false;
            holdBtn.disabled = false;
            chargeBtn.textContent = '💰 Charge & Print Bill';
        });
    }

    function encodePayload(obj) {
        var parts = [];
        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]));
            }
        }
        return parts.join('&');
    }

    function showMessage(msg, type) {
        posMsg.textContent = msg;
        posMsg.className = 'small mt-8 mb-0 ' + (type === 'error' ? 'text-danger' : 'text-success');
    }

    // ========== HELPERS ==========

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatNum(n) {
        return Math.round(n).toLocaleString('en-NP');
    }

    // ========== EVENT BINDINGS ==========

    if (itemGrid) {
        itemGrid.addEventListener('click', function(e) {
            var btn = e.target.closest('.pos-item-btn');
            if (!btn) return;
            addToCart(
                btn.getAttribute('data-id'),
                btn.getAttribute('data-name'),
                btn.getAttribute('data-price')
            );
        });
    }

    if (customLineBtn) {
        customLineBtn.addEventListener('click', addCustomLine);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', clearCart);
    }

    if (chargeBtn) {
        chargeBtn.addEventListener('click', function() { submitOrder(true); });
    }

    if (holdBtn) {
        holdBtn.addEventListener('click', function() { submitOrder(false); });
    }

    // Re-calculate on input change
    [discountType, discountValue, serviceEnabled, serviceRate, vatEnabled, vatRate, amountTendered].forEach(function(el) {
        if (el) el.addEventListener('input', updateTotals);
    });

    // Discount toggle
    if (discountType) {
        discountType.addEventListener('change', function() {
            discountValue.disabled = discountType.value === 'none';
            updateTotals();
        });
    }

    // Expose for inline handlers
    window.rpPos = {
        updateQty: updateQty,
        removeItem: removeItem
    };

})();
