<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $order ? 'Order #' . esc_html( $order->id ) : 'New Order'; ?></h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-orders' ) ); ?>" class="btn btn-outline-secondary">Back to Orders</a>
    </div>

    <?php if ( $order ) : ?>
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Order Items</h5>
                        <table class="table">
                            <thead>
                                <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $order_items as $item ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $item->post_title ); ?></td>
                                        <td><?php echo esc_html( $item->quantity ); ?></td>
                                        <td><?php echo esc_html( RP_Helpers::format_price( (float) $item->price ) ); ?></td>
                                        <td><?php echo esc_html( RP_Helpers::format_price( (float) $item->price * (int) $item->quantity ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr><th colspan="3">Total</th><th><?php echo esc_html( RP_Helpers::format_price( (float) $order->total ) ); ?></th></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Order Details</h5>
                        <p><strong>Status:</strong> <?php echo RP_Helpers::status_badge( $order->status ); ?></p>
                        <p><strong>Table:</strong> <?php echo $order->table_number ? 'Table ' . esc_html( $order->table_number ) : 'N/A'; ?></p>
                        <p><strong>Created:</strong> <?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $order->created_at ) ) ); ?></p>
                        <?php if ( $order->notes ) : ?>
                            <p><strong>Notes:</strong> <?php echo esc_html( $order->notes ); ?></p>
                        <?php endif; ?>

                        <?php if ( current_user_can( 'rp_manage_orders' ) && ! in_array( $order->status, [ 'completed', 'cancelled' ], true ) ) : ?>
                            <hr>
                            <label class="form-label"><strong>Update Status:</strong></label>
                            <select class="form-select mb-2" id="rp-order-status-select">
                                <?php foreach ( RP_Helpers::get_order_statuses() as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $order->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary w-100 rp-update-order-status" data-order="<?php echo esc_attr( $order->id ); ?>">Update Status</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form id="rp-new-order-form">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Table</label>
                            <select class="form-select" name="table_number" id="rp-order-table">
                                <option value="0">No table (Takeaway)</option>
                                <?php foreach ( $tables as $table ) : ?>
                                    <option value="<?php echo esc_attr( $table->id ); ?>"><?php echo esc_html( $table->table_name ); ?> (<?php echo esc_html( $table->capacity ); ?> seats)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" name="notes" id="rp-order-notes" placeholder="Special instructions...">
                        </div>
                    </div>

                    <h5>Add Items</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <select class="form-select" id="rp-item-select">
                                <option value="">Select menu item...</option>
                                <?php foreach ( $menu_items as $mi ) : ?>
                                    <option value="<?php echo esc_attr( $mi->ID ); ?>" data-price="<?php echo esc_attr( get_post_meta( $mi->ID, '_rp_price', true ) ); ?>">
                                        <?php echo esc_html( $mi->post_title ); ?> — <?php echo esc_html( RP_Helpers::format_price( (float) get_post_meta( $mi->ID, '_rp_price', true ) ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control" id="rp-item-qty" value="1" min="1" placeholder="Qty">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary w-100" id="rp-add-item-btn">Add Item</button>
                        </div>
                    </div>

                    <table class="table" id="rp-order-items-table">
                        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th><th></th></tr></thead>
                        <tbody></tbody>
                        <tfoot><tr><th colspan="3">Total</th><th id="rp-order-total"><?php echo esc_html( RP_Helpers::format_price( 0 ) ); ?></th><th></th></tr></tfoot>
                    </table>

                    <button type="submit" class="btn btn-success btn-lg">Create Order</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
