<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$tables = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rp_tables ORDER BY table_name ASC" );
?>

<div class="flex-between mb-16">
    <h1 class="page-title" style="margin-bottom:0">Tables</h1>
</div>

<div class="tables-grid">
    <?php foreach ( $tables as $table ) : ?>
        <div class="table-card">
            <div class="table-card-name"><?php echo esc_html( $table->table_name ); ?></div>
            <div class="table-card-capacity"><?php echo esc_html( $table->capacity ); ?> seats</div>
            <span class="badge badge-<?php echo esc_attr( $table->status ); ?>"><?php echo esc_html( ucfirst( $table->status ) ); ?></span>
            <form method="post" style="margin-top:10px">
                <?php wp_nonce_field( 'rp_tables_action' ); ?>
                <input type="hidden" name="rp_table_action" value="status">
                <input type="hidden" name="table_id" value="<?php echo esc_attr( $table->id ); ?>">
                <select name="status" class="form-select" style="font-size:.78rem;padding:6px 10px;border-radius:8px" onchange="this.form.submit()">
                    <option value="available" <?php selected( $table->status, 'available' ); ?>>Available</option>
                    <option value="occupied" <?php selected( $table->status, 'occupied' ); ?>>Occupied</option>
                    <option value="reserved" <?php selected( $table->status, 'reserved' ); ?>>Reserved</option>
                </select>
            </form>
            <?php if ( $role === 'admin' ) : ?>
                <form method="post" style="margin-top:6px">
                    <?php wp_nonce_field( 'rp_tables_action' ); ?>
                    <input type="hidden" name="rp_table_action" value="delete">
                    <input type="hidden" name="table_id" value="<?php echo esc_attr( $table->id ); ?>">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this table?')">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ( $role === 'admin' ) : ?>
<div class="p-card" style="margin-top:20px">
    <h3 class="p-card-title mb-12">Add Table</h3>
    <form method="post">
        <?php wp_nonce_field( 'rp_tables_action' ); ?>
        <input type="hidden" name="rp_table_action" value="add">
        <div class="form-group">
            <label class="form-label">Table Name</label>
            <input type="text" name="table_name" class="form-input" placeholder="e.g. Table 7" required>
        </div>
        <div class="form-group">
            <label class="form-label">Capacity</label>
            <input type="number" name="capacity" class="form-input" value="4" min="1" max="20">
        </div>
        <button type="submit" class="btn btn-primary">Add Table</button>
    </form>
</div>
<?php endif; ?>
