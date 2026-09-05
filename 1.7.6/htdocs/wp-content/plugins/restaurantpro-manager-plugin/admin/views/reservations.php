<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-wrap">
    <h1 class="mb-4">Reservations</h1>

    <div class="mb-3">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-reservations' ) ); ?>" class="btn btn-sm <?php echo ! $status_filter ? 'btn-dark' : 'btn-outline-secondary'; ?>">All</a>
        <?php foreach ( RP_Helpers::get_reservation_statuses() as $key => $label ) : ?>
            <a href="<?php echo esc_url( admin_url( "admin.php?page=rp-reservations&status={$key}" ) ); ?>" class="btn btn-sm <?php echo $status_filter === $key ? 'btn-dark' : 'btn-outline-secondary'; ?>"><?php echo esc_html( $label ); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Guest</th>
                            <th>Date & Time</th>
                            <th>Guests</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $reservations ) : ?>
                            <?php foreach ( $reservations as $res ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $res->id ); ?></td>
                                    <td>
                                        <strong><?php echo esc_html( $res->guest_name ); ?></strong>
                                        <?php if ( $res->email ) : ?><br><small class="text-muted"><?php echo esc_html( $res->email ); ?></small><?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $res->reservation_date ) ) ); ?><br><small><?php echo esc_html( wp_date( 'g:i A', strtotime( $res->reservation_time ) ) ); ?></small></td>
                                    <td><?php echo esc_html( $res->guests ); ?></td>
                                    <td><?php echo esc_html( $res->phone ); ?></td>
                                    <td><?php echo RP_Helpers::status_badge( $res->status ); ?></td>
                                    <td>
                                        <?php if ( $res->status === 'pending' ) : ?>
                                            <form method="post" class="d-inline">
                                                <?php wp_nonce_field( 'rp_update_reservation' ); ?>
                                                <input type="hidden" name="reservation_id" value="<?php echo esc_attr( $res->id ); ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" name="rp_update_reservation" class="btn btn-sm btn-success">Confirm</button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <?php wp_nonce_field( 'rp_update_reservation' ); ?>
                                                <input type="hidden" name="reservation_id" value="<?php echo esc_attr( $res->id ); ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="rp_update_reservation" class="btn btn-sm btn-danger">Cancel</button>
                                            </form>
                                        <?php elseif ( $res->status === 'confirmed' ) : ?>
                                            <form method="post" class="d-inline">
                                                <?php wp_nonce_field( 'rp_update_reservation' ); ?>
                                                <input type="hidden" name="reservation_id" value="<?php echo esc_attr( $res->id ); ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="rp_update_reservation" class="btn btn-sm btn-outline-danger">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No reservations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
