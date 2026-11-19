/**
 * POS MINIMARKET - JavaScript Utama
 * Bootstrap 5 bundle (sudah mencakup Popper) + helper aplikasi.
 */

import './bootstrap';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Ekspos objek Bootstrap ke window agar bisa dipakai di halaman POS
// (modal, tooltip, dropdown, toast).
window.bootstrap = bootstrap;

/**
 * Helper kecil: tampilkan pesan flash sebagai Bootstrap Toast.
 */
function showToast(message, variant = 'success') {
    const container = document.getElementById('pos-toast-container');

    if (!container) {
        return;
    }

    const tone = {
        success: 'text-bg-success',
        danger: 'text-bg-danger',
        warning: 'text-bg-warning',
        info: 'text-bg-info',
    }[variant] || 'text-bg-secondary';

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center border-0 ${tone}`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');

    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
    `;

    container.appendChild(toastEl);

    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    toast.show();
}

window.showToast = showToast;

/**
 * Konfirmasi sebelum tindakan berisiko (hapus, batalkan, dsb).
 * Dipakai lewat atribut: data-confirm="Pesan konfirmasi"
 */
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm]');

    if (!trigger) {
        return;
    }

    if (!window.confirm(trigger.dataset.confirm)) {
        event.preventDefault();
        event.stopPropagation();
    }
});

/**
 * Jam digital pada topbar.
 */
function startClock() {
    const clock = document.getElementById('pos-clock');

    if (!clock) {
        return;
    }

    const tick = () => {
        const now = new Date();
        clock.textContent = now.toLocaleTimeString('id-ID', { hour12: false, timeZone: 'Asia/Jakarta' });
    };

    tick();
    setInterval(tick, 1000);
}

/**
 * Toggle sidebar pada layar kecil.
 */
document.addEventListener('DOMContentLoaded', () => {
    startClock();

    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('.pos-sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            sidebar.classList.toggle('show');
        });
    }

    // Tutup sidebar saat klik area konten atau latar gelap (mode mobile)
    document.querySelector('.pos-content')?.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            sidebar?.classList.remove('show');
        }
    });

    document.querySelector('.pos-sidebar-backdrop')?.addEventListener('click', () => {
        sidebar?.classList.remove('show');
    });
});
