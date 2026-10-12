/**
 * resident_dashboard.js
 * Place in: public/js/resident/resident_dashboard.js
 * Load via: <script src="{{ asset('js/resident/resident_dashboard.js') }}" defer></script>
 */

document.addEventListener('DOMContentLoaded', function () {

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
       Emergency button — confirm before action
       Prevents accidental taps on mobile
       ───────────────────────────────────────── */
    var emergencyBtn = document.getElementById('btn_emergency');

    if (emergencyBtn) {
        emergencyBtn.addEventListener('click', function (e) {
            var confirmed = confirm(
                'You are about to report an emergency.\n\n' +
                'Please only use this for genuine emergencies.\n\n' +
                'Do you want to continue?'
            );
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    /* ─────────────────────────────────────────
       Notification bell — visual feedback
       (wire up to a real notification drawer
       or dropdown when ready)
       ───────────────────────────────────────── */
    var notifBtn = document.getElementById('btn_notif');

    if (notifBtn) {
        notifBtn.addEventListener('click', function () {
            // Remove the red dot once the user acknowledges notifications
            var dot = notifBtn.querySelector('.notif-dot');
            if (dot) {
                dot.style.transition = 'opacity 0.3s';
                dot.style.opacity    = '0';
                setTimeout(function () { dot.remove(); }, 300);
            }
        });
    }

    /* ─────────────────────────────────────────
       Sign out form submit
       ───────────────────────────────────────── */
    var signOutBtn = document.getElementById('btn_signout');

    if (signOutBtn) {
        signOutBtn.addEventListener('click', function () {
            document.getElementById('signout_form').submit();
        });
    }

});