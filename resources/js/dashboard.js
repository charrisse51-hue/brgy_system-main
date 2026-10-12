/**
 * ============================================================
 * BARANGAY DOYONG EDRMS — dashboard.js
 * Place in: public/js/dashboard.js
 * Link in blade: <script src="{{ asset('js/dashboard.js') }}" defer></script>
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // ══════════════════════════════════════════════════════
    // 1. SIDEBAR – MOBILE TOGGLE
    // ══════════════════════════════════════════════════════
    const sidebar   = document.querySelector('.sidebar');
    const menuBtn   = document.querySelector('#menuToggle');
    const overlay   = document.querySelector('#sidebarOverlay');

    function openSidebar() {
        sidebar?.classList.add('open');
        overlay?.classList.remove('hidden');
    }

    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.add('hidden');
    }

    menuBtn?.addEventListener('click', openSidebar);
    overlay?.addEventListener('click', closeSidebar);

    // Close on resize back to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) closeSidebar();
    });


    // ══════════════════════════════════════════════════════
    // 2. ACTIVE NAV ITEM — highlight based on current URL
    // ══════════════════════════════════════════════════════
    const currentPath = window.location.pathname;

    document.querySelectorAll('.nav-item').forEach(link => {
        const href = link.getAttribute('href') ?? '';
        if (href && currentPath.startsWith(href) && href !== '/') {
            link.classList.add('active');
        }
    });


    // ══════════════════════════════════════════════════════
    // 3. BAR CHART — animate bar heights on load
    //    Heights are driven by data-value="0..100" attributes
    //    e.g. <div class="bar-col" data-value="75">
    // ══════════════════════════════════════════════════════
    function animateBars() {
        const cols = document.querySelectorAll('.bar-col');
        cols.forEach((col, i) => {
            const fill = col.querySelector('.bar-fill');
            if (!fill) return;

            const value    = parseFloat(col.dataset.value ?? 0);   // 0–100
            const maxPx    = col.closest('.bar-chart-wrap')?.clientHeight ?? 140;
            const targetPx = (value / 100) * maxPx;

            fill.style.height = '0px';

            setTimeout(() => {
                fill.style.transition = `height 0.6s cubic-bezier(.4,0,.2,1) ${i * 60}ms`;
                fill.style.height     = `${targetPx}px`;
            }, 100);
        });
    }

    animateBars();

    // Re-animate when a chart period dropdown changes
    document.querySelector('.chart-select')?.addEventListener('change', animateBars);


    // ══════════════════════════════════════════════════════
    // 4. DONUT CHART — CSS conic-gradient driven by data attrs
    //    <div class="donut-wrap"
    //         data-fire="15"
    //         data-accident="25"
    //         data-theft="35"
    //         data-flood="25">
    // ══════════════════════════════════════════════════════
    function buildDonut() {
        const wrap = document.querySelector('.donut-wrap');
        if (!wrap) return;

        const fire     = parseFloat(wrap.dataset.fire     ?? 15);
        const accident = parseFloat(wrap.dataset.accident ?? 25);
        const theft    = parseFloat(wrap.dataset.theft    ?? 35);
        const flood    = parseFloat(wrap.dataset.flood    ?? 25);

        const total = fire + accident + theft + flood;
        let acc = 0;

        const stops = [
            { color: '#ef4444', value: fire },
            { color: '#f59e0b', value: accident },
            { color: '#15803d', value: theft },
            { color: '#10b981', value: flood },
        ];

        const segments = stops.map(s => {
            const from = (acc / total * 100).toFixed(2);
            acc += s.value;
            const to = (acc / total * 100).toFixed(2);
            return `${s.color} ${from}% ${to}%`;
        });

        wrap.style.background = `conic-gradient(${segments.join(', ')})`;
    }

    buildDonut();


    // ══════════════════════════════════════════════════════
    // 5. FLASH MESSAGE — auto-dismiss after 4 seconds
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.flash-msg').forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            msg.style.opacity    = '0';
            msg.style.transform  = 'translateY(-6px)';
            setTimeout(() => msg.remove(), 450);
        }, 4000);
    });


    // ══════════════════════════════════════════════════════
    // 6. SEARCH BAR — live filter on incident table
    // ══════════════════════════════════════════════════════
    const searchInput = document.querySelector('.search-input');
    const tableRows   = document.querySelectorAll('.data-table tbody tr');

    searchInput?.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = q === '' || text.includes(q) ? '' : 'none';
        });

        // Show empty-state row if all rows hidden
        const visibleRows = [...tableRows].filter(r => r.style.display !== 'none');
        const emptyRow    = document.querySelector('#tableEmptyRow');
        if (emptyRow) {
            emptyRow.style.display = visibleRows.length === 0 ? '' : 'none';
        }
    });


    // ══════════════════════════════════════════════════════
    // 7. STATUS BADGE INLINE UPDATE (AJAX)
    //    Triggered when admin changes status select inside a row
    //    Requires: <select class="status-select" data-id="{{ $incident->id }}">
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.addEventListener('change', async () => {
            const id     = sel.dataset.id;
            const status = sel.value;
            const badge  = sel.closest('tr')?.querySelector('.badge');
            const token  = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!id || !token) return;

            try {
                const res = await fetch(`/admin/incidents/${id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ status }),
                });

                if (res.ok && badge) {
                    badge.className  = `badge badge-${status}`;
                    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    showToast(`Status updated to "${status}"`, 'success');
                } else {
                    showToast('Failed to update status', 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            }
        });
    });


    // ══════════════════════════════════════════════════════
    // 8. NOTIFICATION BELL — toggle dropdown
    // ══════════════════════════════════════════════════════
    const bellBtn  = document.querySelector('#notifBell');
    const notifBox = document.querySelector('#notifDropdown');

    bellBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        notifBox?.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
        notifBox?.classList.add('hidden');
    });


    // ══════════════════════════════════════════════════════
    // 9. REFRESH ACTIVITY LOG BUTTON
    // ══════════════════════════════════════════════════════
    document.querySelector('#refreshActivity')?.addEventListener('click', async (btn) => {
        const icon = btn.currentTarget.querySelector('.material-icons-round');
        icon?.classList.add('animate-spin');

        try {
            const res  = await fetch('/admin/activity-log', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await res.text();
            const list = document.querySelector('.activity-list');
            if (list && html) list.innerHTML = html;
        } catch (e) {
            // silently fail — activity log is non-critical
        } finally {
            setTimeout(() => icon?.classList.remove('animate-spin'), 600);
        }
    });


    // ══════════════════════════════════════════════════════
    // 10. TOAST NOTIFICATION HELPER
    // ══════════════════════════════════════════════════════
    /**
     * showToast(message, type)
     * type: 'success' | 'error' | 'info'
     */
    window.showToast = function(message, type = 'success') {
        // Remove any existing toast
        document.querySelector('.toast-msg')?.remove();

        const icons = { success: 'check_circle', error: 'error', info: 'info' };

        const toast = document.createElement('div');
        toast.className = `toast-msg flash-msg ${type}`;
        toast.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            min-width: 280px;
            max-width: 360px;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
        `;
        toast.innerHTML = `
            <span class="material-icons-round">${icons[type] ?? 'info'}</span>
            <span style="flex:1">${message}</span>
        `;

        document.body.appendChild(toast);

        // Auto-remove after 3.5s
        setTimeout(() => {
            toast.style.transition = 'opacity 0.4s, transform 0.4s';
            toast.style.opacity    = '0';
            toast.style.transform  = 'translateY(10px)';
            setTimeout(() => toast.remove(), 450);
        }, 3500);
    };


    // ══════════════════════════════════════════════════════
    // 11. CONFIRM BEFORE ARCHIVING / DELETING
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const msg = el.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });


    // ══════════════════════════════════════════════════════
    // 12. STAT CARD COUNT-UP ANIMATION
    // ══════════════════════════════════════════════════════
    function countUp(el, target, duration = 900) {
        const start     = 0;
        const startTime = performance.now();

        function update(now) {
            const elapsed  = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease     = 1 - Math.pow(1 - progress, 3); // easeOutCubic

            el.textContent = String(Math.floor(ease * target)).padStart(
                el.dataset.pad ? parseInt(el.dataset.pad) : 0, '0'
            );

            if (progress < 1) requestAnimationFrame(update);
        }

        requestAnimationFrame(update);
    }

    document.querySelectorAll('.stat-value[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count ?? '0');
        countUp(el, target);
    });


    // ══════════════════════════════════════════════════════
    // 13. AUTO-REFRESH STAT CARDS every 60 seconds
    // ══════════════════════════════════════════════════════
    async function refreshStats() {
        try {
            const res  = await fetch('/admin/dashboard/stats', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();

            const map = {
                'stat-total':    data.totalIncidents,
                'stat-pending':  data.pendingCount,
                'stat-ongoing':  data.ongoingCount,
                'stat-resolved': data.resolvedCount,
                'stat-alerts':   data.totalAlerts,
                'stat-residents':data.totalResidents,
            };

            Object.entries(map).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) {
                    const prev = parseInt(el.textContent.replace(/,/g, '')) || 0;
                    if (prev !== val) countUp(el, val);
                }
            });

            // Update pending badge in sidebar
            const badge = document.querySelector('.nav-badge');
            if (badge && data.pendingCount !== undefined) {
                badge.textContent = data.pendingCount;
                badge.style.display = data.pendingCount > 0 ? '' : 'none';
            }

        } catch (e) {
            // Non-critical — silent fail
        }
    }

    // Kick off auto-refresh
    setInterval(refreshStats, 60_000);


    // ══════════════════════════════════════════════════════
    // 14. KEYBOARD SHORTCUTS
    // ══════════════════════════════════════════════════════
    document.addEventListener('keydown', (e) => {
        // Ctrl/Cmd + K → focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput?.focus();
        }
        // Escape → blur search / close mobile sidebar
        if (e.key === 'Escape') {
            searchInput?.blur();
            closeSidebar();
        }
    });

}); // end DOMContentLoaded