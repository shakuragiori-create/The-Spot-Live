(function($) {
    'use strict';

    function loadDashboardChart() {
        $.post(rpAdmin.ajaxUrl, {
            action: 'rp_get_dashboard_stats',
            nonce: rpAdmin.nonce
        }, function(response) {
            if (!response.success) return;

            var data = response.data;
            var ctx = document.getElementById('rpOrdersChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.hours_labels,
                    datasets: [{
                        label: 'Orders',
                        data: data.hours_data,
                        backgroundColor: 'rgba(230, 126, 34, 0.7)',
                        borderColor: '#e67e22',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        });
    }

    $(document).ready(function() {
        if ($('#rpOrdersChart').length) {
            loadDashboardChart();
        }
    });

})(jQuery);
