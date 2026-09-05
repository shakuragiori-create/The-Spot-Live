<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-wrap rp-pos">

    <?php if ( empty( $items ) ) : ?>
        <div class="alert alert-warning">
            <strong>No menu items yet.</strong>
            Go to <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings&tab=tools' ) ); ?>">Settings → Tools</a>
            and run <em>Import / Sync Menu</em> first.
        </div>
    <?php endif; ?>

    <div class="row g-3">

        <!-- ITEM PICKER -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <h1 class="h4 mb-0 me-auto">Point of Sale</h1>
                        <input type="search" id="rp-pos-search" class="form-control" style="max-width:260px"
                               placeholder="Search items…" autocomplete="off">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="rp-pos-veg">
                            <label class="form-check-label small" for="rp-pos-veg">Veg only</label>
                        </div>
                    </div>

                    <div class="rp-pos-cats mb-3">
                        <button type="button" class="btn btn-sm btn-dark rp-pos-cat" data-cat="">All</button>
                        <?php foreach ( $categories as $cat ) : ?>
                            <?php $icon = (string) get_term_meta( $cat->term_id, '_rp_icon', true ); ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary rp-pos-cat" data-cat="<?php echo esc_attr( $cat->slug ); ?>">
                                <?php echo $icon ? esc_html( $icon ) . ' ' : ''; ?><?php echo esc_html( $cat->name ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="rp-pos-grid" id="rp-pos-grid">
                        <?php foreach ( $items as $item ) :
                            $price = (float) get_post_meta( $item->ID, '_rp_price', true );
                            $veg   = get_post_meta( $item->ID, '_rp_is_veg', true ) === '1';
                            $terms = wp_get_object_terms( $item->ID, 'rp_menu_category', [ 'fields' => 'slugs' ] );
                            $slugs = is_wp_error( $terms ) ? [] : $terms;
                            ?>
                            <button type="button" class="rp-pos-item"
                                    data-id="<?php echo esc_attr( $item->ID ); ?>"
                                    data-name="<?php echo esc_attr( $item->post_title ); ?>"
                                    data-price="<?php echo esc_attr( number_format( $price, 2, '.', '' ) ); ?>"
                                    data-cats="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>"
                                    data-veg="<?php echo $veg ? '1' : '0'; ?>">
                                <span class="rp-pos-item-name">
                                    <?php echo esc_html( $item->post_title ); ?>
                                    <?php if ( $veg ) : ?><span class="rp-pos-veg" title="Vegetarian"></span><?php endif; ?>
                                </span>
                                <span class="rp-pos-item-price"><?php echo esc_html( RP_Helpers::format_price( $price ) ); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-muted small mb-0 mt-3" id="rp-pos-empty" style="display:none">No items match that search.</p>
                </div>
            </div>
        </div>

        <!-- CART -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rp-pos-cart">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <strong>Current Bill</strong>
                    <button type="button" class="btn btn-sm btn-outline-light" id="rp-pos-clear">Clear</button>
                </div>
                <div class="card-body">

                    <div id="rp-cart-rows" class="rp-cart-rows">
                        <p class="text-muted small text-center py-4 mb-0" id="rp-cart-empty">
                            Tap an item on the left to start a bill.
                        </p>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-3" id="rp-cart-custom">
                        + Add custom line
                    </button>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Order type</label>
                            <select class="form-select form-select-sm" id="rp-pos-order-type">
                                <?php foreach ( RP_Billing::order_types() as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Table</label>
                            <select class="form-select form-select-sm" id="rp-pos-table">
                                <option value="0">— none —</option>
                                <?php foreach ( $tables as $table ) : ?>
                                    <option value="<?php echo esc_attr( $table->id ); ?>">
                                        <?php echo esc_html( $table->table_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Customer</label>
                            <input type="text" class="form-control form-control-sm" id="rp-pos-customer" placeholder="Optional">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Phone</label>
                            <input type="tel" class="form-control form-control-sm" id="rp-pos-phone" placeholder="Optional">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Discount</label>
                            <select class="form-select form-select-sm" id="rp-pos-discount-type">
                                <option value="none">No discount</option>
                                <option value="percent">Percent (%)</option>
                                <option value="amount">Amount</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Value</label>
                            <input type="number" class="form-control form-control-sm" id="rp-pos-discount-value"
                                   value="0" min="0" step="0.01" disabled>
                        </div>
                    </div>

                    <div class="rp-pos-charges mb-2">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="form-check form-switch mb-0 flex-grow-1">
                                <input class="form-check-input" type="checkbox" id="rp-pos-service"
                                    <?php checked( $billing['service_enabled'] ); ?>>
                                <label class="form-check-label small" for="rp-pos-service">Service charge</label>
                            </div>
                            <div class="input-group input-group-sm" style="width:96px">
                                <input type="number" class="form-control" id="rp-pos-service-rate"
                                       value="<?php echo esc_attr( $billing['service_rate'] ); ?>" min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check form-switch mb-0 flex-grow-1">
                                <input class="form-check-input" type="checkbox" id="rp-pos-vat"
                                    <?php checked( $billing['vat_enabled'] ); ?>>
                                <label class="form-check-label small" for="rp-pos-vat">VAT</label>
                            </div>
                            <div class="input-group input-group-sm" style="width:96px">
                                <input type="number" class="form-control" id="rp-pos-vat-rate"
                                       value="<?php echo esc_attr( $billing['vat_rate'] ); ?>" min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="rp-pos-totals">
                        <div class="d-flex justify-content-between"><span>Subtotal</span><span id="rp-t-subtotal">—</span></div>
                        <div class="d-flex justify-content-between text-danger" id="rp-t-discount-row" style="display:none">
                            <span>Discount</span><span id="rp-t-discount">—</span>
                        </div>
                        <div class="d-flex justify-content-between" id="rp-t-service-row" style="display:none">
                            <span>Service <span class="text-muted small" id="rp-t-service-rate"></span></span><span id="rp-t-service">—</span>
                        </div>
                        <div class="d-flex justify-content-between" id="rp-t-vat-row" style="display:none">
                            <span>VAT <span class="text-muted small" id="rp-t-vat-rate"></span></span><span id="rp-t-vat">—</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between rp-pos-grand">
                            <strong>TOTAL</strong><strong id="rp-t-total">—</strong>
                        </div>
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-6">
                            <label class="form-label small mb-1">Payment</label>
                            <select class="form-select form-select-sm" id="rp-pos-payment">
                                <?php foreach ( RP_Billing::payment_methods() as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Tendered</label>
                            <input type="number" class="form-control form-control-sm" id="rp-pos-tendered"
                                   min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <p class="mb-0 mt-2 small text-success fw-bold" id="rp-t-change-row" style="display:none">
                        Change due: <span id="rp-t-change"></span>
                    </p>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="rp-pos-kot" checked>
                        <label class="form-check-label small" for="rp-pos-kot">Also send a kitchen ticket (KOT)</label>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="button" class="btn btn-primary btn-lg" id="rp-pos-charge">Charge &amp; Print Bill</button>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="rp-pos-hold">Send to kitchen (unpaid)</button>
                    </div>

                    <p class="small mt-2 mb-0" id="rp-pos-msg"></p>
                </div>
            </div>
        </div>
    </div>
</div>
