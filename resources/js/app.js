import Chart from 'chart.js/auto';

function renderDashboardCharts() {
    document.querySelectorAll('[data-dashboard-chart]').forEach((canvas) => {
        Chart.getChart(canvas)?.destroy();

        const config = JSON.parse(canvas.dataset.dashboardChart);
        new Chart(canvas, config);
    });
}

document.addEventListener('DOMContentLoaded', renderDashboardCharts);
document.addEventListener('livewire:navigated', renderDashboardCharts);
