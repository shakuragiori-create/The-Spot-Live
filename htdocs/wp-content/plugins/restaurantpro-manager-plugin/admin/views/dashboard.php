<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-wrap">
    <h1 class="mb-4">RestaurantPro Dashboard</h1>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-1"><?php echo esc_html( $today_orders ); ?></h3>
                    <p class="text-muted mb-0">Today's Orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1"><?php echo esc_html( RP_Helpers::format_price( $today_revenue ) ); ?></h3>
                    <p class="text-muted mb-0">Today's Revenue</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-1"><?php echo esc_html( $pending_kots ); ?></h3>
                    <p class="text-muted mb-0">Pending KOTs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-info mb-1"><?php echo esc_html( $pending_reservations ); ?></h3>
                    <p class="text-muted mb-0">Pending Reservations</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Orders Today</h5>
                    <canvas id="rpOrdersChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Popular Items</h5>
                    <?php if ( $popular_items ) : ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ( $popular_items as $item ) : ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <?php echo esc_html( $item->post_title ); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo esc_html( $item->total_qty ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="text-muted">No orders yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Recent Orders</h5>
                    <?php if ( $recent_orders ) : ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Table</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $recent_orders as $order ) : ?>
                                        <tr>
                                            <td><?php echo esc_html( $order->id ); ?></td>
                                            <td><?php echo $order->table_number ? 'Table ' . esc_html( $order->table_number ) : '—'; ?></td>
                                            <td><?php echo RP_Helpers::status_badge( $order->status ); ?></td>
                                            <td><?php echo esc_html( RP_Helpers::format_price( (float) $order->total ) ); ?></td>
                                            <td><?php echo esc_html( RP_Helpers::time_ago( $order->created_at ) ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="text-muted">No orders today.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
