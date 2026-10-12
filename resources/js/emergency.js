/**
 * ============================================================
 * BARANGAY DOYONG EDRMS — emergency.js
 * Place in: public/js/emergency.js
 * Requires: public/js/dashboard.js (loaded first)
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // ══════════════════════════════════════════════════════
    // 1. LIVE DATE/TIME CLOCK in topbar
    // ══════════════════════════════════════════════════════
    const clockEl = document.getElementById('liveDateTime');

    function updateClock() {
        if (!clockEl) return;
        const now = new Date();
        const opts = {
            year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true
        };
        clockEl.textContent = now.toLocaleDateString('en-PH', opts);
    }

    updateClock();
    setInterval(updateClock, 60_000);


    // ══════════════════════════════════════════════════════
    // 2. ALERT LEVEL RADIO — Banner Preview + HIGH warning
    // ══════════════════════════════════════════════════════
    const levelRadios    = document.querySelectorAll('.em-level-radio');
    const highAlertInfo  = document.getElementById('highAlertInfo');
    const bannerPreview  = document.getElementById('bannerPreview');
    const bannerTextEl   = document.getElementById('bannerPreviewText');
    const alertTitleInput = document.getElementById('alertTitle');

    const BANNER_CLASSES = ['em-banner--low', 'em-banner--medium', 'em-banner--high'];

    function updateBannerPreview() {
        const selected = document.querySelector('.em-level-radio:checked');
        if (!selected || !bannerPreview) return;

        const level = selected.value;
        const title = alertTitleInput?.value.trim() ||
                      'Alert message will appear here';

        // Remove previous level class, add new
        BANNER_CLASSES.forEach(c => bannerPreview.classList.remove(c));
        bannerPreview.classList.add(`em-banner--${level}`);

        // Update preview text
        if (bannerTextEl) bannerTextEl.textContent = title;

        // Show/hide HIGH warning box
        if (highAlertInfo) {
            highAlertInfo.style.display = level === 'high' ? 'flex' : 'none';
        }
    }

    levelRadios.forEach(r => r.addEventListener('change', updateBannerPreview));

    // Also update banner text as user types the title
    alertTitleInput?.addEventListener('input', () => {
        if (document.querySelector('.em-level-radio:checked') && bannerTextEl) {
            bannerTextEl.textContent = alertTitleInput.value.trim() ||
                                       'Alert message will appear here';
        }
    });

    // Run once if old() value pre-selects a level (Laravel validation redirect)
    if (document.querySelector('.em-level-radio:checked')) {
        updateBannerPreview();
    }


    // ══════════════════════════════════════════════════════
    // 3. FORM SUBMIT — loading state on Broadcast button
    // ══════════════════════════════════════════════════════
    const alertForm    = document.getElementById('alertForm');
    const broadcastBtn = alertForm?.querySelector('.em-broadcast-btn');

    alertForm?.addEventListener('submit', (e) => {
        // Basic client-side validation
        const title   = document.getElementById('alertTitle')?.value.trim();
        const message = document.getElementById('alertMessage')?.value.trim();
        const level   = document.querySelector('.em-level-radio:checked');

        if (!title || !message || !level) {
            e.preventDefault();
            window.showToast?.('Please fill in all required fields and select an alert level.', 'error');
            return;
        }

        // Show loading state
        if (broadcastBtn) {
            broadcastBtn.disabled = true;
            broadcastBtn.innerHTML = '<span class="material-icons-round animate-spin">sync</span> Broadcasting...';
        }
    });

    // Re-enable button if browser back-navigates
    window.addEventListener('pageshow', () => {
        if (broadcastBtn) {
            broadcastBtn.disabled = false;
            broadcastBtn.innerHTML = '<span class="material-icons-round">send</span> Broadcast Alert';
        }
    });


    // ══════════════════════════════════════════════════════
    // 4. ALERT SEARCH — live filter on history table
    // ══════════════════════════════════════════════════════
    const alertSearch    = document.getElementById('alertSearch');
    const alertsBody     = document.getElementById('alertsTableBody');
    const noAlertResults = document.getElementById('noAlertResults');

    function getRows() {
        return alertsBody ? [...alertsBody.querySelectorAll('.alert-row')] : [];
    }

    let activeLevelFilter = 'all';

    function filterAlerts() {
        const q     = alertSearch?.value.trim().toLowerCase() ?? '';
        const rows  = getRows();
        let visible = 0;

        rows.forEach(row => {
            const text  = row.textContent.toLowerCase();
            const level = row.dataset.level ?? '';

            const matchSearch = q === '' || text.includes(q);
            const matchLevel  = activeLevelFilter === 'all' || level === activeLevelFilter;

            if (matchSearch && matchLevel) {
                row.classList.remove('alert-row--hidden');
                visible++;
            } else {
                row.classList.add('alert-row--hidden');
            }
        });

        if (noAlertResults) {
            noAlertResults.style.display = (rows.length > 0 && visible === 0) ? 'block' : 'none';
        }
    }

    let searchTimer;
    alertSearch?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(filterAlerts, 200);
    });


    // ══════════════════════════════════════════════════════
    // 5. LEVEL FILTER PILLS (All | High | Medium | Low)
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.em-level-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active state
            document.querySelectorAll('.em-level-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            activeLevelFilter = btn.dataset.filter ?? 'all';
            filterAlerts();
        });
    });


    // ══════════════════════════════════════════════════════
    // 6. EDIT ALERT MODAL — OPEN
    // ══════════════════════════════════════════════════════
    const editModal      = document.getElementById('editAlertModal');
    const editForm       = document.getElementById('editAlertForm');
    const editSubtitle   = document.getElementById('editAlertSubtitle');
    const editTitleInput = document.getElementById('editTitle');
    const editMsgInput   = document.getElementById('editMessage');

    document.querySelectorAll('.btn-edit-alert').forEach(btn => {
        btn.addEventListener('click', () => {
            const id      = btn.dataset.id;
            const title   = btn.dataset.title   ?? '';
            const message = btn.dataset.message  ?? '';
            const level   = btn.dataset.level    ?? 'low';

            // Populate modal fields
            if (editSubtitle)   editSubtitle.textContent   = `Alert #${id}`;
            if (editTitleInput) editTitleInput.value        = title;
            if (editMsgInput)   editMsgInput.value          = message;

            // Pre-select level radio
            const radio = editModal?.querySelector(`input[name="level"][value="${level}"]`);
            if (radio) radio.checked = true;

            // Set form action URL
            if (editForm) {
                editForm.action = `/admin/emergency/${id}`;
            }

            openEditModal();
        });
    });


    // ══════════════════════════════════════════════════════
    // 7. EDIT ALERT MODAL — OPEN / CLOSE
    // ══════════════════════════════════════════════════════
    function openEditModal() {
        if (editModal) {
            editModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            setTimeout(() => editTitleInput?.focus(), 100);
        }
    }

    function closeEditModal() {
        if (editModal) {
            editModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    document.getElementById('closeEditModal')?.addEventListener('click', closeEditModal);
    document.getElementById('cancelEditModal')?.addEventListener('click', closeEditModal);

    editModal?.addEventListener('click', (e) => {
        if (e.target === editModal) closeEditModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeEditModal();
    });


    // ══════════════════════════════════════════════════════
    // 8. CONFIRM BEFORE DELETE
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', (e) => {
            const msg = form.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });


    // ══════════════════════════════════════════════════════
    // 9. FORM CLEAR — reset banner preview on clear
    // ══════════════════════════════════════════════════════
    alertForm?.addEventListener('reset', () => {
        setTimeout(() => {
            // Reset banner preview
            if (bannerTextEl) bannerTextEl.textContent = 'Alert message will appear here';
            if (highAlertInfo) highAlertInfo.style.display = 'none';
            if (bannerPreview) {
                BANNER_CLASSES.forEach(c => bannerPreview.classList.remove(c));
            }
        }, 50);
    });


    // ══════════════════════════════════════════════════════
    // 10. ROW HIGHLIGHT — pulse newly created rows
    //     Works if the page reloads after POST and the
    //     server passes ?new_id=X in the redirect URL.
    // ══════════════════════════════════════════════════════
    const urlParams = new URLSearchParams(window.location.search);
    const newId = urlParams.get('new_id');

    if (newId) {
        const rows = getRows();
        const target = rows[0]; // Newest row is always first (latest())
        if (target) {
            target.style.transition = 'background .4s';
            target.style.background = '#f0fdf4';
            setTimeout(() => { target.style.background = ''; }, 2000);
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        // Clean URL
        history.replaceState({}, '', window.location.pathname);
    }

}); // end DOMContentLoaded