<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-wrap">

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h1 class="h4 mb-0 me-auto">
            Messages
            <?php if ( $counts['unread'] ) : ?>
                <span class="badge bg-danger align-middle"><?php echo esc_html( $counts['unread'] ); ?> new</span>
            <?php endif; ?>
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-messages' ) ); ?>"
           class="btn btn-sm <?php echo $status_filter ? 'btn-outline-secondary' : 'btn-dark'; ?>">
            All (<?php echo esc_html( $counts['all'] ); ?>)
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-messages&status=unread' ) ); ?>"
           class="btn btn-sm <?php echo $status_filter === 'unread' ? 'btn-dark' : 'btn-outline-secondary'; ?>">Unread</a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-messages&status=read' ) ); ?>"
           class="btn btn-sm <?php echo $status_filter === 'read' ? 'btn-dark' : 'btn-outline-secondary'; ?>">Read</a>
    </div>

    <?php if ( $notice ) : ?>
        <div class="alert alert-success py-2"><?php echo esc_html( $notice ); ?></div>
    <?php endif; ?>

    <?php if ( empty( $messages ) ) : ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <p class="mb-1">No messages yet.</p>
                <p class="small mb-0">Enquiries sent from the website contact form will appear here.</p>
            </div>
        </div>
    <?php else : ?>
        <div class="row g-3">
            <?php foreach ( $messages as $msg ) :
                $unread = ( 'unread' === $msg->status );
                ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 <?php echo $unread ? 'border-start border-4 border-warning' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?php echo esc_html( $msg->name ?: 'Anonymous' ); ?></strong>
                                    <?php if ( $unread ) : ?>
                                        <span class="badge bg-warning text-dark">New</span>
                                    <?php endif; ?>
                                    <div class="small text-muted">
                                        <?php echo esc_html( wp_date( 'd M Y, g:i A', strtotime( $msg->created_at ) ) ); ?>
                                    </div>
                                </div>
                                <div class="text-end small">
                                    <?php if ( $msg->phone ) : ?>
                                        <div><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $msg->phone ) ); ?>"><?php echo esc_html( $msg->phone ); ?></a></div>
                                    <?php endif; ?>
                                    <?php if ( $msg->email ) : ?>
                                        <div><a href="mailto:<?php echo esc_attr( $msg->email ); ?>"><?php echo esc_html( $msg->email ); ?></a></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <p class="mb-3" style="white-space:pre-wrap"><?php echo esc_html( $msg->message ); ?></p>

                            <form method="post" class="d-flex gap-2">
                                <?php wp_nonce_field( 'rp_message_action' ); ?>
                                <input type="hidden" name="message_id" value="<?php echo (int) $msg->id; ?>">
                                <?php if ( $unread ) : ?>
                                    <button type="submit" name="rp_message_action" value="read" class="btn btn-sm btn-outline-success">Mark read</button>
                                <?php else : ?>
                                    <button type="submit" name="rp_message_action" value="unread" class="btn btn-sm btn-outline-secondary">Mark unread</button>
                                <?php endif; ?>
                                <?php if ( $msg->phone ) : ?>
                                    <a class="btn btn-sm btn-outline-dark"
                                       href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $msg->phone ) ); ?>"
                                       target="_blank" rel="noopener">WhatsApp</a>
                                <?php endif; ?>
                                <button type="submit" name="rp_message_action" value="delete" class="btn btn-sm btn-outline-danger ms-auto"
                                        onclick="return confirm('Delete this message permanently?');">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
