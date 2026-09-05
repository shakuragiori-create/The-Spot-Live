(function() {
    'use strict';

    var data = window.rpDashData;
    if (!data) return;

    var canvas = document.getElementById('hourlyChart');
    if (!canvas) return;

    var fd = new FormData();
    fd.append('action', 'rp_get_hourly_stats');
    fd.append('nonce', data.nonce);

    fetch(data.ajaxUrl, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) return;
            var hours = res.data.hours || [];
            var counts = res.data.counts || [];

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'Orders',
                        data: counts,
                        backgroundColor: '#F5C300',
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
})();
