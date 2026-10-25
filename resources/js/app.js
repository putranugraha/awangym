import './workout-chat';

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!button) return;
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
    button.querySelector('[data-password-slash]').toggleAttribute('hidden', !visible);
});

async function renderDashboardCharts() {
    if (!document.querySelector('[data-dashboard-chart]')) return;

    const { default: Chart } = await import('chart.js/auto');

    document.querySelectorAll('[data-dashboard-chart]').forEach((canvas) => {
        Chart.getChart(canvas)?.destroy();

        const config = JSON.parse(canvas.dataset.dashboardChart);
        new Chart(canvas, config);
    });
}

document.addEventListener('DOMContentLoaded', renderDashboardCharts);
document.addEventListener('livewire:navigated', renderDashboardCharts);
