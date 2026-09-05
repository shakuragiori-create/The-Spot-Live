/* RestaurantPro — report charts (Chart.js) */
(function () {
    'use strict';

    function draw() {
        var data = window.rpReportData;
        if (!data || typeof window.Chart === 'undefined') { return; }

        var orange = '#e67e22';

        var revCanvas = document.getElementById('rp-chart-revenue');
        if (revCanvas) {
            new window.Chart(revCanvas, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: data.revenue,
                            backgroundColor: 'rgba(230,126,34,0.75)',
                            borderColor: orange,
                            borderWidth: 1,
                            borderRadius: 4,
                            order: 2
                        },
                        {
                            label: 'Orders',
                            data: data.orders,
                            type: 'line',
                            yAxisID: 'y1',
                            borderColor: '#334155',
                            backgroundColor: '#334155',
                            borderWidth: 2,
                            tension: 0.35,
                            pointRadius: 2,
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y:  { beginAtZero: true, position: 'left', title: { display: true, text: 'Revenue' } },
                        y1: {
                            beginAtZero: true, position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { precision: 0 },
                            title: { display: true, text: 'Orders' }
                        }
                    },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        var payCanvas = document.getElementById('rp-chart-payments');
        if (payCanvas) {
            var labels = [];
            var values = [];
            (data.payments || []).forEach(function (row) {
                labels.push(row.label);
                values.push(row.revenue);
            });

            if (!labels.length) {
                payCanvas.parentNode.innerHTML =
                    '<p class="text-muted small text-center mb-0 py-4">No payments recorded in this period.</p>';
                return;
            }

            new window.Chart(payCanvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#e67e22', '#16a34a', '#7c3aed', '#0ea5e9', '#f59e0b', '#64748b', '#dc2626'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '58%',
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', draw);
    } else {
        draw();
    }
})();
