<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'rp_view_reports' ) ) {
    echo '<p class="text-danger">Access denied.</p>';
    return;
}

global $wpdb;

// Date filters
$today = current_time( 'Y-m-d' );
$from = sanitize_text_field( $_GET['from'] ?? $today );
$to = sanitize_text_field( $_GET['to'] ?? $today );
$filter_table = absint( $_GET['table_id'] ?? 0 );

if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) $from = $today;
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) $to = $today;

// Presets
$preset = sanitize_text_field( $_GET['preset'] ?? '' );
switch ( $preset ) {
    case 'today': $from = $to = $today; break;
    case 'yesterday': $from = $to = date( 'Y-m-d', strtotime( '-1 day', strtotime( $today ) ) ); break;
    case 'week': $to = $today; $from = date( 'Y-m-d', strtotime( '-6 days', strtotime( $today ) ) ); break;
    case 'month': $from = date( 'Y-m-01', strtotime( $today ) ); $to = $today; break;
}

// Get all tables for filter
$tables = $wpdb->get_results( "SELECT id, table_name FROM {$wpdb->prefix}rp_tables ORDER BY table_name" );

// Fetch completed orders
$where = "status = 'completed' AND DATE(created_at) BETWEEN %s AND %s";
$params = [ $from, $to ];

if ( $filter_table > 0 ) {
    $where .= " AND table_number = %d";
    $params[] = $filter_table;
}

$orders = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}rp_orders WHERE {$where} ORDER BY created_at DESC",
    ...$params
) );

// Calculate totals
$totals = [
    'revenue' => 0,
    'subtotal' => 0,
    'discount' => 0,
    'service' => 0,
    'vat' => 0,
    'orders' => count( $orders ),
    'cash' => 0,
    'esewa' => 0,
    'khalti' => 0,
    'card' => 0,
    'qr' => 0,
];

$table_earnings = [];
$hourly_earnings = [];

foreach ( $orders as $o ) {
    $totals['revenue'] += (float) $o->total;
    $totals['subtotal'] += (float) $o->subtotal;
    $totals['discount'] += (float) $o->discount_amount;
    $totals['service'] += (float) $o->service_amount;
    $totals['vat'] += (float) $o->vat_amount;

    // Payment method
    if ( isset( $totals[ $o->payment_method ] ) ) {
        $totals[ $o->payment_method ] += (float) $o->total;
    }

    // Table-wise earnings
    $tid = (int) $o->table_number;
    if ( ! isset( $table_earnings[ $tid ] ) ) {
        $table_earnings[ $tid ] = [ 'count' => 0, 'revenue' => 0, 'name' => 'No Table' ];
    }
    $table_earnings[ $tid ]['count']++;
    $table_earnings[ $tid ]['revenue'] += (float) $o->total;

    // Get table name
    foreach ( $tables as $t ) {
        if ( $t->id == $tid ) {
            $table_earnings[ $tid ]['name'] = $t->table_name;
            break;
        }
    }

    // Hourly earnings
    $hour = date( 'H:00', strtotime( $o->created_at ) );
    if ( ! isset( $hourly_earnings[ $hour ] ) ) {
        $hourly_earnings[ $hour ] = [ 'count' => 0, 'revenue' => 0 ];
    }
    $hourly_earnings[ $hour ]['count']++;
    $hourly_earnings[ $hour ]['revenue'] += (float) $o->total;
}

// Sort hourly
ksort( $hourly_earnings );

// CSV export URL
$csv_url = wp_nonce_url(
    add_query_arg( [
        'action' => 'rp_panel_csv',
        'from' => $from,
        'to' => $to,
        'table_id' => $filter_table,
    ], admin_url( 'admin-post.php' ) ),
    'rp_panel_csv'
);
?>

<div class="flex-between mb-16">
    <h1 class="page-title" style="margin-bottom:0">Accounts &amp; Reports</h1>
    <a href="<?php echo esc_url( $csv_url ); ?>" class="btn btn-sm btn-outline">Download CSV</a>
</div>

