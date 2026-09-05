<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$methods    = RP_Billing::payment_methods();
$types      = RP_Billing::order_types();
$can_edit   = current_user_can( 'rp_manage_orders' );
$is_paid    = in_array( $order->status, [ 'completed' ], true ) || (float) $order->amount_paid > 0;
$created_ts = strtotime( $order->created_at );
?>
<div class="wrap rp-wrap rp-bill-wrap">

    <div class="rp-noprint">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h1 class="h4 mb-0 me-auto">
                Bill <?php echo esc_html( $order->invoice_no ); ?>
                <?php echo RP_Helpers::status_badge( $order->status ); ?>
            </h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-orders' ) ); ?>" class="btn btn-sm btn-outline-secondary">← All orders</a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-pos' ) ); ?>" class="btn btn-sm btn-outline-dark">New sale</a>
            <button type="button" class="btn btn-sm btn-primary" id="rp-print-80">🧾 Print 80mm</button>
            <button type="button" class="btn btn-sm btn-dark" id="rp-print-a4">📄 Print A4</button>
        </div>

        <?php if ( $saved_notice ) : ?>
            <div class="alert alert-success py-2"><?php echo esc_html( $saved_notice ); ?></div>
        <?php endif; ?>
    </div>

    <div class="row g-4">

        <!-- ================= EDITOR (screen only) ================= -->
        <?php if ( $can_edit ) : ?>
        <div class="col-xl-7 rp-noprint">
            <form method="post" class="card border-0 shadow-sm">
                <?php wp_nonce_field( 'rp_save_bill_' . $order->id ); ?>
                <div class="card-header bg-white"><strong>Edit this bill before printing</strong></div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:45%">Item</th>
                                    <th style="width:90px">Qty</th>
                                    <th style="width:130px">Rate</th>
                                    <th style="width:60px" class="text-center">Del</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $items as $i => $item ) : ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="line_item_id[<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $item->menu_item_id ); ?>">
                                            <input type="text" class="form-control form-control-sm" name="line_name[<?php echo (int) $i; ?>]"
                                                   value="<?php echo esc_attr( $item->display_name ); ?>">
                                        </td>
                                        <td><input type="number" class="form-control form-control-sm" name="line_qty[<?php echo (int) $i; ?>]"
                                                   value="<?php echo esc_attr( $item->quantity ); ?>" min="1" step="1"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="line_price[<?php echo (int) $i; ?>]"
                                                   value="<?php echo esc_attr( number_format( (float) $item->price, 2, '.', '' ) ); ?>" min="0" step="0.01"></td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input" name="line_remove[<?php echo (int) $i; ?>]" value="1"
                                                   title="Remove this line on save">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-light">
                                    <td><input type="text" class="form-control form-control-sm" name="new_line_name" placeholder="+ add a line…"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="new_line_qty" value="1" min="1" step="1"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="new_line_price" value="0.00" min="0" step="0.01"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Discount type</label>
                            <select class="form-select form-select-sm" name="discount_type">
                                <option value="none" <?php selected( $order->discount_type, 'none' ); ?>>No discount</option>
                                <option value="percent" <?php selected( $order->discount_type, 'percent' ); ?>>Percent (%)</option>
                                <option value="amount" <?php selected( $order->discount_type, 'amount' ); ?>>Amount</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Discount value</label>
                            <input type="number" class="form-control form-control-sm" name="discount_value"
                                   value="<?php echo esc_attr( number_format( (float) $order->discount_value, 2, '.', '' ) ); ?>" min="0" step="0.01">
                        </div>

                        <div class="col-sm-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="service_enabled" id="bill_service" value="1"
                                    <?php checked( (float) $order->service_rate > 0 ); ?>>
                                <label class="form-check-label small" for="bill_service">Service charge</label>
                            </div>
                            <div class="input-group input-group-sm mt-1">
                                <input type="number" class="form-control" name="service_rate" min="0" max="100" step="0.01"
                                       value="<?php echo esc_attr( (float) $order->service_rate > 0 ? number_format( (float) $order->service_rate, 2, '.', '' ) : RP_Settings::get( 'service_rate', '10' ) ); ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="vat_enabled" id="bill_vat" value="1"
                                    <?php checked( (float) $order->vat_rate > 0 ); ?>>
                                <label class="form-check-label small" for="bill_vat">VAT</label>
                            </div>
                            <div class="input-group input-group-sm mt-1">
                                <input type="number" class="form-control" name="vat_rate" min="0" max="100" step="0.01"
                                       value="<?php echo esc_attr( (float) $order->vat_rate > 0 ? number_format( (float) $order->vat_rate, 2, '.', '' ) : RP_Settings::get( 'vat_rate', '13' ) ); ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Customer name</label>
                            <input type="text" class="form-control form-control-sm" name="customer_name" value="<?php echo esc_attr( $order->customer_name ); ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Customer phone</label>
                            <input type="text" class="form-control form-control-sm" name="customer_phone" value="<?php echo esc_attr( $order->customer_phone ); ?>">
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Payment method</label>
                            <select class="form-select form-select-sm" name="payment_method">
                                <?php foreach ( $methods as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $order->payment_method, $key ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Amount tendered</label>
                            <input type="number" class="form-control form-control-sm" name="amount_paid" min="0" step="0.01"
                                   value="<?php echo esc_attr( number_format( (float) $order->amount_paid, 2, '.', '' ) ); ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1">Note on the bill (optional)</label>
                            <textarea class="form-control form-control-sm" name="notes" rows="2"><?php echo esc_textarea( $order->notes ); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex gap-2">
                    <button type="submit" name="rp_save_bill" value="1" class="btn btn-primary">Save changes</button>
                    <span class="text-muted small align-self-center">Totals are recalculated on save.</span>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ================= PRINTABLE RECEIPT ================= -->
        <div class="<?php echo $can_edit ? 'col-xl-5' : 'col-12'; ?>">
            <div id="rp-bill">
                <div class="rp-bill-head">
                    <div class="rp-bill-shop"><?php echo esc_html( $shop['name'] ); ?></div>
                    <?php if ( $shop['address'] ) : ?><div class="rp-bill-line"><?php echo esc_html( $shop['address'] ); ?></div><?php endif; ?>
                    <?php if ( $shop['phone'] ) : ?><div class="rp-bill-line">Tel: <?php echo esc_html( $shop['phone'] ); ?></div><?php endif; ?>
                    <div class="rp-bill-doc">TAX INVOICE</div>
                </div>

                <div class="rp-bill-meta">
                    <div><span>Invoice</span><strong><?php echo esc_html( $order->invoice_no ); ?></strong></div>
                    <div><span>Date</span><strong><?php echo esc_html( wp_date( 'd M Y, g:i A', $created_ts ) ); ?></strong></div>
                    <div><span>Type</span><strong><?php echo esc_html( $types[ $order->order_type ] ?? 'Dine In' ); ?></strong></div>
                    <?php if ( $order->table_name ) : ?>
                        <div><span>Table</span><strong><?php echo esc_html( $order->table_name ); ?></strong></div>
                    <?php endif; ?>
                    <?php if ( $order->customer_name ) : ?>
                        <div><span>Customer</span><strong><?php echo esc_html( $order->customer_name ); ?></strong></div>
                    <?php endif; ?>
                    <?php if ( $order->customer_phone ) : ?>
                        <div><span>Phone</span><strong><?php echo esc_html( $order->customer_phone ); ?></strong></div>
                    <?php endif; ?>
                    <?php if ( $order->creator_name ) : ?>
                        <div><span>Cashier</span><strong><?php echo esc_html( $order->creator_name ); ?></strong></div>
                    <?php endif; ?>
                </div>

                <table class="rp-bill-items">
                    <thead>
                        <tr>
                            <th class="rp-c-item">Item</th>
                            <th class="rp-c-qty">Qty</th>
                            <th class="rp-c-rate">Rate</th>
                            <th class="rp-c-amt">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $items as $item ) : ?>
                            <tr>
                                <td class="rp-c-item"><?php echo esc_html( $item->display_name ); ?></td>
                                <td class="rp-c-qty"><?php echo esc_html( $item->quantity ); ?></td>
                                <td class="rp-c-rate"><?php echo esc_html( number_format( (float) $item->price, 2 ) ); ?></td>
                                <td class="rp-c-amt"><?php echo esc_html( number_format( (float) $item->price * (int) $item->quantity, 2 ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="rp-bill-sums">
                    <div><span>Subtotal</span><span><?php echo esc_html( RP_Helpers::format_price( (float) $order->subtotal ) ); ?></span></div>

                    <?php if ( (float) $order->discount_amount > 0 ) : ?>
                        <div><span>Discount<?php echo $order->discount_type === 'percent' ? ' (' . esc_html( rtrim( rtrim( number_format( (float) $order->discount_value, 2 ), '0' ), '.' ) ) . '%)' : ''; ?></span>
                             <span>− <?php echo esc_html( RP_Helpers::format_price( (float) $order->discount_amount ) ); ?></span></div>
                    <?php endif; ?>

                    <?php if ( (float) $order->service_amount > 0 ) : ?>
                        <div><span>Service charge (<?php echo esc_html( rtrim( rtrim( number_format( (float) $order->service_rate, 2 ), '0' ), '.' ) ); ?>%)</span>
                             <span><?php echo esc_html( RP_Helpers::format_price( (float) $order->service_amount ) ); ?></span></div>
                    <?php endif; ?>

                    <?php if ( (float) $order->vat_amount > 0 ) : ?>
                        <div><span>VAT (<?php echo esc_html( rtrim( rtrim( number_format( (float) $order->vat_rate, 2 ), '0' ), '.' ) ); ?>%)</span>
                             <span><?php echo esc_html( RP_Helpers::format_price( (float) $order->vat_amount ) ); ?></span></div>
                    <?php endif; ?>

                    <div class="rp-bill-grand"><span>GRAND TOTAL</span><span><?php echo esc_html( RP_Helpers::format_price( (float) $order->total ) ); ?></span></div>

                    <?php if ( (float) $order->amount_paid > 0 ) : ?>
                        <div><span>Paid (<?php echo esc_html( $methods[ $order->payment_method ] ?? 'Cash' ); ?>)</span>
                             <span><?php echo esc_html( RP_Helpers::format_price( (float) $order->amount_paid ) ); ?></span></div>
                    <?php endif; ?>
                    <?php if ( (float) $order->change_due > 0 ) : ?>
                        <div><span>Change</span><span><?php echo esc_html( RP_Helpers::format_price( (float) $order->change_due ) ); ?></span></div>
                    <?php endif; ?>
                </div>

                <?php if ( $is_paid ) : ?>
                    <div class="rp-bill-stamp">PAID</div>
                <?php endif; ?>

                <?php if ( $order->notes ) : ?>
                    <div class="rp-bill-note"><?php echo nl2br( esc_html( $order->notes ) ); ?></div>
                <?php endif; ?>

                <?php if ( $shop['footer'] ) : ?>
                    <div class="rp-bill-foot"><?php echo nl2br( esc_html( $shop['footer'] ) ); ?></div>
                <?php endif; ?>
                <div class="rp-bill-foot rp-bill-tiny">
                    <?php echo esc_html( wp_date( 'd M Y g:i A' ) ); ?> · Order #<?php echo (int) $order->id; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function printAs(mode) {
        document.body.classList.remove('rp-print-80', 'rp-print-a4');
        document.body.classList.add(mode === 'a4' ? 'rp-print-a4' : 'rp-print-80');
        window.setTimeout(function () { window.print(); }, 60);
    }
    var b80 = document.getElementById('rp-print-80');
    var ba4 = document.getElementById('rp-print-a4');
    if (b80) { b80.addEventListener('click', function () { printAs('80'); }); }
    if (ba4) { ba4.addEventListener('click', function () { printAs('a4'); }); }

    var auto = <?php echo wp_json_encode( $autoprint ); ?>;
    if (auto) { window.setTimeout(function () { printAs(auto === 'a4' ? 'a4' : '80'); }, 500); }
})();
</script>
