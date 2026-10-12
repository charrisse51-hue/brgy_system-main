/**
 * ============================================================
 * BARANGAY DOYONG EDRMS — user_management.js
 * Place in: public/js/user_management.js
 * Requires: public/js/dashboard.js (loaded first)
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // ══════════════════════════════════════════════════════
    // 1. ELEMENTS
    // ══════════════════════════════════════════════════════
    const searchInput  = document.getElementById('staffSearch');
    const roleFilter   = document.getElementById('roleFilter');
    const tableBody    = document.getElementById('staffTableBody');
    const emptyRow     = document.getElementById('umEmptyRow');

    const createModal  = document.getElementById('createUserModal');
    const editModal    = document.getElementById('editUserModal');
    const createForm   = document.getElementById('createUserForm');
    const editForm     = document.getElementById('editUserForm');


    // ══════════════════════════════════════════════════════
    // 2. LIVE SEARCH + ROLE FILTER
    // ══════════════════════════════════════════════════════
    function getRows() {
        return tableBody ? [...tableBody.querySelectorAll('.staff-row')] : [];
    }

    function filterRows() {
        const q    = searchInput?.value.trim().toLowerCase() ?? '';
        const role = roleFilter?.value.toLowerCase() ?? '';
        const rows = getRows();
        let visible = 0;

        rows.forEach(row => {
            const text    = row.textContent.toLowerCase();
            const rowRole = row.dataset.role?.toLowerCase() ?? '';

            const matchSearch = q === '' || text.includes(q);
            const matchRole   = role === '' || rowRole === role;

            if (matchSearch && matchRole) {
                row.classList.remove('staff-row--hidden');
                visible++;
            } else {
                row.classList.add('staff-row--hidden');
            }
        });

        if (emptyRow) {
            emptyRow.style.display = (rows.length > 0 && visible === 0) ? '' : 'none';
        }
    }

    let searchTimer;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(filterRows, 200);
    });

    roleFilter?.addEventListener('change', filterRows);
    filterRows(); // run on load


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

    [createModal, editModal].forEach(modal => {
        modal?.addEventListener('click', e => {
            if (e.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeModal(createModal);
            closeModal(editModal);
        }
    });


    // ══════════════════════════════════════════════════════
    // 4. CREATE MODAL — open / close / role note / submit
    // ══════════════════════════════════════════════════════
    const ROLE_NOTES = {
        admin:     'Administrators have full access to all system features including user management and system settings.',
        staff:     'Staff Members can manage incident reports, emergency alerts, and resident records.',
        resident:  'Residents can view their profile and submit incident reports through the resident portal.',
    };

    document.getElementById('openCreateModal')?.addEventListener('click', () => {
        createForm?.reset();
        hideRoleNote('roleNoteCreate');
        openModal(createModal);
        setTimeout(() => createForm?.querySelector('input[name="name"]')?.focus(), 100);
    });

    document.getElementById('closeCreateModal')?.addEventListener('click',  () => closeModal(createModal));
    document.getElementById('cancelCreateModal')?.addEventListener('click', () => closeModal(createModal));

    // Show role info note when role changes
    document.getElementById('createRole')?.addEventListener('change', function () {
        showRoleNote('roleNoteCreate', 'roleNoteCreateText', this.value);
    });

    function showRoleNote(noteId, textId, role) {
        const note = document.getElementById(noteId);
        const text = document.getElementById(textId);
        if (!note || !text) return;

        if (role && ROLE_NOTES[role]) {
            text.textContent = ROLE_NOTES[role];
            note.style.display = 'flex';
        } else {
            note.style.display = 'none';
        }
    }

    function hideRoleNote(noteId) {
        const note = document.getElementById(noteId);
        if (note) note.style.display = 'none';
    }

    // Validate + loading state on create submit
    createForm?.addEventListener('submit', e => {
        const name  = createForm.querySelector('input[name="name"]')?.value.trim();
        const email = createForm.querySelector('input[name="email"]')?.value.trim();
        const role  = createForm.querySelector('select[name="role"]')?.value;
        const pw    = document.getElementById('createPassword')?.value;
        const cpw   = document.getElementById('confirmPassword')?.value;

        if (!name || !email || !role || !pw) {
            e.preventDefault();
            window.showToast?.('Please fill in all required fields.', 'error');
            return;
        }

        if (pw.length < 8) {
            e.preventDefault();
            window.showToast?.('Password must be at least 8 characters.', 'error');
            return;
        }

        if (pw !== cpw) {
            e.preventDefault();
            window.showToast?.('Passwords do not match.', 'error');
            document.getElementById('confirmPassword')?.focus();
            return;
        }

        const btn = document.getElementById('createSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-icons-round animate-spin">sync</span> Creating...';
        }
    });


    // ══════════════════════════════════════════════════════
    // 5. EDIT MODAL — pre-fill from data-* attrs
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', () => {
            const id      = btn.dataset.id;
            const name    = btn.dataset.name    ?? '';
            const email   = btn.dataset.email   ?? '';
            const role    = btn.dataset.role    ?? 'staff';
            const contact = btn.dataset.contact ?? '';

            // Subtitle
            const sub = document.getElementById('editUserSubtitle');
            if (sub) sub.textContent = name;

            // Fields
            const editName    = document.getElementById('editName');
            const editEmail   = document.getElementById('editEmail');
            const editRole    = document.getElementById('editRole');
            const editContact = document.getElementById('editContact');
            const editPw      = document.getElementById('editPassword');

            if (editName)    editName.value    = name;
            if (editEmail)   editEmail.value   = email;
            if (editRole)    editRole.value    = role;
            if (editContact) editContact.value = contact;
            if (editPw)      editPw.value      = ''; // clear password field

            // Form action
            if (editForm) editForm.action = `/admin/users/${id}`;

            openModal(editModal);
            setTimeout(() => editName?.focus(), 100);
        });
    });

    document.getElementById('closeEditModal')?.addEventListener('click',  () => closeModal(editModal));
    document.getElementById('cancelEditModal')?.addEventListener('click', () => closeModal(editModal));

    // Loading state on edit submit
    editForm?.addEventListener('submit', e => {
        const name  = document.getElementById('editName')?.value.trim();
        const email = document.getElementById('editEmail')?.value.trim();

        if (!name || !email) {
            e.preventDefault();
            window.showToast?.('Name and email are required.', 'error');
            return;
        }

        // If new password entered, confirm length
        const pw = document.getElementById('editPassword')?.value;
        if (pw && pw.length < 8) {
            e.preventDefault();
            window.showToast?.('New password must be at least 8 characters.', 'error');
            return;
        }

        const btn = document.getElementById('editSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-icons-round animate-spin">sync</span> Saving...';
        }
    });


    // ══════════════════════════════════════════════════════
    // 6. PASSWORD SHOW / HIDE TOGGLE
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('.um-pw-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input    = document.getElementById(targetId);
            const icon     = btn.querySelector('.material-icons-round');
            if (!input || !icon) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.textContent = isHidden ? 'visibility_off' : 'visibility';
        });
    });


    // ══════════════════════════════════════════════════════
    // 7. CONFIRM BEFORE DELETE
    // ══════════════════════════════════════════════════════
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            const msg = form.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });


    // ══════════════════════════════════════════════════════
    // 8. KEYBOARD SHORTCUT: Ctrl+F → focus search
    // ══════════════════════════════════════════════════════
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
    });


    // ══════════════════════════════════════════════════════
    // 9. HIGHLIGHT NEWLY CREATED ROW (?new_id=X in URL)
    // ══════════════════════════════════════════════════════
    const params = new URLSearchParams(window.location.search);
    const newId  = params.get('new_id');

    if (newId) {
        const rows  = getRows();
        const first = rows[0];
        if (first) {
            first.style.transition = 'background .4s';
            first.style.background = '#f0fdf4';
            setTimeout(() => { first.style.background = ''; }, 2200);
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        history.replaceState({}, '', window.location.pathname);
    }

}); // end DOMContentLoaded
