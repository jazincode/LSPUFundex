// ============================================
// LSPUFundex - Main JavaScript File
// File: assets/js/main.js
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ----------------------------------------
    // 1. Live Clock in Navbar
    // ----------------------------------------
    const clockEl = document.getElementById('navClock');
    if (clockEl) {
        function updateClock() {
            const now = new Date();
            clockEl.textContent = now.toLocaleDateString('en-PH', {
                weekday: 'short', year: 'numeric',
                month:   'short', day:  'numeric'
            }) + '  ' + now.toLocaleTimeString('en-PH', {
                hour:   '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // ----------------------------------------
    // 2. Mobile Sidebar Toggle
    // ----------------------------------------
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // ----------------------------------------
    // 3. Auto-dismiss flash messages after 4s
    // ----------------------------------------
    const flash = document.querySelector('[data-flash]');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.5s';
            flash.style.opacity    = '0';
            setTimeout(() => flash.remove(), 500);
        }, 4000);
    }

    // ----------------------------------------
    // 4. Confirm before deleting
    // ----------------------------------------
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

});