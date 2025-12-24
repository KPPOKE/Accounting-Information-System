let revenueExpenseChart = null;
let categoryChart = null;
let cashFlowChart = null;

function initializeCharts() {
    const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
    const successColor = getComputedStyle(document.documentElement).getPropertyValue('--success').trim();
    const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--danger').trim();
    const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--warning').trim();
    const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim();
    const textSecondary = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim();
    const borderColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim();
    const bgSecondary = getComputedStyle(document.documentElement).getPropertyValue('--bg-secondary').trim();

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : borderColor;
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.plugins.legend.labels.color = textColor;
    Chart.defaults.scale.ticks.color = textColor;
    Chart.defaults.scale.grid.color = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    function formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
    }

    function createGradient(ctx, color) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, color + '40');
        gradient.addColorStop(1, color + '00');
        return gradient;
    }

    if (revenueExpenseChart) {
        revenueExpenseChart.destroy();
    }
    if (categoryChart) {
        categoryChart.destroy();
    }
    if (cashFlowChart) {
        cashFlowChart.destroy();
    }

    if (document.getElementById('revenueExpenseChart')) {
        const ctx = document.getElementById('revenueExpenseChart').getContext('2d');
        const chartData = JSON.parse(document.getElementById('revenueExpenseData').textContent);

        revenueExpenseChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: chartData.datasets.revenue,
                        borderColor: successColor,
                        backgroundColor: isDarkMode ? createGradient(ctx, successColor) : successColor + '20',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 3,
                        pointBackgroundColor: successColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Beban',
                        data: chartData.datasets.expense,
                        borderColor: dangerColor,
                        backgroundColor: isDarkMode ? createGradient(ctx, dangerColor) : dangerColor + '20',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 3,
                        pointBackgroundColor: dangerColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            useBorderRadius: true,
                            borderRadius: 6,
                            color: textColor
                        }
                    },
                    tooltip: {
                        backgroundColor: bgSecondary,
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + formatRupiah(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: function (value) {
                                return formatRupiah(value);
                            }
                        },
                        grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        ticks: { color: textColor },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    if (document.getElementById('categoryChart')) {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const chartData = JSON.parse(document.getElementById('categoryData').textContent);

        const colors = [primaryColor, successColor, warningColor, dangerColor, '#8b5cf6', '#ec4899'];

        categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.values,
                    backgroundColor: colors.slice(0, chartData.labels.length),
                    borderWidth: 2,
                    borderColor: isDarkMode ? '#1e293b' : '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        align: 'center',
                        maxWidth: 1000,
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 12,
                            usePointStyle: true,
                            pointStyle: 'rect',
                            useBorderRadius: false,
                            font: {
                                size: 11,
                                family: "'Inter', sans-serif",
                                weight: '500'
                            },
                            color: textColor
                        }
                    },
                    tooltip: {
                        backgroundColor: bgSecondary,
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + percentage + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('cashFlowChart')) {
        const ctx = document.getElementById('cashFlowChart').getContext('2d');
        const chartData = JSON.parse(document.getElementById('cashFlowData').textContent);

        cashFlowChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Kas Masuk',
                        data: chartData.datasets.cash_in,
                        backgroundColor: successColor + 'CC',
                        borderColor: successColor,
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Kas Keluar',
                        data: chartData.datasets.cash_out,
                        backgroundColor: dangerColor + 'CC',
                        borderColor: dangerColor,
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            useBorderRadius: true,
                            borderRadius: 6,
                            color: textColor
                        }
                    },
                    tooltip: {
                        backgroundColor: bgSecondary,
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: borderColor,
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + formatRupiah(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: function (value) {
                                return formatRupiah(value);
                            }
                        },
                        grid: { color: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        ticks: { color: textColor },
                        grid: { display: false }
                    }
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
    }

    initializeCharts();

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.attributeName === 'data-theme') {
                setTimeout(initializeCharts, 100);
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });
});

function updateChart(chartType, period, chartInstance) {
    if (!chartInstance) return;

    const loading = document.createElement('div');
    loading.className = 'chart-loading active';
    loading.innerHTML = '<div class="chart-loading-spinner"></div><span>Memuat data...</span>';
    chartInstance.canvas.parentElement.appendChild(loading);

    fetch(`${window.location.origin}/FinacoreSIA/api/chart_data_ajax.php?chart=${chartType}&period=${period}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                chartInstance.data.labels = result.data.labels;

                if (chartType === 'revenue_expense') {
                    chartInstance.data.datasets[0].data = result.data.datasets.revenue;
                    chartInstance.data.datasets[1].data = result.data.datasets.expense;
                } else if (chartType === 'cash_flow') {
                    chartInstance.data.datasets[0].data = result.data.datasets.cash_in;
                    chartInstance.data.datasets[1].data = result.data.datasets.cash_out;
                }

                chartInstance.update('active');
            }
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            alert('Gagal memuat data chart. Silakan coba lagi.');
        })
        .finally(() => {
            loading.remove();
        });
}
