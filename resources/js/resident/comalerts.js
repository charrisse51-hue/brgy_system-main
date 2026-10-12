/**
 * comalerts.js (community alerts page logic)
 * Place in: resources/js/resident/comalerts.js (built by Vite)
 * Load via: @vite('resources/js/resident/comalerts.js')
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ─────────────────────────────────────────
       Auto-dismiss flash alerts after 4 s
       ───────────────────────────────────────── */
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 420);
        }, 4000);
    });

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
       Sign-out — submit hidden CSRF form
       ───────────────────────────────────────── */
    var btnSignout = document.getElementById('btn_signout');
    if (btnSignout) {
        btnSignout.addEventListener('click', function () {
            document.getElementById('signout_form').submit();
        });
    }

    /* ─────────────────────────────────────────
       Emergency button confirmation guard
       ───────────────────────────────────────── */
    var btnEmergency = document.getElementById('btn_emergency');
    if (btnEmergency) {
        btnEmergency.addEventListener('click', function (e) {
            // If the button is an <a> tag with a real route, let it through directly.
            // Remove the confirm() below once you wire up the real route.
            if (!confirm('You are about to report an emergency. Continue?')) {
                e.preventDefault();
            }
        });
    }

    /* ─────────────────────────────────────────
       Alert card — entrance animation stagger
       ───────────────────────────────────────── */
    var cards = document.querySelectorAll('.alert-card');

    cards.forEach(function (card, i) {
        card.style.opacity   = '0';
        card.style.transform = 'translateY(12px)';
        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';

        setTimeout(function () {
            card.style.opacity   = '1';
            card.style.transform = 'translateY(0)';
        }, 80 + i * 80);
    });

    /* ─────────────────────────────────────────
       Auto-refresh alerts every 60 s
       Hits GET /resident/alerts?json=1 and
       updates the alerts count badge + feed.
       ───────────────────────────────────────── */
    var feedEl      = document.getElementById('alerts_feed');
    var countBadge  = document.getElementById('alerts_count_badge');
    var lastRefresh = Date.now();

    function refreshAlerts() {
        fetch(window.location.pathname + '?json=1', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data) return;

            /* Update count badge */
            if (countBadge && typeof data.total !== 'undefined') {
                countBadge.textContent = data.total + ' Active Alert' + (data.total !== 1 ? 's' : '');
            }

            /* Full feed re-render if HTML snippet provided */
            if (feedEl && data.html) {
                feedEl.innerHTML = data.html;
                animateNewCards();
            }

            lastRefresh = Date.now();
        })
        .catch(function () { /* silent — network may be offline */ });
    }

    function animateNewCards() {
        document.querySelectorAll('.alert-card').forEach(function (card, i) {
            card.style.opacity   = '0';
            card.style.transform = 'translateY(12px)';
            setTimeout(function () {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity    = '1';
                card.style.transform  = 'translateY(0)';
            }, 60 + i * 60);
        });
    }

    /* Kick off auto-refresh every 60 s */
    setInterval(refreshAlerts, 60000);

    /* Refresh when tab regains focus (if idle > 30 s) */
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && Date.now() - lastRefresh > 30000) {
            refreshAlerts();
        }
    });

    /* ─────────────────────────────────────────
       Toast helper (available globally)
       ───────────────────────────────────────── */
    window.showToast = function (msg, isError) {
        var toast = document.getElementById('toast');
        if (!toast) return;
        var span = toast.querySelector('span:last-child');
        var icon = toast.querySelector('.material-symbols-outlined');
        if (span) span.textContent = msg;
        if (icon) icon.style.color = isError ? '#ef4444' : '#15803d';
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 3200);
    };

});