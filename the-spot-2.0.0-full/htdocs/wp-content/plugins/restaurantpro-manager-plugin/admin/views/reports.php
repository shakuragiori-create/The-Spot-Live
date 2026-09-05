<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap rp-wrap rp-reports">

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h1 class="h4 mb-0 me-auto">Accounts &amp; Reports</h1>
        <a href="<?php echo esc_url( $csv_url ); ?>" class="btn btn-sm btn-outline-dark">⬇ Download CSV</a>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
    </div>

    <!-- ============ RANGE PICKER ============ -->
    <form method="get" class="card border-0 shadow-sm mb-4">
        <input type="hidden" name="page" value="rp-reports">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Group by</label>
                    <select name="group" class="form-select form-select-sm">
                        <option value="day"   <?php selected( $group, 'day' ); ?>>Daily</option>
                        <option value="week"  <?php selected( $group, 'week' ); ?>>Weekly</option>
                        <option value="month" <?php selected( $group, 'month' ); ?>>Monthly</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="<?php echo esc_attr( $from ); ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="<?php echo esc_attr( $to ); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Show</button>
                </div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <?php
                        $presets = [
                            'today'     => 'Today',
                            'yesterday' => 'Yesterday',
                            '7'         => 'Last 7 days',
                            '30'        => 'Last 30 days',
                            'month'     => 'This month',
                            'year'      => 'This year',
                        ];
                        foreach ( $presets as $key => $label ) :
                            $url = admin_url( 'admin.php?page=rp-reports&group=' . $group . '&preset=' . $key );
                            ?>
                            <a href="<?php echo esc_url( $url ); ?>" class="btn btn-sm btn-outline-secondary"><?php echo esc_html( $label ); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <p class="text-muted small">
        Showing <strong><?php echo esc_html( wp_date( 'd M Y', strtotime( $from ) ) ); ?></strong>
        to <strong><?php echo esc_html( wp_date( 'd M Y', strtotime( $to ) ) ); ?></strong>.
        Only <em>completed</em> (paid) orders count as revenue.
    </p>

    <!-- ============ KPI CARDS ============ -->
    <div class="row g-3 mb-4">
        <?php
        $kpis = [
            [ 'Net revenue',    RP_Helpers::format_price( $report['totals']['revenue'] ),  'text-primary' ],
            [ 'Orders',         number_format_i18n( $report['totals']['orders'] ),          '' ],
            [ 'Average bill',   RP_Helpers::format_price( $report['totals']['avg'] ),       '' ],
            [ 'Gross sales',    RP_Helpers::format_price( $report['totals']['subtotal'] ),  '' ],
            [ 'Discounts',      RP_Helpers::format_price( $report['totals']['discount'] ),  'text-danger' ],
            [ 'Service charge', RP_Helpers::format_price( $report['totals']['service'] ),   '' ],
            [ 'VAT collected',  RP_Helpers::format_price( $report['totals']['vat'] ),       '' ],
        ];
        foreach ( $kpis as $kpi ) : ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px">
                            <?php echo esc_html( $kpi[0] ); ?>
                        </div>
                        <div class="fw-bold <?php echo esc_attr( $kpi[2] ); ?>" style="font-size:1.15rem">
                            <?php echo esc_html( $kpi[1] ); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ============ CHARTS ============ -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Revenue</strong>
                    <span class="text-muted small">(<?php echo esc_html( $group === 'day' ? 'per day' : ( $group === 'week' ? 'per week' : 'per month' ) ); ?>)</span>
                </div>
                <div class="card-body"><canvas id="rp-chart-revenue" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Payment methods</strong></div>
                <div class="card-body"><canvas id="rp-chart-payments" height="180"></canvas></div>
            </div>
        </div>
    </div>

    <!-- ============ TABLES ============ -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Best sellers</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                        <?php if ( empty( $report['top_items'] ) ) : ?>
                            <tr><td colspan="3" class="text-muted text-center py-3">No sales in this period.</td></tr>
                        <?php else : foreach ( $report['top_items'] as $row ) : ?>
                            <tr>
                                <td><?php echo esc_html( $row['label'] ); ?></td>
                                <td class="text-center"><?php echo esc_html( $row['qty'] ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $row['revenue'] ) ); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>By category</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Category</th><th class="text-center">Qty</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                        <?php if ( empty( $report['categories'] ) ) : ?>
                            <tr><td colspan="3" class="text-muted text-center py-3">No sales in this period.</td></tr>
                        <?php else : foreach ( $report['categories'] as $row ) : ?>
                            <tr>
                                <td><?php echo esc_html( $row['label'] ); ?></td>
                                <td class="text-center"><?php echo esc_html( $row['qty'] ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $row['revenue'] ) ); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong><?php echo esc_html( ucfirst( $group ) ); ?> breakdown</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th class="text-center">Orders</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Service</th>
                                <th class="text-end">VAT</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $report['labels'] as $i => $label ) : ?>
                            <tr<?php echo $report['orders'][ $i ] ? '' : ' class="text-muted"'; ?>>
                                <td><?php echo esc_html( $label ); ?></td>
                                <td class="text-center"><?php echo esc_html( $report['orders'][ $i ] ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $report['discount'][ $i ] ) ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $report['service'][ $i ] ) ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $report['vat'][ $i ] ) ); ?></td>
                                <td class="text-end fw-semibold"><?php echo esc_html( RP_Helpers::format_price( $report['revenue'][ $i ] ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-center"><?php echo esc_html( $report['totals']['orders'] ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $report['totals']['discount'] ) ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $report['totals']['service'] ) ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $report['totals']['vat'] ) ); ?></td>
                                <td class="text-end"><?php echo esc_html( RP_Helpers::format_price( $report['totals']['revenue'] ) ); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.rpReportData = <?php echo wp_json_encode( [
    'labels'   => $report['labels'],
    'revenue'  => $report['revenue'],
    'orders'   => $report['orders'],
    'payments' => $report['payments'],
] ); ?>;
</script>
