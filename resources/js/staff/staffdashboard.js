/**
 * staff_dashboard.js
 * Place in:  public/js/admin/staff_dashboard.js
 * Load via:  <script src="{{ asset('js/admin/staff_dashboard.js') }}" defer></script>
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ─────────────────────────────────────────
       Live clock — updates the header date/time
       ───────────────────────────────────────── */
    var clockEl = document.getElementById('live_clock');

    function updateClock() {
        if (!clockEl) return;
        var now  = new Date();
        var opts = {
            month: 'long', day: 'numeric', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true
        };
        clockEl.textContent = now.toLocaleString('en-PH', opts);
    }

    updateClock();
    setInterval(updateClock, 30000); // refresh every 30 s

    /* ─────────────────────────────────────────
       Incident search — filters table rows live
       ───────────────────────────────────────── */
    var searchInput  = document.getElementById('incident_search');
    var tableBody    = document.getElementById('incident_tbody');
    var totalShownEl = document.getElementById('total_shown');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            var query = this.value.trim().toLowerCase();
            var rows  = tableBody.querySelectorAll('tr[data-searchable]');
            var shown = 0;

            rows.forEach(function (row) {
                var match = !query || row.dataset.searchable.toLowerCase().includes(query);
                row.style.display = match ? '' : 'none';
                if (match) shown++;
            });

            if (totalShownEl) totalShownEl.textContent = shown;
        });
    }

    /* ─────────────────────────────────────────
       Notification bell — clear red dot on click
       ───────────────────────────────────────── */
    var btnNotif = document.getElementById('btn_notif');

    if (btnNotif) {
        btnNotif.addEventListener('click', function () {
            var dot = this.querySelector('.notif-dot');
            if (dot) {
                dot.style.transition = 'opacity 0.25s';
                dot.style.opacity    = '0';
                setTimeout(function () { dot.remove(); }, 260);
            }
        });
    }

    /* ─────────────────────────────────────────
       Filter button (placeholder)
       ───────────────────────────────────────── */
    var btnFilter = document.getElementById('btn_filter');

    if (btnFilter) {
        btnFilter.addEventListener('click', function () {
            // TODO: open filter panel / modal
            alert('Filter panel coming soon.');
        });
    }

    /* ─────────────────────────────────────────
       Logout form submit
       ───────────────────────────────────────── */
    var btnLogout = document.getElementById('btn_logout');

    if (btnLogout) {
        btnLogout.addEventListener('click', function () {
            document.getElementById('logout_form').submit();
        });
    }

    /* ─────────────────────────────────────────
       Auto-dismiss flash alerts after 4 s
       ───────────────────────────────────────── */
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s ease';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 420);
        }, 4000);
    });

    /* ─────────────────────────────────────────
       Stat counter animation on page load
       ───────────────────────────────────────── */
    document.querySelectorAll('.stat-value[data-count]').forEach(function (el) {
        var target   = parseInt(el.dataset.count, 10);
        var duration = 900;
        var steps    = 40;
        var step     = 0;

        var timer = setInterval(function () {
            step++;
            var eased   = 1 - Math.pow(1 - step / steps, 3); // ease-out cubic
            var current = Math.round(eased * target);
            el.textContent = current.toLocaleString();

            if (step >= steps) {
                clearInterval(timer);
                el.textContent = target.toLocaleString();
            }
        }, duration / steps);
    });

});