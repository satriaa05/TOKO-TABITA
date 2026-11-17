/**
 * Pembuatan grafik dashboard memakai Chart.js.
 *
 * Diinisialisasi ulang bila halaman memakai layout gemeinsame
 * (mis. berpindah dari filter periode tanpa reload penuh).
 */
import {
    Chart,
    BarController,
    LineController,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    BarController,
    LineController,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Tooltip,
    Legend,
    Filler
);

const rupiahFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

/**
 * Gambar grafik penjualan (baris/area).
 *
 * @param {HTMLCanvasElement} canvas
 * @param {string[]} labels
 * @param {number[]} values
 */
export function renderSalesChart(canvas, labels, values) {
    if (!canvas) {
        return null;
    }

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Penjualan',
                    data: values,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => rupiahFormatter.format(context.parsed.y),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { autoSkip: true, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: {
                        callback: (value) => rupiahFormatter.format(value),
                    },
                },
            },
        },
    });
}

/**
 * Gambar grafik batang (contoh: produk terlaris / performa kasir).
 *
 * @param {HTMLCanvasElement} canvas
 * @param {string[]} labels
 * @param {number[]} values
 */
export function renderBarChart(canvas, labels, values, color = '#16a34a') {
    if (!canvas) {
        return null;
    }

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Nilai',
                    data: values,
                    backgroundColor: color,
                    borderRadius: 4,
                    maxBarThickness: 34,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => rupiahFormatter.format(context.parsed.y),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: {
                        callback: (value) => rupiahFormatter.format(value),
                    },
                },
            },
        },
    });
}

window.renderSalesChart = renderSalesChart;
window.renderBarChart = renderBarChart;
