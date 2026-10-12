/**
 * ============================================================
 * BARANGAY DOYONG EDRMS — resident_record.js
 * Place in: public/js/resident_record.js
 * Requires: public/js/dashboard.js (loaded first)
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // ══════════════════════════════════════════════════════
    // 1. ELEMENTS
    // ══════════════════════════════════════════════════════
    const searchInput   = document.getElementById('residentSearch');
    const statusFilter  = document.getElementById('statusFilter');
    const zoneFilter    = document.getElementById('zoneFilter');
    const tableBody     = document.getElementById('residentsTableBody');
    const emptyRow      = document.getElementById('resEmptyRow');

    const addModal      = document.getElementById('addResidentModal');
    const editModal     = document.getElementById('editResidentModal');
    const addForm       = document.getElementById('addResidentForm');
    const editForm      = document.getElementById('editResidentForm');


    // ══════════════════════════════════════════════════════
    // 2. LIVE SEARCH + STATUS + ZONE FILTER
    //    Filters client-side instantly, then falls back to
    //    server-side on pagination clicks.
    // ══════════════════════════════════════════════════════
    function getRows() {
        return tableBody ? [...tableBody.querySelectorAll('.res-row')] : [];
    }

    function filterRows() {
        const q      = searchInput?.value.trim().toLowerCase() ?? '';
        const status = statusFilter?.value.toLowerCase() ?? '';
        const zone   = zoneFilter?.value.toLowerCase() ?? '';
        const rows   = getRows();
        let visible  = 0;

        rows.forEach(row => {
            const text      = row.textContent.toLowerCase();
            const rowStatus = row.dataset.status?.toLowerCase() ?? '';
            const rowZone   = row.dataset.zone?.toLowerCase() ?? '';

            const matchSearch = q === '' || text.includes(q);
            const matchStatus = status === '' || rowStatus === status;
            const matchZone   = zone === ''   || rowZone.includes(zone);

            if (matchSearch && matchStatus && matchZone) {
                row.classList.remove('res-row--hidden');
                visible++;
            } else {
                row.classList.add('res-row--hidden');
            }
        });

        // Toggle empty row
        if (emptyRow) {
            emptyRow.style.display = (rows.length > 0 && visible === 0) ? '' : 'none';
        }
    }

    // Debounce search for performance
    let searchTimer;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(filterRows, 200);
    });

    statusFilter?.addEventListener('change', filterRows);
    zoneFilter?.addEventListener('change', filterRows);

    // Run on page load (applies pre-filled URL params visually)
    filterRows();


    // ══════════════════════════════════════════════════════
    // 3. MODAL HELPERS
    // ══════════════════════════════════════════════════════
    function openModal(modal) {
        if (!modal) return;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Close on backdrop click
    [addModal, editModal].forEach(modal => {
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal(addModal);
            closeModal(editModal);
        }
    });


    // ══════════════════════════════════════════════════════
    // 4. ADD RESIDENT MODAL
    // ══════════════════════════════════════════════════════
    document.getElementById('openAddModal')?.addEventListener('click', () => {
        addForm?.reset();
        openModal(addModal);
        setTimeout(() => addForm?.querySelector('input[name="first_name"]')?.focus(), 100);
    });

    document.getElementById('closeAddModal')?.addEventListener('click', () => closeModal(addModal));
    document.getElementById('cancelAddModal')?.addEventListener('click', () => closeModal(addModal));

    // Loading state on submit
    addForm?.addEventListener('submit', (e) => {
        const first = addForm.querySelector('input[name="first_name"]')?.value.trim();
        const last  = addForm.querySelector('input[name="last_name"]')?.value.trim();
        const addr  = addForm.querySelector('input[name="address"]')?.value.trim();

        if (!first || !last || !addr) {
            e.preventDefault();
            window.showToast?.('Please fill in all required fields.', 'error');
            return;
        }

        const btn = addForm.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-icons-round animate-spin">sync</span> Saving...';
        }
    });


    // ══════════════════════════════════════════════════════
    // 5. EDIT RESIDENT MODAL — pre-fill from data-* attrs
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.btn-edit-resident').forEach(btn => {
        btn.addEventListener('click', () => {
            const id      = btn.dataset.id;
            const first   = btn.dataset.first ?? '';
            const middle  = btn.dataset.middle ?? '';
            const last    = btn.dataset.last ?? '';
            const suffix  = btn.dataset.suffix ?? '';
            const address = btn.dataset.address ?? '';
            const contact = btn.dataset.contact ?? '';
            const status  = btn.dataset.status  ?? 'active';
            const birthdate = btn.dataset.birthdate ?? '';
            const civilStatus = btn.dataset.civil_status ?? '';
            const zone = btn.dataset.zone ?? '';

            // Fill subtitle
            const sub = document.getElementById('editResidentSubtitle');
            if (sub) sub.textContent = `Resident #RES-${String(id).padStart(4,'0')}`;

            // Fill name part fields
            const editFirst   = document.getElementById('editFirstName');
            const editMiddle  = document.getElementById('editMiddleName');
            const editLast    = document.getElementById('editLastName');
            const editSuffix  = document.getElementById('editSuffix');
            const editAddr    = document.getElementById('editAddress');
            const editContact = document.getElementById('editContact');

            if (editFirst)  editFirst.value  = first;
            if (editMiddle) editMiddle.value = middle;
            if (editLast)   editLast.value   = last;
            if (editSuffix) editSuffix.value = suffix;
            if (editAddr)    editAddr.value    = address;
            if (editContact) editContact.value = contact;
            const editBirth = document.getElementById('editBirthdate');
            const editCivil = document.getElementById('editCivilStatus');
            const editZone  = document.getElementById('editZone');
            if (editBirth) editBirth.value = birthdate;
            if (editCivil) editCivil.value = civilStatus;
            if (editZone)  editZone.value  = zone;

            // Pre-select status radio
            const activeRadio   = document.getElementById('editStatusActive');
            const inactiveRadio = document.getElementById('editStatusInactive');
            if (activeRadio && inactiveRadio) {
                activeRadio.checked   = status === 'active';
                inactiveRadio.checked = status === 'inactive';
            }

            // Set form action URL
            if (editForm) {
                editForm.action = `/admin/residents/${id}`;
            }

            openModal(editModal);
            setTimeout(() => editFirst?.focus(), 100);
        });
    });

    document.getElementById('closeEditModal')?.addEventListener('click', () => closeModal(editModal));
    document.getElementById('cancelEditModal')?.addEventListener('click', () => closeModal(editModal));

    // Loading state on edit submit
    editForm?.addEventListener('submit', (e) => {
        const first = document.getElementById('editFirstName')?.value.trim();
        const last  = document.getElementById('editLastName')?.value.trim();
        const addr  = document.getElementById('editAddress')?.value.trim();

        if (!first || !last || !addr) {
            e.preventDefault();
            window.showToast?.('First name, last name, and address are required.', 'error');
            return;
        }

        const btn = editForm.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-icons-round animate-spin">sync</span> Saving...';
        }
    });


    // ══════════════════════════════════════════════════════
    // 6. GENERATE AVATAR INITIALS from name input (live)
    //    For the add-resident preview inside the modal
    // ══════════════════════════════════════════════════════
    function getInitials(name) {
        return name.trim().split(/\s+/).slice(0, 2)
            .map(w => w[0]?.toUpperCase() ?? '')
            .join('');
    }

    addForm?.querySelector('input[name="name"]')?.addEventListener('input', (e) => {
        const preview = document.getElementById('addAvatarPreview');
        if (preview) preview.textContent = getInitials(e.target.value) || '?';
    });


    // ══════════════════════════════════════════════════════
    // 7. HIGHLIGHT NEWLY ADDED ROW (URL param ?new_id=X)
    // ══════════════════════════════════════════════════════
    const params = new URLSearchParams(window.location.search);
    const newId  = params.get('new_id');

    if (newId) {
        const rows = getRows();
        const target = rows[0]; // newest row is first
        if (target) {
            target.style.transition = 'background .4s';
            target.style.background = '#f0fdf4';
            setTimeout(() => { target.style.background = ''; }, 2200);
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        history.replaceState({}, '', window.location.pathname);
    }


    // ══════════════════════════════════════════════════════
    // 8. KEYBOARD SHORTCUT: Ctrl+F → focus search
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