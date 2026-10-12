/**
 * ============================================================
 * BARANGAY DOYONG EDRMS — incidents.js
 * Place in: public/js/incidents.js
 * Requires: public/js/dashboard.js (loaded first)
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // ══════════════════════════════════════════════════════
    // 1. ELEMENTS
    // ══════════════════════════════════════════════════════
    const searchInput    = document.getElementById('incidentSearch');
    const typeFilter     = document.getElementById('typeFilter');
    const tableBody      = document.getElementById('incidentsTableBody');
    const allRows        = () => tableBody ? [...tableBody.querySelectorAll('.inc-row')] : [];
    const noResults      = document.getElementById('noSearchResults');
    const emptyStateRow  = document.getElementById('emptyStateRow');

    // Modal
    const statusModal    = document.getElementById('statusModal');
    const closeModalBtn  = document.getElementById('closeModal');
    const cancelModalBtn = document.getElementById('cancelModal');
    const confirmBtn     = document.getElementById('confirmStatusBtn');
    const modalIncId     = document.getElementById('modalIncidentId');

    let activeIncidentId  = null;
    let activeStatusRadio = null;


    // ══════════════════════════════════════════════════════
    // 2. CLIENT-SIDE SEARCH + TYPE FILTER
    //    Filters rows without a page reload.
    //    Falls back to server-side via URL if JS is disabled.
    // ══════════════════════════════════════════════════════
    function filterRows() {
        const query = searchInput?.value.trim().toLowerCase() ?? '';
        const type  = typeFilter?.value.toLowerCase() ?? '';
        const rows  = allRows();

        let visibleCount = 0;

        rows.forEach(row => {
            const text    = row.textContent.toLowerCase();
            const rowType = row.dataset.type?.toLowerCase() ?? '';

            const matchesSearch = query === '' || text.includes(query);
            const matchesType   = type === ''  || rowType === type;

            if (matchesSearch && matchesType) {
                row.classList.remove('inc-row--hidden');
                visibleCount++;
            } else {
                row.classList.add('inc-row--hidden');
            }
        });

        // Show/hide no-results message
        if (noResults) {
            noResults.style.display = (rows.length > 0 && visibleCount === 0) ? 'flex' : 'none';
        }

        // Hide the "no records" empty state when search is active
        if (emptyStateRow) {
            emptyStateRow.style.display = rows.length === 0 ? '' : 'none';
        }
    }

    // Debounce search input for performance
    let searchTimer;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(filterRows, 220);
    });

    typeFilter?.addEventListener('change', filterRows);

    // Run once on load to apply any URL-prefilled values
    filterRows();


    // ══════════════════════════════════════════════════════
    // 3. FILTER TAB CLICKS (client-side instant filter)
    //    The tabs are <a> links (server-side) BUT we also
    //    apply client-side filtering for instant UX.
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            // Let the browser navigate (server-side filtering).
            // Remove active from all, add to clicked.
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
        });
    });


    // ══════════════════════════════════════════════════════
    // 4. STATUS UPDATE MODAL — OPEN
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.btn-status-change').forEach(btn => {
        btn.addEventListener('click', () => {
            activeIncidentId = btn.dataset.id;
            const currentStatus = btn.dataset.status;

            // Set modal subtitle
            if (modalIncId) {
                modalIncId.textContent = `#INC-${String(activeIncidentId).padStart(3,'0')}`;
            }

            // Pre-select current status
            const radios = document.querySelectorAll('input[name="incidentStatus"]');
            radios.forEach(r => {
                r.checked = r.value === currentStatus;
            });

            openModal();
        });
    });


    // ══════════════════════════════════════════════════════
    // 5. STATUS UPDATE MODAL — CLOSE
    // ══════════════════════════════════════════════════════
    function openModal() {
        if (statusModal) {
            statusModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (statusModal) {
            statusModal.style.display = 'none';
            document.body.style.overflow = '';
            activeIncidentId = null;
        }
    }

    closeModalBtn?.addEventListener('click', closeModal);
    cancelModalBtn?.addEventListener('click', closeModal);

    // Close on backdrop click
    statusModal?.addEventListener('click', (e) => {
        if (e.target === statusModal) closeModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });


    // ══════════════════════════════════════════════════════
    // 6. STATUS UPDATE MODAL — CONFIRM (AJAX PATCH)
    // ══════════════════════════════════════════════════════
    confirmBtn?.addEventListener('click', async () => {
        const selected = document.querySelector('input[name="incidentStatus"]:checked');
        if (!selected) {
            window.showToast?.('Please select a status.', 'error');
            return;
        }

        const newStatus = selected.value;
        const token     = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!activeIncidentId || !token) return;

        // Loading state
        confirmBtn.disabled   = true;
        confirmBtn.innerHTML  = '<span class="material-icons-round animate-spin">sync</span> Saving...';

        try {
            const res = await fetch(`${document.body.dataset.incidentsUrl || '/admin/incidents'}/${activeIncidentId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status: newStatus }),
            });

            if (res.ok) {
                // Update badge in the table row — no page reload needed
                updateRowStatus(activeIncidentId, newStatus);
                closeModal();
                window.showToast?.(`Status updated to "${newStatus}"`, 'success');

                // Refresh sidebar pending badge count
                refreshPendingBadge();
            } else {
                const data = await res.json().catch(() => ({}));
                window.showToast?.(data.message ?? 'Failed to update status.', 'error');
            }
        } catch (err) {
            window.showToast?.('Network error. Please try again.', 'error');
        } finally {
            confirmBtn.disabled  = false;
            confirmBtn.innerHTML = '<span class="material-icons-round">save</span> Save Changes';
        }
    });


    // ══════════════════════════════════════════════════════
    // 7. UPDATE ROW STATUS IN THE TABLE (no reload)
    // ══════════════════════════════════════════════════════
    function updateRowStatus(incidentId, newStatus) {
        // Find the row by the edit button's data-id
        const editBtn = document.querySelector(`.btn-status-change[data-id="${incidentId}"]`);
        if (!editBtn) return;

        const row = editBtn.closest('tr.inc-row');
        if (!row) return;

        // Update badge
        const badge = row.querySelector('.badge');
        if (badge) {
            badge.className  = `badge badge-${newStatus}`;
            badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        }

        // Update data attribute on the row for client-side filtering
        row.dataset.status = newStatus;

        // Update the edit button's data-status
        editBtn.dataset.status = newStatus;

        // If resolved: show archive button (inject if not present)
        const actionsCell = editBtn.closest('.inc-actions');
        if (!document.body.dataset.incidentsUrl?.includes('/staff/') && actionsCell && newStatus === 'resolved' && !actionsCell.querySelector('.inc-action-btn--danger')) {
            const archiveForm = document.createElement('form');
            archiveForm.method = 'POST';
            archiveForm.action = `/admin/incidents/${incidentId}/archive`;
            archiveForm.style.display = 'inline';
            archiveForm.dataset.confirm = 'Archive this resolved incident?';
            archiveForm.innerHTML = `
                <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]').content}">
                <input type="hidden" name="_method" value="PATCH">
                <button type="submit" class="inc-action-btn inc-action-btn--danger" title="Archive">
                    <span class="material-icons-round">archive</span>
                </button>`;

            actionsCell.appendChild(archiveForm);

            // Confirmation is delegated to the shared UI layer.
        }

        // Highlight row briefly
        row.style.transition = 'background .3s';
        row.style.background = '#f0fdf4';
        setTimeout(() => { row.style.background = ''; }, 1200);
    }


    // ══════════════════════════════════════════════════════
    // 8. UPDATE TOPBAR NOTIFICATION DOT
    // ══════════════════════════════════════════════════════
    async function refreshPendingBadge() {
        try {
            const res  = await fetch(document.body.dataset.statsUrl || '/admin/dashboard/stats', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;

            const data = await res.json();

            // Update topbar notif dot
            const dot = document.querySelector('.notif-dot');
            if (dot) {
                dot.style.display = (data.pendingCount ?? 0) > 0 ? '' : 'none';
            }
        } catch (e) {
            // silent fail
        }
    }

    // ══════════════════════════════════════════════════════
    // AUTO-HIDE SUCCESS & FLASH NOTIFICATIONS (3 seconds)
    // ══════════════════════════════════════════════════════
    const flashMessages = document.querySelectorAll('.flash-msg');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.4s ease, transform 0.4s ease, max-height 0.4s ease, margin 0.4s ease, padding 0.4s ease';
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-8px)';
            msg.style.maxHeight = '0';
            msg.style.marginTop = '0';
            msg.style.marginBottom = '0';
            msg.style.paddingTop = '0';
            msg.style.paddingBottom = '0';
            msg.style.borderWidth = '0';
            setTimeout(() => msg.remove(), 450);
        }, 3000);
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            document.querySelectorAll('.flash-msg').forEach(msg => msg.remove());
        }
    });


    // ══════════════════════════════════════════════════════
    // 9. CONFIRM DIALOG FOR ARCHIVE FORMS
    // ══════════════════════════════════════════════════════
    // Shared UI handles data-confirm forms, including newly inserted rows.


    // ══════════════════════════════════════════════════════
    // 10. KEYBOARD SHORTCUT: Ctrl+F → focus search
    // ══════════════════════════════════════════════════════
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
    });

}); // end DOMContentLoaded