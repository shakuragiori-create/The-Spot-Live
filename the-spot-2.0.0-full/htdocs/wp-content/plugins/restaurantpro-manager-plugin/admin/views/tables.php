<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-wrap">
    <h1 class="mb-4">Table Management</h1>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Add New Table</h5>
                    <form method="post">
                        <?php wp_nonce_field( 'rp_add_table' ); ?>
                        <div class="mb-3">
                            <label class="form-label">Table Name</label>
                            <input type="text" class="form-control" name="table_name" placeholder="e.g. Table 1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacity (seats)</label>
                            <input type="number" class="form-control" name="capacity" value="4" min="1" max="50">
                        </div>
                        <button type="submit" name="rp_add_table" class="btn btn-primary w-100">Add Table</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">All Tables</h5>
                    <?php if ( $tables ) : ?>
                        <div class="row g-3">
                            <?php foreach ( $tables as $table ) : ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <h6><?php echo esc_html( $table->table_name ); ?></h6>
                                            <p class="mb-1"><?php echo RP_Helpers::status_badge( $table->status ); ?></p>
                                            <small class="text-muted"><?php echo esc_html( $table->capacity ); ?> seats</small>
                                            <div class="mt-2">
                                                <select class="form-select form-select-sm rp-table-status-select" data-table="<?php echo esc_attr( $table->id ); ?>">
                                                    <?php foreach ( RP_Helpers::get_table_statuses() as $key => $label ) : ?>
                                                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $table->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( "admin.php?page=rp-tables&delete_table={$table->id}" ), 'rp_delete_table' ) ); ?>" class="btn btn-sm btn-outline-danger mt-2" onclick="return confirm('Delete this table?')">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="text-muted">No tables added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
