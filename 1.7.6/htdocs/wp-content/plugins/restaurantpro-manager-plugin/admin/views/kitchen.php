<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Kitchen / KOT Display</h1>
        <span class="badge bg-dark" id="rp-kitchen-refresh-indicator">Auto-refresh: 10s</span>
    </div>

    <div class="mb-3">
        <button class="btn btn-sm btn-warning rp-kitchen-filter active" data-filter="all">All</button>
        <button class="btn btn-sm btn-outline-warning rp-kitchen-filter" data-filter="pending">Pending</button>
        <button class="btn btn-sm btn-outline-warning rp-kitchen-filter" data-filter="preparing">Preparing</button>
        <button class="btn btn-sm btn-outline-warning rp-kitchen-filter" data-filter="ready">Ready</button>
    </div>

    <div class="row g-3" id="rp-kitchen-board">
        <?php if ( $kots ) : ?>
            <?php foreach ( $kots as $kot ) : ?>
                <div class="col-md-4 col-lg-3 rp-kot-card" data-status="<?php echo esc_attr( $kot->status ); ?>">
                    <div class="card border-0 shadow-sm h-100 <?php echo $kot->status === 'pending' ? 'border-start border-4 border-warning' : ( $kot->status === 'preparing' ? 'border-start border-4 border-info' : 'border-start border-4 border-success' ); ?>">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <strong>KOT #<?php echo esc_html( $kot->id ); ?></strong>
                            <?php echo RP_Helpers::status_badge( $kot->status ); ?>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><small class="text-muted">Order #<?php echo esc_html( $kot->order_id ); ?> | Table <?php echo esc_html( $kot->table_number ?: 'Takeaway' ); ?></small></p>
                            <ul class="list-unstyled mb-2">
                                <?php foreach ( $kot->items as $item ) : ?>
                                    <li class="py-1 border-bottom">
                                        <strong><?php echo esc_html( $item->quantity ); ?>x</strong> <?php echo esc_html( $item->post_title ); ?>
                                        <?php if ( $item->notes ) : ?><br><small class="text-muted"><?php echo esc_html( $item->notes ); ?></small><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ( $kot->order_notes ) : ?>
                                <p class="mb-0"><small class="text-danger"><strong>Note:</strong> <?php echo esc_html( $kot->order_notes ); ?></small></p>
                            <?php endif; ?>
                            <p class="mb-0 mt-2"><small class="text-muted"><?php echo esc_html( RP_Helpers::time_ago( $kot->created_at ) ); ?></small></p>
                        </div>
                        <?php if ( current_user_can( 'rp_manage_kot' ) ) : ?>
                            <div class="card-footer bg-transparent">
                                <?php if ( $kot->status === 'pending' ) : ?>
                                    <button class="btn btn-sm btn-info w-100 rp-kot-action" data-kot="<?php echo esc_attr( $kot->id ); ?>" data-status="preparing">Start Preparing</button>
                                <?php elseif ( $kot->status === 'preparing' ) : ?>
                                    <button class="btn btn-sm btn-success w-100 rp-kot-action" data-kot="<?php echo esc_attr( $kot->id ); ?>" data-status="ready">Mark Ready</button>
                                <?php elseif ( $kot->status === 'ready' ) : ?>
                                    <button class="btn btn-sm btn-dark w-100 rp-kot-action" data-kot="<?php echo esc_attr( $kot->id ); ?>" data-status="completed">Complete</button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-12">
                <div class="alert alert-success">All clear! No pending kitchen orders.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
