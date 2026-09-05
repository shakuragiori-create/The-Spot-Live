<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$reservations = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}rp_reservations ORDER BY reservation_date DESC, reservation_time DESC LIMIT 50"
);
?>

<h1 class="page-title">Reservations</h1>

<div class="p-card" style="padding:12px 16px">
    <?php if ( $reservations ) : ?>
        <?php foreach ( $reservations as $r ) : ?>
            <div style="padding:14px 0;border-bottom:1px solid #f0f0f0">
                <div class="flex-between mb-12">
                    <div>
                        <div class="fw-700" style="font-size:.92rem"><?php echo esc_html( $r->guest_name ); ?></div>
                        <div class="text-sm text-muted"><?php echo esc_html( $r->phone ); ?> <?php echo $r->email ? '· ' . esc_html( $r->email ) : ''; ?></div>
                    </div>
                    <span class="badge badge-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( ucfirst( $r->status ) ); ?></span>
                </div>
                <div style="display:flex;gap:16px;font-size:.82rem;color:var(--g);margin-bottom:8px">
                    <span>📅 <?php echo esc_html( date( 'M j, Y', strtotime( $r->reservation_date ) ) ); ?></span>
                    <span>⏰ <?php echo esc_html( date( 'g:i A', strtotime( $r->reservation_time ) ) ); ?></span>
                    <span>👥 <?php echo esc_html( $r->guests ); ?> guests</span>
                </div>
                <?php if ( $r->notes ) : ?>
                    <div class="text-sm text-muted" style="margin-bottom:8px">📝 <?php echo esc_html( $r->notes ); ?></div>
                <?php endif; ?>
                <?php if ( $r->status === 'pending' ) : ?>
                    <div style="display:flex;gap:8px">
                        <form method="post" style="display:inline">
                            <?php wp_nonce_field( 'rp_reservations_action' ); ?>
                            <input type="hidden" name="rp_res_action" value="confirm">
                            <input type="hidden" name="reservation_id" value="<?php echo esc_attr( $r->id ); ?>">
                            <button class="btn btn-sm btn-success">Confirm</button>
                        </form>
                        <form method="post" style="display:inline">
                            <?php wp_nonce_field( 'rp_reservations_action' ); ?>
                            <input type="hidden" name="rp_res_action" value="cancel">
                            <input type="hidden" name="reservation_id" value="<?php echo esc_attr( $r->id ); ?>">
                            <button class="btn btn-sm btn-danger">Cancel</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="empty-state">
            <div class="empty-icon">📅</div>
            <p>No reservations yet</p>
        </div>
    <?php endif; ?>
</div>