<!-- Date Filter -->
<div class="p-card mb-16">
    <form method="get" class="flex-wrap gap-8 align-center">
        <input type="hidden" name="page" value="reports">
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label small">From</label>
            <input type="date" name="from" class="form-input" value="<?php echo esc_attr( $from ); ?>">
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label small">To</label>
            <input type="date" name="to" class="form-input" value="<?php echo esc_attr( $to ); ?>">
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label small">Table</label>
            <select name="table_id" class="form-select">
                <option value="0">All Tables</option>
                <?php foreach ( $tables as $t ) : ?>
                    <option value="<?php echo esc_attr( $t->id ); ?>" <?php selected( $filter_table, $t->id ); ?>><?php echo esc_html( $t->table_name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Show</button>
    </form>
    <div class="flex-wrap gap-6 mt-12">
        <a href="?page=reports&preset=today" class="btn btn-sm btn-outline">Today</a>
        <a href="?page=reports&preset=yesterday" class="btn btn-sm btn-outline">Yesterday</a>
        <a href="?page=reports&preset=week" class="btn btn-sm btn-outline">Last 7 Days</a>
        <a href="?page=reports&preset=month" class="btn btn-sm btn-outline">This Month</a>
    </div>
</div>

<p class="text-muted small mb-12">
    Showing <strong><?php echo esc_html( wp_date( 'd M Y', strtotime( $from ) ) ); ?></strong> to
    <strong><?php echo esc_html( wp_date( 'd M Y', strtotime( $to ) ) ); ?></strong>.
    Only <em>completed (paid)</em> orders count.
</p>

<!-- KPI Cards -->
<div class="stats-grid mb-16">
    <div class="stat-card stat-primary">
        <div class="stat-value"><?php echo esc_html( RP_Helpers::format_price( $totals['revenue'] ) ); ?></div>
        <div class="stat-label">Net Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo esc_html( number_format_i18n( $totals['orders'] ) ); ?></div>
        <div class="stat-label">Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo esc_html( $totals['orders'] > 0 ? RP_Helpers::format_price( $totals['revenue'] / $totals['orders'] ) : 'Rs.0' ); ?></div>
        <div class="stat-label">Average Bill</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo esc_html( RP_Helpers::format_price( $totals['subtotal'] ) ); ?></div>
        <div class="stat-label">Gross Sales</div>
    </div>
    <div class="stat-card text-danger">
        <div class="stat-value">−<?php echo esc_html( RP_Helpers::format_price( $totals['discount'] ) ); ?></div>
        <div class="stat-label">Discounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo esc_html( RP_Helpers::format_price( $totals['service'] ) ); ?></div>
        <div class="stat-label">Service Charge</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo esc_html( RP_Helpers::format_price( $totals['vat'] ) ); ?></div>
        <div class="stat-label">VAT Collected</div>
    </div>
</div>

<!-- Two Columns -->
<div class="grid-2 mb-16">
    <!-- Table-wise Earnings -->
    <div class="p-card">
        <h3 class="p-card-title">Table-wise Earnings</h3>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th class="text-center">Orders</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $table_earnings ) ) : ?>
                        <tr><td colspan="3" class="text-muted text-center py-16">No orders in this period.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $table_earnings as $tid => $tdata ) : ?>
                            <tr>
                                <td><?php echo esc_html( $tdata['name'] ); ?></td>
                                <td class="text-center"><?php echo esc_html( $tdata['count'] ); ?></td>
                                <td class="text-right fw-700"><?php echo esc_html( RP_Helpers::format_price( $tdata['revenue'] ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="p-card">
        <h3 class="p-card-title">Payment Methods</h3>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $methods = [
                        'cash' => 'Cash',
                        'esewa' => '📱 eSewa',
                        'khalti' => '📱 Khalti',
                        'card' => 'Card',
                        'qr' => '📷 QR',
                    ];
                    foreach ( $methods as $key => $label ) :
                        if ( $totals[ $key ] > 0 ) :
                    ?>
                        <tr>
                            <td><?php echo esc_html( $label ); ?></td>
                            <td class="text-right fw-700"><?php echo esc_html( RP_Helpers::format_price( $totals[ $key ] ) ); ?></td>
                        </tr>
                    <?php endif; endforeach; ?>
                    <?php if ( $totals['cash'] + $totals['esewa'] + $totals['khalti'] + $totals['card'] + $totals['qr'] == 0 ) : ?>
                        <tr><td colspan="2" class="text-muted text-center py-16">No payments in this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hourly Breakdown -->
<div class="p-card mb-16">
    <h3 class="p-card-title">⏰ Hourly Breakdown</h3>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Time</th>
                    <th class="text-center">Orders</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $hourly_earnings ) ) : ?>
                    <tr><td colspan="3" class="text-muted text-center py-16">No data.</td></tr>
                <?php else : ?>
                    <?php foreach ( $hourly_earnings as $hour => $hdata ) : ?>
                        <tr>
                            <td><?php echo esc_html( $hour ); ?> - <?php echo esc_html( date( 'H:i', strtotime( $hour ) + 3600 ) ); ?></td>
                            <td class="text-center"><?php echo esc_html( $hdata['count'] ); ?></td>
                            <td class="text-right fw-700"><?php echo esc_html( RP_Helpers::format_price( $hdata['revenue'] ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Transaction Log -->
<div class="p-card">
    <h3 class="p-card-title">Transaction Log</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Date & Time</th>
                    <th>Table</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th class="text-right">Amount</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $orders ) ) : ?>
                    <tr><td colspan="8" class="text-muted text-center py-20">No transactions in this period.</td></tr>
                <?php else : ?>
                    <?php foreach ( $orders as $o ) :
                        $item_count = $wpdb->get_var( $wpdb->prepare(
                            "SELECT SUM(quantity) FROM {$wpdb->prefix}rp_order_items WHERE order_id = %d",
                            $o->id
                        ) );
                        $table_name = '—';
                        foreach ( $tables as $t ) {
                            if ( $t->id == $o->table_number ) {
                                $table_name = $t->table_name;
                                break;
                            }
                        }
                        $bill_url = home_url( '/panel/orders/' . $o->id . '/?print=1' );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $o->invoice_no ); ?></strong></td>
                            <td>
                                <?php echo esc_html( wp_date( 'd M', strtotime( $o->created_at ) ) ); ?><br>
                                <span class="text-muted small"><?php echo esc_html( wp_date( 'g:i A', strtotime( $o->created_at ) ) ); ?></span>
                            </td>
                            <td><?php echo esc_html( $table_name ); ?></td>
                            <td>
                                <?php if ( $o->customer_name ) : ?>
                                    <?php echo esc_html( $o->customer_name ); ?>
                                    <?php if ( $o->customer_phone ) : ?>
                                        <br><span class="text-muted small"><?php echo esc_html( $o->customer_phone ); ?></span>
                                    <?php endif; ?>
                                <?php else : ?>—<?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $item_count ); ?> items</td>
                            <td class="text-right fw-700"><?php echo esc_html( RP_Helpers::format_price( (float) $o->total ) ); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $o->payment_method === 'cash' ? 'success' : 'info'; ?>">
                                    <?php echo esc_html( RP_Billing::payment_methods()[ $o->payment_method ] ?? ucfirst( $o->payment_method ) ); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $bill_url ); ?>" class="btn btn-sm btn-outline" target="_blank">View Bill</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px}
.stat-card{background:var(--w);border-radius:10px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.stat-card.stat-primary{background:linear-gradient(135deg,#F5C300,#e6b800)}
.stat-value{font-family:'Poppins',sans-serif;font-size:1.3rem;font-weight:800;color:var(--b)}
.stat-primary .stat-value{color:var(--w)}
.stat-label{font-size:.72rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-top:4px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.table th{font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;color:#888;font-weight:600}
.table td{vertical-align:middle}
.badge{padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:600}
.badge-success{background:#d4edda;color:#155724}
.badge-info{background:#cce5ff;color:#004085}
@media(max-width:768px){
    .grid-2{grid-template-columns:1fr}
    .stats-grid{grid-template-columns:1fr 1fr}
}
</style>
