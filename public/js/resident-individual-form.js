document.addEventListener('DOMContentLoaded', () => {
    const controllers = {};
    document.querySelectorAll('.resident-individual-modal, .resident-form-page').forEach(modal => {
        const fullPage = modal.classList.contains('resident-form-page');
        const form = modal.querySelector('form');
        const steps = [...modal.querySelectorAll('.resident-step')];
        let current = 0;
        let opener;
        let readOnly = false;
        const field = name => form.elements.namedItem(name);
        const originalSubtitle = modal.querySelector('.modal-sub').textContent;
        const detailsPanel = document.createElement('div');
        detailsPanel.className = 'resident-details-panel';
        detailsPanel.hidden = true;
        modal.querySelector('.resident-step-body').append(detailsPanel);
        function renderDetails(values) {
            const element = (tag, className, text) => {
                const node = document.createElement(tag);
                node.className = className;
                if (text !== undefined) node.textContent = text;
                return node;
            };
            const display = name => {
                const value = values[name];
                if (value === null || value === undefined || value === '') return '—';
                if (name === 'birthdate') {
                    const date = new Date(`${value}T00:00:00`);
                    return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                }
                const input = field(name);
                const optionValue = typeof value === 'boolean' ? (value ? '1' : '0') : String(value);
                if (input?.tagName === 'SELECT') return [...input.options].find(option => option.value === optionValue)?.textContent || String(value);
                return Array.isArray(value) ? (value.join(', ') || '—') : String(value);
            };
            detailsPanel.replaceChildren();
            const name = [values.first_name, values.middle_name, values.last_name, values.suffix].filter(Boolean).join(' ');
            const summary = element('div', 'resident-details-summary');
            summary.append(element('div', 'resident-details-avatar', [values.first_name, values.last_name].filter(Boolean).map(part => part[0]).join('').toUpperCase()));
            const identity = element('div', 'resident-details-identity');
            identity.append(element('h4', '', name || 'Resident'), element('p', '', `Resident ID: RES-${String(values.id ?? '').padStart(4, '0')}`), element('p', '', values.address || [values.house_number, values.street, values.barangay].filter(Boolean).join(', ') || '—'));
            summary.append(identity);
            detailsPanel.append(summary);
            const grid = element('div', 'resident-details-grid');
            const icons = ['location_on', 'person', 'accessibility_new', 'badge'];
            // Use the form's sections and labels so every resident field stays in sync.
            const sections = steps.filter(step => step.querySelector('[data-field]')).map((step, index) => [
                step.getAttribute('aria-label'),
                icons[index] || 'info',
                [...step.querySelectorAll('[data-field]')].map(wrapper => [
                    wrapper.querySelector('label.em-label, legend').textContent.replace('*', '').trim(),
                    wrapper.dataset.field
                ])
            ]);
            sections.forEach(([title, icon, rows]) => {
                const section = element('section', 'resident-details-section');
                const heading = element('h5', '');
                const symbol = element('span', 'material-icons-round', icon);
                symbol.setAttribute('aria-hidden', 'true');
                heading.append(symbol, document.createTextNode(title));
                const list = element('dl', '');
                rows.forEach(([label, key]) => list.append(element('dt', '', label), element('dd', '', display(key))));
                section.append(heading, list);
                grid.append(section);
            });
            detailsPanel.append(grid);
        }
        const checkedGroups = () => [...form.querySelectorAll('[name="target_groups[]"]:checked')].map(input => input.value);
        function conditions() {
            const groups = checkedGroups();
            const pwd = groups.includes('PWD');
            const visible = {
                pwd, pwd_other: pwd && field('pwd_classification').value === 'Others',
                senior: groups.includes('Senior Citizen'), voter: field('registered_voter').value === '1'
            };
            modal.querySelectorAll('[data-condition]').forEach(wrapper => {
                wrapper.hidden = !visible[wrapper.dataset.condition];
                wrapper.querySelectorAll('input, select').forEach(input => {
                    input.disabled = wrapper.hidden;
                    input.required = !wrapper.hidden && ['pwd_classification', 'pwd_other'].includes(input.name);
                });
            });
        }
        function review() {
            const output = modal.querySelector('.resident-review');
            output.replaceChildren();
            steps.slice(0, 4).forEach(step => {
                const heading = document.createElement('h5');
                heading.textContent = step.getAttribute('aria-label');
                output.append(heading);
                const list = document.createElement('dl');
                step.querySelectorAll('[data-field]').forEach(wrapper => {
                    if (wrapper.hidden) return;
                    const label = wrapper.querySelector('label.em-label, legend');
                    const inputs = [...wrapper.querySelectorAll('input, select')];
                    const values = inputs.filter(input => !input.disabled && (input.type !== 'checkbox' || input.checked)).map(input => input.tagName === 'SELECT' ? (input.value ? input.selectedOptions[0].textContent : '') : input.value).filter(Boolean);
                    const term = document.createElement('dt');
                    term.textContent = label.textContent.replace('*', '').trim();
                    const description = document.createElement('dd');
                    description.textContent = values.join(', ') || 'Not provided';
                    list.append(term, description);
                });
                output.append(list);
            });
        }
        function show(index, focus = true) {
            current = index;
            conditions();
            steps.forEach((step, i) => { step.hidden = i !== index; });
            modal.querySelectorAll('[data-step-link]').forEach((button, i) => {
                if (i === index) button.setAttribute('aria-current', 'step');
                else button.removeAttribute('aria-current');
            });
            modal.querySelector('[data-resident-back]').disabled = index === 0;
            modal.querySelector('[data-resident-next]').hidden = index === 4;
            modal.querySelector('[data-resident-save]').hidden = index !== 4;
            if (index === 4) review();
            modal.querySelector('.resident-step-body').scrollTop = 0;
            if (focus) steps[index].querySelector('input:not(:disabled), select:not(:disabled)')?.focus();
        }
        function validateThrough(index) {
            conditions();
            for (let i = 0; i <= Math.min(index, 3); i++) {
                const invalid = [...steps[i].querySelectorAll('input, select')].find(input => !input.disabled && !input.checkValidity());
                if (invalid) { show(i, false); invalid.focus(); invalid.reportValidity(); return false; }
            }
            return true;
        }
        function fill(values = {}) {
            form.reset();
            form.querySelectorAll('[data-field] input, [data-field] select').forEach(input => {
                const name = input.name.replace('[]', '');
                let value = Object.hasOwn(values, name) ? values[name] : (input.dataset.default ?? '');
                if (name === 'sitio_purok' && input.tagName === 'SELECT' && value) {
                    const normalize = text => String(text).trim().replace(/^sit[i]?o\s+/i, '').toLowerCase();
                    const match = [...input.options].find(option => option.value && normalize(option.value) === normalize(value));
                    if (match) value = match.value;
                    else if (![...input.options].some(option => option.value === String(value))) {
                        // Preserve an older address value rather than clearing it on edit.
                        input.add(new Option(`${value} (current record)`, value));
                    }
                }
                if (input.type === 'checkbox') input.checked = Array.isArray(value) && value.includes(input.value);
                else {
                    if (typeof value === 'boolean') value = value ? '1' : '0';
                    input.value = value ?? '';
                }
            });
            field('_resident_id').value = values.id ?? values._resident_id ?? '';
            const save = modal.querySelector('[data-resident-save]');
            save.disabled = false;
            show(0, false);
        }
        function open(values = {}, action = null, restore = false, viewing = false) {
            readOnly = viewing;
            modal.classList.toggle('resident-details-mode', viewing);
            detailsPanel.hidden = !viewing;
            modal.querySelector('.modal-sub').textContent = viewing ? 'Complete information for this resident' : originalSubtitle;
            opener = document.activeElement;
            fill(values);
            if (action) form.action = action;
            if (!restore) modal.querySelectorAll('.resident-form-errors, .resident-field-error').forEach(element => element.hidden = true);
            if (!fullPage) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
            let target = 0;
            if (restore) {
                const errors = JSON.parse(modal.dataset.errors);
                const found = steps.findIndex(step => [...step.querySelectorAll('[data-field]')].some(wrapper => errors.some(error => error.split('.')[0] === wrapper.dataset.field)));
                if (found >= 0) target = found;
            }
            show(target);
            modal.querySelector('.resident-steps').hidden = viewing;
            modal.querySelector('.modal-title').textContent = viewing ? 'Resident Details' : (modal.dataset.mode === 'edit' ? 'Edit Resident' : 'Add New Resident');
            if (viewing) {
                steps.forEach(step => { step.hidden = true; });
                renderDetails(values);
                modal.querySelector('[data-resident-back]').hidden = true;
                modal.querySelector('[data-resident-save]').hidden = true;
                modal.querySelector('[data-resident-close]').focus();
            } else {
                modal.querySelector('[data-resident-back]').hidden = false;
            }
        }
        function close() { modal.style.display = 'none'; document.body.style.overflow = ''; opener?.focus(); }
        form.addEventListener('change', conditions);
        modal.querySelectorAll('[data-resident-close]').forEach(button => button.addEventListener('click', close));
        modal.querySelector('[data-resident-next]').addEventListener('click', () => { if (validateThrough(current)) show(current + 1); });
        modal.querySelector('[data-resident-back]').addEventListener('click', () => show(Math.max(0, current - 1)));
        modal.querySelectorAll('[data-step-link]').forEach(button => button.addEventListener('click', () => {
            const target = Number(button.dataset.stepLink);
            if (target < current || validateThrough(target - 1)) show(target);
        }));
        form.addEventListener('submit', event => {
            if (readOnly) { event.preventDefault(); return; }
            if (current !== 4) { event.preventDefault(); if (validateThrough(current)) show(current + 1); return; }
            if (!validateThrough(3)) { event.preventDefault(); return; }
            modal.querySelector('[data-resident-save]').disabled = true;
        });
        modal.addEventListener('click', event => { if (!fullPage && event.target === modal) close(); });
        modal.addEventListener('keydown', event => {
            if (fullPage) return;
            if (event.key === 'Escape') { event.preventDefault(); close(); }
            if (event.key !== 'Tab') return;
            const focusable = [...modal.querySelectorAll('button, input, select, a[href]')].filter(element => !element.disabled && element.getClientRects().length);
            const first = focusable[0], last = focusable.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        });
        controllers[modal.dataset.mode] = { open };
        if (fullPage || modal.dataset.restore === '1') open(JSON.parse(modal.dataset.values), null, modal.dataset.restore === '1');
    });
    initSplitPanel(controllers);
    document.addEventListener('resident-results-updated', () => initSplitPanel(controllers));

    document.getElementById('openAddModal')?.addEventListener('click', () => controllers.add?.open());
    document.querySelectorAll('.btn-edit-resident').forEach(button => button.addEventListener('click', (e) => {
        e.stopPropagation();
        controllers.edit.open(JSON.parse(button.dataset.resident), button.dataset.updateUrl);
    }));

    const restoring = document.querySelector('[data-restore="1"]');
    const editing = document.getElementById('residentEditData');
    if (editing && !restoring) controllers.edit.open(JSON.parse(editing.dataset.resident), editing.dataset.updateUrl);
    if (new URLSearchParams(location.search).get('add') === '1' && !restoring) controllers.add.open();
    const newId = new URLSearchParams(location.search).get('new_id');
    if (newId) {
        const row = document.querySelector(`.res-row[data-id="${newId}"]`);
        if (row) {
            row.classList.add('resident-new-row');
            row.click();
        }
    }

    function initSplitPanel(controllers) {
        const splitContainer = document.getElementById('resSplitContainer');
        if (!splitContainer) return;

        const rows = document.querySelectorAll('.res-row');
        const editBtn = document.getElementById('splitEditResidentBtn');
        const addBtn = document.getElementById('splitAddResidentBtn');
        const emptyAddBtn = document.getElementById('emptyAddBtn');
        const backToListBtn = document.getElementById('resBackToListBtn');

        let activeResident = null;
        let activeUpdateUrl = null;
        let activeEditUrl = null;

        function formatCurrency(val) {
            if (val === null || val === undefined || val === '') return '—';
            const num = Number(val);
            return isNaN(num) ? String(val) : '₱' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatDate(val) {
            if (!val) return '—';
            const d = new Date(`${val}T00:00:00`);
            return Number.isNaN(d.getTime()) ? val : d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        }

        function maskPhilsys(val) {
            if (!val) return '—';
            const s = String(val);
            return s.length > 4 ? ('••••-••••-' + s.slice(-4)) : s;
        }

        function updateProfilePanel(res) {
            if (!res) return;
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = (val !== null && val !== undefined && String(val).trim() !== '') ? val : '—';
            };

            // Avatar & Name
            const fullName = res.full_name || [res.first_name, res.middle_name, res.last_name, res.suffix].filter(Boolean).join(' ') || 'Resident';
            const initials = [res.first_name, res.last_name].filter(Boolean).map(s => s[0]).join('').toUpperCase() || 'R';
            const avatarEl = document.getElementById('resProfileAvatar');
            if (avatarEl) avatarEl.textContent = initials;
            const nameEl = document.getElementById('resProfileName');
            if (nameEl) nameEl.textContent = fullName;

            // Resident ID
            const idEl = document.getElementById('resProfileId');
            if (idEl) idEl.textContent = '#RES-' + String(res.id ?? '').padStart(4, '0');
            const locText = document.getElementById('resProfileLocationText');
            if (locText) locText.textContent = res.sitio_purok || res.purok || 'Sitio Not Specified';

            // Section A: Address Information
            setVal('resVal_barangay', res.barangay || 'Doyong');
            setVal('resVal_sitio_purok', res.sitio_purok || res.purok);
            setVal('resVal_house_number', res.house_number);
            setVal('resVal_street', res.street);
            setVal('resVal_subdivision', res.subdivision);
            setVal('resVal_zone', res.zone ? (res.zone.charAt(0).toUpperCase() + res.zone.slice(1)) : null);
            setVal('resVal_address', res.address);

            // Section B: Personal Information
            setVal('resVal_id', '#RES-' + String(res.id ?? '').padStart(4, '0'));
            setVal('resVal_philsys_card_number', maskPhilsys(res.philsys_card_number));
            setVal('resVal_first_name', res.first_name);
            setVal('resVal_middle_name', res.middle_name);
            setVal('resVal_last_name', res.last_name);
            setVal('resVal_suffix', res.suffix);
            setVal('resVal_birthdate', formatDate(res.birthdate));
            setVal('resVal_age', (res.age !== null && res.age !== undefined) ? (res.age + ' yrs old') : null);
            setVal('resVal_birthplace', res.birthplace);
            setVal('resVal_sex', res.sex);
            setVal('resVal_religion', res.religion);
            setVal('resVal_citizenship', res.citizenship || 'Filipino');
            setVal('resVal_ethnicity', res.ethnicity);
            setVal('resVal_civil_status', res.civil_status ? (res.civil_status.charAt(0).toUpperCase() + res.civil_status.slice(1)) : null);
            setVal('resVal_educational_attainment', res.educational_attainment);
            setVal('resVal_school_location', res.school_location);
            setVal('resVal_labor_force_status', res.labor_force_status);
            setVal('resVal_occupation', res.occupation);
            setVal('resVal_monthly_income', formatCurrency(res.monthly_income));
            const incomeSources = Array.isArray(res.income_sources) ? (res.income_sources.join(', ') || null) : res.income_sources;
            setVal('resVal_income_sources', incomeSources);

            // Section C: Vulnerable / Target Groups
            const targetGroups = Array.isArray(res.target_groups) ? (res.target_groups.join(', ') || 'None / Not Applicable') : (res.target_groups || 'None / Not Applicable');
            setVal('resVal_target_groups', targetGroups);
            setVal('resVal_pwd_classification', res.pwd_classification);
            setVal('resVal_pwd_other', res.pwd_other);
            setVal('resVal_out_of_school_youth', res.out_of_school_youth === null || res.out_of_school_youth === undefined ? null : (res.out_of_school_youth ? 'Yes' : 'No'));
            setVal('resVal_can_read_write', res.can_read_write === null || res.can_read_write === undefined ? null : (res.can_read_write ? 'Yes' : 'No'));
            setVal('resVal_immunization_status', res.immunization_status);
            setVal('resVal_senior_citizen_number', res.senior_citizen_number);

            // Section D: Contact & Identity
            setVal('resVal_email', res.email);
            setVal('resVal_contact_number', res.contact_number);
            setVal('resVal_blood_type', res.blood_type);
            setVal('resVal_height_cm', res.height_cm ? (res.height_cm + ' cm') : null);
            setVal('resVal_weight_kg', res.weight_kg ? (res.weight_kg + ' kg') : null);
            setVal('resVal_registered_voter', res.registered_voter === null || res.registered_voter === undefined ? null : (res.registered_voter ? 'Yes' : 'No'));
            setVal('resVal_last_year_voted', res.last_year_voted);
            setVal('resVal_voter_location', res.voter_location);
            const motherName = [res.mother_first_name, res.mother_middle_name, res.mother_last_name].filter(Boolean).join(' ');
            setVal('resVal_mother_name', motherName);

            // Section E: Record Information
            setVal('resVal_created_at', res.created_at_formatted || res.created_at);
            setVal('resVal_updated_at', res.updated_at_formatted || res.updated_at);

            if (editBtn) editBtn.disabled = false;
        }

        function selectRow(row) {
            rows.forEach(r => {
                r.classList.remove('is-selected');
                r.setAttribute('aria-selected', 'false');
            });
            row.classList.add('is-selected');
            row.setAttribute('aria-selected', 'true');
            const res = JSON.parse(row.dataset.resident);
            activeResident = res;
            activeUpdateUrl = row.dataset.updateUrl;
            activeEditUrl = row.dataset.editUrl;
            updateProfilePanel(res);

            if (window.innerWidth <= 800 && splitContainer) {
                splitContainer.classList.add('res-show-details');
            }
        }

        rows.forEach(row => {
            row.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectRow(row);
                }
            });
            row.addEventListener('click', (e) => {
                if (e.target.closest('button, a, input')) return;
                selectRow(row);
            });
        });

        const initialSelectedRow = document.querySelector('.res-row.is-selected') || rows[0];
        if (initialSelectedRow && initialSelectedRow.dataset.resident) {
            activeResident = JSON.parse(initialSelectedRow.dataset.resident);
            activeUpdateUrl = initialSelectedRow.dataset.updateUrl;
            activeEditUrl = initialSelectedRow.dataset.editUrl;
        }

        if (editBtn) {
            editBtn.addEventListener('click', () => {
                if (activeEditUrl) { window.location.assign(activeEditUrl); return; }
                if (activeResident && activeUpdateUrl && controllers.edit) {
                    controllers.edit.open(activeResident, activeUpdateUrl);
                }
            });
        }

        const addResident = button => {
            if (button.dataset.createUrl) window.location.assign(button.dataset.createUrl);
            else controllers.add?.open();
        };
        if (addBtn) addBtn.addEventListener('click', () => addResident(addBtn));
        if (emptyAddBtn) emptyAddBtn.addEventListener('click', () => addResident(emptyAddBtn));

        if (backToListBtn && splitContainer) {
            backToListBtn.addEventListener('click', () => {
                splitContainer.classList.remove('res-show-details');
            });
        }


    }
});
