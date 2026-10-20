/**
 * Wallet dashboard charts
 */
'use strict';

(function () {
    var data = window.walletChartData || {};
    var isDark = !!data.isDark;

    var colors = {
        brand: '#cb0c9f',
        brandDark: '#830866',
        brandLight: '#a10a7f',
        success: '#22c55e',
        danger: '#f43f5e',
        info: '#38bdf8',
        palette: ['#cb0c9f', '#830866', '#a10a7f', '#22c55e', '#38bdf8', '#f59e0b', '#8b5cf6', '#ec4899'],
        grid: isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(15, 23, 42, 0.08)',
        text: isDark ? '#a1a8b8' : '#64748b',
        tooltipBg: isDark ? 'rgba(22, 22, 30, 0.95)' : 'rgba(255, 255, 255, 0.96)',
        tooltipBorder: isDark ? 'rgba(255, 255, 255, 0.12)' : 'rgba(255, 255, 255, 0.72)',
    };

    var chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        legend: { display: false },
        tooltips: {
            backgroundColor: colors.tooltipBg,
            borderColor: colors.tooltipBorder,
            borderWidth: 1,
            titleFontColor: isDark ? '#f8fafc' : '#0f172a',
            bodyFontColor: colors.text,
            xPadding: 12,
            yPadding: 10,
            cornerRadius: 8,
        },
    };

    function initSparkline() {
        var canvas = document.getElementById('walletSparklineChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var values = data.sparklineValues || [0, 0, 0, 0, 0, 0, 0];
        var labels = data.activityLabels || [];

        var gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 120);
        gradient.addColorStop(0, isDark ? 'rgba(203, 12, 159, 0.35)' : 'rgba(203, 12, 159, 0.25)');
        gradient.addColorStop(1, 'rgba(203, 12, 159, 0)');

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderColor: colors.brand,
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: colors.brand,
                    pointBorderColor: isDark ? '#16161d' : '#fff',
                    pointBorderWidth: 2,
                    lineTension: 0.35,
                    fill: true,
                }],
            },
            options: Object.assign({}, chartDefaults, {
                scales: {
                    xAxes: [{
                        gridLines: { display: false, drawBorder: false },
                        ticks: { fontColor: colors.text, fontSize: 11, maxRotation: 0 },
                    }],
                    yAxes: [{
                        gridLines: { color: colors.grid, drawBorder: false, zeroLineColor: colors.grid },
                        ticks: {
                            fontColor: colors.text,
                            fontSize: 11,
                            beginAtZero: true,
                            callback: function (value) {
                                return '$' + value;
                            },
                        },
                    }],
                },
                tooltips: Object.assign({}, chartDefaults.tooltips, {
                    callbacks: {
                        label: function (tooltipItem) {
                            return ' Volume: $' + Number(tooltipItem.yLabel).toFixed(2);
                        },
                    },
                }),
            }),
        });
    }

    function buildLegend(labels, values, chartColors) {
        var legend = document.getElementById('walletAllocationLegend');
        if (!legend) {
            return;
        }

        legend.innerHTML = '';
        var total = values.reduce(function (sum, val) { return sum + val; }, 0);

        labels.forEach(function (label, index) {
            var value = values[index] || 0;
            var pct = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
            var item = document.createElement('li');
            item.className = 'wallet-allocation-legend__item';
            item.innerHTML =
                '<span class="wallet-allocation-legend__dot" style="background:' + chartColors[index % chartColors.length] + '"></span>' +
                '<span class="wallet-allocation-legend__label">' + label + '</span>' +
                '<span class="wallet-allocation-legend__value">$' + Number(value).toFixed(2) + ' <em>(' + pct + '%)</em></span>';
            legend.appendChild(item);
        });
    }

    function initAllocation() {
        var canvas = document.getElementById('walletAllocationChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var labels = data.allocationLabels || [];
        var values = data.allocationValues || [];
        var chartColors = colors.palette.slice(0, Math.max(labels.length, 1));

        if (!labels.length) {
            labels = ['No holdings'];
            values = [1];
            chartColors = [isDark ? 'rgba(255,255,255,0.12)' : 'rgba(15,23,42,0.1)'];
            var legend = document.getElementById('walletAllocationLegend');
            if (legend) {
                legend.innerHTML = '<li class="wallet-allocation-legend__empty">Start trading to see your portfolio breakdown</li>';
            }
        } else {
            buildLegend(labels, values, chartColors);
            if (data.isDemo) {
                var legendEl = document.getElementById('walletAllocationLegend');
                if (legendEl) {
                    var note = document.createElement('li');
                    note.className = 'wallet-allocation-legend__demo';
                    note.textContent = 'Preview — not your actual holdings';
                    legendEl.appendChild(note);
                }
            }
        }

        new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: chartColors,
                    borderColor: isDark ? 'rgba(22, 22, 30, 0.8)' : 'rgba(255, 255, 255, 0.85)',
                    borderWidth: 2,
                    hoverBorderWidth: 2,
                    hoverOffset: 4,
                }],
            },
            options: Object.assign({}, chartDefaults, {
                cutoutPercentage: 68,
                tooltips: Object.assign({}, chartDefaults.tooltips, {
                    callbacks: {
                        label: function (tooltipItem, chartData) {
                            var label = chartData.labels[tooltipItem.index] || '';
                            var value = chartData.datasets[0].data[tooltipItem.index] || 0;
                            return ' ' + label + ': $' + Number(value).toFixed(2);
                        },
                    },
                }),
            }),
        });
    }

    function initActivity() {
        var canvas = document.getElementById('walletActivityChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.activityLabels || [],
                datasets: [
                    {
                        label: 'Buy',
                        data: data.activityBuy || [],
                        backgroundColor: isDark ? 'rgba(34, 197, 94, 0.75)' : 'rgba(34, 197, 94, 0.85)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 1,
                        barPercentage: 0.55,
                        categoryPercentage: 0.65,
                    },
                    {
                        label: 'Sell',
                        data: data.activitySell || [],
                        backgroundColor: isDark ? 'rgba(244, 63, 94, 0.75)' : 'rgba(244, 63, 94, 0.85)',
                        borderColor: 'rgba(244, 63, 94, 1)',
                        borderWidth: 1,
                        barPercentage: 0.55,
                        categoryPercentage: 0.65,
                    },
                ],
            },
            options: Object.assign({}, chartDefaults, {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 10,
                        fontColor: colors.text,
                        fontSize: 12,
                        padding: 16,
                        usePointStyle: true,
                    },
                },
                scales: {
                    xAxes: [{
                        gridLines: { display: false, drawBorder: false },
                        ticks: { fontColor: colors.text, fontSize: 12 },
                    }],
                    yAxes: [{
                        gridLines: { color: colors.grid, drawBorder: false, zeroLineColor: colors.grid },
                        ticks: {
                            fontColor: colors.text,
                            fontSize: 11,
                            beginAtZero: true,
                            precision: 0,
                            stepSize: 1,
                        },
                    }],
                },
            }),
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSparkline();
        initAllocation();
        initActivity();
    });
})();
