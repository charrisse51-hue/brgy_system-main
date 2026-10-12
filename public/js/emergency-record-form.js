document.addEventListener('DOMContentLoaded', () => {
    const form=document.getElementById('emergency-record-form');
    if (!form) return;
    const schema=JSON.parse(document.getElementById('er-schema').textContent);
    const errors=JSON.parse(document.getElementById('er-errors').textContent);
    const payload=document.getElementById('er-entries');
    let entries;
    try { entries=JSON.parse(payload.value); } catch { entries={}; }
    if (!entries || typeof entries!=='object' || Array.isArray(entries)) entries={};
    for (const key of Object.keys(schema)) if (!Array.isArray(entries[key])) entries[key]=[];
    let dirty=Object.keys(errors).length>0, saving=false;
    const dialog=document.getElementById('er-entry-editor'), entryForm=document.getElementById('er-entry-form'), fields=document.getElementById('er-entry-fields');
    let groupKey, rowIndex, category, opener, baseline;
    const node=(tag,text,cls)=>{ const el=document.createElement(tag); if(text!==undefined) el.textContent=text; if(cls) el.className=cls; return el; };
    const sync=()=>{ payload.value=JSON.stringify(entries); };
    const display=(value,field)=>value===null || value===undefined || value==='' ? '—' : (field.options?.[value] ?? (field.type==='datetime-local' ? String(value).replace('T',' ') : String(value)));
    function clearErrors(group,index,removed=false) {
        const prefix=`entries.${group}.`, shifted={};
        for (const key of Object.keys(errors)) {
            if (!key.startsWith(prefix)) continue;
            const [i,...parts]=key.slice(prefix.length).split('.');
            if (Number(i)===index) delete errors[key];
            else if (removed && Number(i)>index) { shifted[`${prefix}${Number(i)-1}.${parts.join('.')}`]=errors[key]; delete errors[key]; }
        }
        Object.assign(errors,shifted);
    }
    function render() {
        document.querySelectorAll('[data-entry-table]').forEach(container=>{
            const group=container.dataset.entryTable, filter=container.dataset.category;
            const columns=group==='casualties' ? [['name',{label:'Full Name',type:'text'}], ...Object.entries(schema[group].fields).filter(([key])=>!['casualty_type','first_name','middle_name','last_name'].includes(key))] : Object.entries(schema[group].fields).filter(([key])=>key!=='casualty_type');
            container.replaceChildren();
            const rows=entries[group].map((row,index)=>({row,index})).filter(({row})=>row && (!filter || row.casualty_type===filter));
            if (!rows.length) container.append(node('p','No entries added.','er-empty'));
            else {
                const wrap=node('div',undefined,'er-table-wrap'), table=node('table',undefined,'er-table'), head=node('thead'), headings=node('tr'), body=node('tbody');
                table.setAttribute('aria-label',filter ? schema.casualties.fields.casualty_type.options[filter] : schema[group].title);
                for (const [,field] of columns) { const th=node('th',field.label); th.scope='col'; headings.append(th); }
                headings.append(node('th','Actions')); head.append(headings); table.append(head);
                for (const {row,index} of rows) {
                    const tr=node('tr');
                    for (const [key,field] of columns) {
                        const td=node('td',display(group==='casualties' && key==='name' ? (row.name || [row.first_name,row.middle_name,row.last_name].filter(Boolean).join(' ')) : row[key],field));
                        const errorKeys=group==='casualties' && key==='name' ? ['first_name','middle_name','last_name'].map(part=>`entries.${group}.${index}.${part}`) : [`entries.${group}.${index}.${key}`];
                        for (const errorKey of errorKeys) for (const message of errors[errorKey] || []) td.append(node('small',message,'er-error'));
                        tr.append(td);
                    }
                    const actions=node('td'), edit=node('button','Edit','btn btn-ghost'), remove=node('button','Remove','btn btn-ghost');
                    edit.type=remove.type='button';
                    edit.addEventListener('click',()=>openEntry(group,filter,index,edit));
                    remove.addEventListener('click',async()=>{
                        if (!await window.uiConfirm('Remove this entry?')) return;
                        entries[group].splice(index,1); clearErrors(group,index,true); dirty=true; sync(); render();
                        document.querySelector(`[data-add-entry="${group}"]`)?.focus();
                    });
                    actions.append(edit,remove); tr.append(actions); body.append(tr);
                }
                table.append(body); wrap.append(table); container.append(wrap);
            }
            for (const message of errors[`entries.${group}`] || []) container.append(node('p',message,'er-error'));
            if (Object.keys(errors).some(key=>key.startsWith(`entries.${group}`))) container.closest('details')?.setAttribute('open','');
        });
        document.querySelectorAll('[data-entry-count]').forEach(el=>{ const count=entries[el.dataset.entryCount].length; el.textContent=`${count} ${count===1 ? 'entry' : 'entries'}`; });
        document.querySelectorAll('[data-add-entry]').forEach(button=>{ button.disabled=entries[button.dataset.addEntry].length>=100; });
    }
    function total() {
        if (groupKey==='damagedHouses') entryForm.elements.total.value=Number(entryForm.elements.totally_damaged.value || 0)+Number(entryForm.elements.partially_damaged.value || 0);
    }
    async function openEntry(group,selectedCategory,index,trigger) {
        if (!dialog.hidden && entryDirty() && !await window.uiConfirm('Discard changes to this entry?')) return;
        groupKey=group; category=selectedCategory; rowIndex=index; opener=trigger;
        const row=index===null ? {} : entries[group][index], person=group==='casualties';
        if (person && row.name && row.first_name===undefined) { const parts=row.name.trim().split(/\s+/u); row.last_name=parts.length>1 ? parts.pop() : ''; row.first_name=parts.join(' '); row.middle_name=''; }
        fields.replaceChildren();
        fields.classList.toggle('er-casualty-grid', person);
        document.getElementById('er-entry-title').textContent=`${index===null ? 'Add' : 'Edit'} ${person ? schema.casualties.fields.casualty_type.options[category]+' Person' : schema[group].title}`;
        document.getElementById('er-save-entry').textContent=index===null ? (person ? 'Add Person' : 'Add Entry') : 'Save Changes';
        for (const [key,field] of Object.entries(schema[group].fields)) {
            if (key==='casualty_type') continue;
            const wrapper=node('div',undefined,`er-field${field.type==='textarea' || ['address','description'].includes(key) ? ' er-wide' : ''}`), label=node('label',field.label+(field.required ? ' *' : ''));
            label.htmlFor=`entry-${key}`;
            let input;
            if (field.type==='select') {
                input=node('select'); input.append(new Option('Select...',''));
                for (const [value,text] of Object.entries(field.options)) input.append(new Option(text,value));
            } else if (field.type==='textarea') { input=node('textarea'); input.rows=3; input.maxLength=20000; }
            else {
                input=node('input'); input.type=['integer','age','decimal','computed'].includes(field.type) ? 'number' : field.type;
                if (input.type==='number') { input.min='0'; input.step=field.type==='decimal' ? '0.01' : '1'; input.max=field.type==='age' ? '120' : field.type==='decimal' ? '999999999999.99' : '1000000000'; }
                if (input.type==='text') input.maxLength=255;
                if (field.type==='computed') input.readOnly=true;
            }
            input.setAttribute('form','er-entry-form'); input.id=`entry-${key}`; input.name=key; input.required=field.required; input.value=row[key] ?? '';
            if (person && ['first_name','middle_name','last_name','age','address'].includes(key)) { input.dataset.personField=key; wrapper.classList.add(`er-person-${key}`); if (key.endsWith('_name')) input.maxLength=50; }
            wrapper.append(label,input);
            const messages=errors[`entries.${group}.${index}.${key}`] || [];
            if (messages.length) { input.setAttribute('aria-invalid','true'); const message=node('small',messages.join(' '),'er-error'); message.id=`entry-error-${key}`; input.setAttribute('aria-describedby',message.id); wrapper.append(message); }
            fields.append(wrapper);
        }
        total(); baseline=JSON.stringify([...new FormData(entryForm)]); trigger.closest('.er-accordion-body,.er-entry-section').append(dialog); dialog.hidden=false; fields.querySelector('input,select,textarea')?.focus();
    }
    const entryDirty=()=>!dialog.hidden && JSON.stringify([...new FormData(entryForm)])!==baseline;
    const close=async()=>{ if (entryDirty() && !confirm('Discard changes to this entry?')) return; dialog.hidden=true; fields.replaceChildren(); opener?.focus(); };
    dialog.addEventListener('keydown',event=>{ if(event.key==='Escape') { event.preventDefault(); close(); } });
    dialog.querySelectorAll('[data-close-entry]').forEach(button=>button.addEventListener('click',close));
    document.querySelectorAll('[data-add-entry]').forEach(button=>button.addEventListener('click',()=>openEntry(button.dataset.addEntry,button.dataset.category,null,button)));
    fields.addEventListener('input',()=>{ total(); entryForm.elements.restored_at?.setCustomValidity(''); });
    entryForm.noValidate=true;
    function validateEntry() {
        let invalid;
        fields.querySelectorAll('input,select,textarea').forEach(input=>{
            input.setCustomValidity('');
            const message=input.dataset.personField ? window.PersonValidation.validate(input, fields, true) : (input.checkValidity() ? null : input.validationMessage);
            setFieldError(input,message);
            if (message && !invalid) invalid=input;
        });
        return invalid;
    }
    fields.addEventListener('blur',event=>{
        const input=event.target;
        if (input.dataset.personField) setFieldError(input,window.PersonValidation.validate(input,fields,true));
    },true);
    fields.addEventListener('input',event=>{
        const input=event.target;
        if (input.dataset.personField && input.getAttribute('aria-invalid')==='true') setFieldError(input,window.PersonValidation.validate(input,fields,true));
    });
    entryForm.addEventListener('submit',event=>{
        event.preventDefault();
        const invalid=validateEntry(); if (invalid) { invalid.focus(); return; }
        const row=Object.fromEntries(new FormData(entryForm)), start=row.interrupted_at || row.reported_at;
        if (row.restored_at && start && row.restored_at<start) { entryForm.elements.restored_at.setCustomValidity('Restoration must be on or after the interruption or report time.'); entryForm.elements.restored_at.reportValidity(); return; }
        if (groupKey==='casualties') { row.casualty_type=category; row.name=[row.first_name,row.middle_name,row.last_name].filter(Boolean).join(' '); }
        if (rowIndex===null) entries[groupKey].push(row); else entries[groupKey][rowIndex]=row;
        clearErrors(groupKey,rowIndex); dirty=true; sync(); render(); dialog.hidden=true; fields.replaceChildren();
        document.querySelector(`[data-add-entry="${groupKey}"]`)?.focus();
    });
    const updateMain=()=>{
        const progress=form.elements.report_type.value==='progress';
        document.querySelector('[data-main-field="progress_report_no"]').hidden=!progress;
        form.elements.progress_report_no.disabled=!progress; form.elements.progress_report_no.required=progress;
        const date=form.elements.incident_date.value;
        document.getElementById('er-day').value=date ? new Date(`${date}T12:00:00`).toLocaleDateString('en-US',{weekday:'long'}) : '';
        form.elements.date_verified.disabled=form.elements.verification_status.value!=='verified';
    };
    const SITIOS = [
        'Sitio Cabuaan',
        'Sitio Camanggaan',
        'Sitio Gonzales',
        'Sitio Basasong',
        'Sitio Doyong Centro',
        'Sitio Baba',
        'Sitio Central'
    ];

    function syncReporterHiddenFields() {
        const fn = form.elements.reporter_first_name?.value.trim() || '';
        const mn = form.elements.reporter_middle_name?.value.trim() || '';
        const ln = form.elements.reporter_last_name?.value.trim() || '';
        const fullName = [fn, mn, ln].filter(Boolean).join(' ');
        if (form.elements.reporter_name) form.elements.reporter_name.value = fullName;

        const sitio = form.elements.reporter_sitio?.value || '';
        const other = form.elements.reporter_other_address?.value.trim() || '';
        const fullAddr = [sitio, other].filter(Boolean).join(', ');
        if (form.elements.reporter_address) form.elements.reporter_address.value = fullAddr;
    }

    function validateField(input) {
        if (!input || !input.name || input.type === 'hidden' || input.disabled || input.readOnly) return null;
        if (input.form !== form) return null;

        if (input.name.startsWith('reporter_')) {
            const key = {reporter_first_name:'first_name',reporter_middle_name:'middle_name',reporter_last_name:'last_name',reporter_age:'age',reporter_other_address:'address'}[input.name];
            if (key) { input.dataset.personField=key; return window.PersonValidation.validate(input, form); }
        }
        const val = input.value.trim();
        const name = input.name;
        const isRequired = input.required || input.hasAttribute('required');

        const hasAnyReporter = !!(
            form.elements.reporter_first_name?.value.trim() ||
            form.elements.reporter_middle_name?.value.trim() ||
            form.elements.reporter_last_name?.value.trim() ||
            form.elements.reporter_age?.value.trim() ||
            form.elements.reporter_sitio?.value ||
            form.elements.reporter_other_address?.value.trim()
        );

        if (name === 'reporter_first_name' || name === 'reporter_last_name') {
            if (hasAnyReporter && val === '') {
                return (name === 'reporter_first_name' ? 'First name' : 'Last name') + ' is required.';
            }
            if (val !== '') {
                if (/\d/.test(val)) return 'Numbers are not allowed in the name.';
                if (/[^a-zA-Z\u00C0-\u024F\u1E00-\u1EFF\s\-\']/.test(val)) return 'Special characters are not allowed in the name.';
                if (val.length < 2) return (name === 'reporter_first_name' ? 'First name' : 'Last name') + ' must contain at least 2 characters.';
                if (val.length > 50) return (name === 'reporter_first_name' ? 'First name' : 'Last name') + ' cannot exceed 50 characters.';
            }
            return null;
        }

        if (name === 'reporter_middle_name') {
            if (val !== '') {
                if (/\d/.test(val)) return 'Numbers are not allowed in the name.';
                if (/[^a-zA-Z\u00C0-\u024F\u1E00-\u1EFF\s\-\']/.test(val)) return 'Special characters are not allowed in the name.';
                if (val.length > 50) return 'Middle name cannot exceed 50 characters.';
            }
            return null;
        }

        if (name === 'reporter_sitio') {
            if (val !== '' && !SITIOS.includes(val)) {
                return 'Please select a valid sitio.';
            }
            return null;
        }

        if (name === 'reporter_age') {
            if (val !== '') {
                const num = Number(val);
                if (!/^\d+$/.test(val) || isNaN(num) || num < 0 || num > 120) {
                    return 'Please enter a valid age (0–120).';
                }
            }
            return null;
        }

        if (name === 'reporter_contact') {
            if (val !== '') {
                if (!/^09\d{9}$/.test(val)) {
                    return 'Please enter a valid Philippine mobile number (09XXXXXXXXX).';
                }
            }
            return null;
        }

        if (isRequired && val === '') {
            if (name === 'incident_date') return 'Please select a valid date.';
            if (name === 'incident_time') return 'Please select a valid time.';
            if (name === 'report_date_time') return 'Please select a valid date & time.';
            return 'This field is required.';
        }

        if (name === 'incident_date' || name === 'incident_time') {
            const dateInput = form.elements.incident_date;
            const timeInput = form.elements.incident_time;
            if (dateInput?.value && timeInput?.value) {
                const dt = new Date(`${dateInput.value}T${timeInput.value}`);
                if (!isNaN(dt.getTime()) && dt > new Date()) {
                    return 'Incident date and time cannot be in the future.';
                }
            }
        }

        return null;
    }

    function setFieldError(input, errorMsg) {
        let wrapper = input.closest('.er-field') || input.parentElement;
        let errorEl = wrapper ? wrapper.querySelector('.er-error') : null;

        if (errorMsg) {
            input.setAttribute('aria-invalid', 'true');
            if (!errorEl) {
                errorEl = document.createElement('small');
                errorEl.className = 'er-error';
                errorEl.id = 'error-' + (input.id || input.name.replace(/[^a-zA-Z0-9_-]/g, '_'));
                if (wrapper) wrapper.appendChild(errorEl);
            }
            errorEl.textContent = errorMsg;
            input.setAttribute('aria-describedby', errorEl.id);
        } else {
            input.removeAttribute('aria-invalid');
            if (errorEl) {
                errorEl.remove();
            }
            input.removeAttribute('aria-describedby');
        }
        updateTabErrors();
    }

    function updateTabErrors() {
        panels.forEach((panel, i) => {
            const hasEntryErrors = [...panel.querySelectorAll('[data-entry-table]')].some(el => Object.keys(errors).some(key => key === `entries.${el.dataset.entryTable}` || key.startsWith(`entries.${el.dataset.entryTable}.`)));
            const hasErr = !!panel.querySelector('[aria-invalid="true"]') || hasEntryErrors;
            tabs[i].classList.toggle('er-tab-has-errors', hasErr);
        });
    }

    function validatePanel(panel) {
        const inputs = [...panel.querySelectorAll('input, select, textarea')];
        let firstInvalid = null;

        inputs.forEach(input => {
            const error = validateField(input);
            setFieldError(input, error);
            if (error && !firstInvalid) {
                firstInvalid = input;
            }
        });

        return firstInvalid;
    }

    form.addEventListener('blur', event => {
        const target = event.target;
        if (target && target.form === form && target.name && target.type !== 'hidden') {
            syncReporterHiddenFields();
            const error = validateField(target);
            setFieldError(target, error);
        }
    }, true);

    form.addEventListener('input', event => {
        if (event.target.form === form) {
            dirty = true;
            syncReporterHiddenFields();
            updateMain();
            if (event.target.getAttribute('aria-invalid') === 'true') {
                const error = validateField(event.target);
                setFieldError(event.target, error);
            }
        }
    });

    form.addEventListener('change', event => {
        if (event.target.form === form) {
            dirty = true;
            syncReporterHiddenFields();
            updateMain();
            if (event.target.getAttribute('aria-invalid') === 'true' || event.target.tagName === 'SELECT') {
                const error = validateField(event.target);
                setFieldError(event.target, error);
            }
        }
    });

    for (const [name,key] of Object.entries({reporter_first_name:'first_name',reporter_middle_name:'middle_name',reporter_last_name:'last_name',reporter_age:'age',reporter_other_address:'address',reporter_sitio:'sitio'})) if (form.elements[name]) form.elements[name].dataset.personField=key;

    const tabs = [...form.querySelectorAll('[role="tab"]')];
    const panels = [...form.querySelectorAll('[role="tabpanel"]')];
    const previous = document.getElementById('er-previous'), next = document.getElementById('er-next');
    let active = 0;

    function showTab(index, focus = false) {
        active = index;
        tabs.forEach((tab, i) => {
            tab.setAttribute('aria-selected', String(i === index));
            tab.tabIndex = (i === index) ? 0 : -1;
            panels[i].hidden = (i !== index);
        });
        previous.hidden = (index === 0);
        next.hidden = (index === tabs.length - 1);
        form.querySelector('[data-discard]').hidden = (index !== tabs.length - 1);
        document.getElementById('save-emergency').hidden = (index !== tabs.length - 1);
        document.getElementById('er-step-label').textContent = `Section ${index + 1} of ${tabs.length}`;
        if (focus) tabs[index].focus();
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => showTab(index));
        tab.addEventListener('keydown', event => {
            let target;
            if (event.key === 'ArrowRight') target = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') target = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') target = 0;
            if (event.key === 'End') target = tabs.length - 1;
            if (target !== undefined) {
                event.preventDefault();
                showTab(target, true);
            }
        });
    });

    previous.addEventListener('click', () => showTab(active - 1, true));

    next.addEventListener('click', () => {
        if (!dialog.hidden) { const invalid=validateEntry(); (invalid || document.getElementById('er-save-entry')).focus(); return; }
        syncReporterHiddenFields();
        const currentPanel = panels[active];
        const firstInvalid = validatePanel(currentPanel);

        if (firstInvalid) {
            firstInvalid.focus();
            return;
        }

        showTab(active + 1, true);
    });

    // Form submission validation
    form.noValidate = true;
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (!dialog.hidden) {
            showTab(panels.indexOf(dialog.closest('[role="tabpanel"]')), true);
            document.getElementById('er-save-entry').focus();
            window.uiNotify('Please save or cancel the open entry before saving the emergency record.');
            return;
        }

        syncReporterHiddenFields();
        let firstInvalid = null;
        let firstInvalidPanel = null;

        panels.forEach(panel => {
            const invalid = validatePanel(panel);
            if (invalid && !firstInvalid) {
                firstInvalid = invalid;
                firstInvalidPanel = panel;
            }
        });

        if (firstInvalid) {
            const tabIdx = panels.indexOf(firstInvalidPanel);
            if (tabIdx >= 0) showTab(tabIdx);
            firstInvalid.focus();
            return;
        }

        if (active !== tabs.length - 1) {
            showTab(active + 1, true);
            return;
        }

        sync();
        saving = true;
        document.getElementById('save-emergency').disabled = true;
        HTMLFormElement.prototype.submit.call(form);
    });
    document.querySelectorAll('a[href]').forEach(link=>link.addEventListener('click',async event=>{
        if (!dirty) return;
        event.preventDefault();
        if (await window.uiConfirm('Discard unsaved emergency record information?')) { dirty=false; window.location.assign(link.href); }
    }));
    window.addEventListener('beforeunload',event=>{ if (!saving && (dirty || entryDirty())) { event.preventDefault(); event.returnValue=''; } });
    updateMain(); render(); sync();
    for (const [index,panel] of panels.entries()) {
        const hasEntryErrors=[...panel.querySelectorAll('[data-entry-table]')].some(el=>Object.keys(errors).some(key=>key===`entries.${el.dataset.entryTable}` || key.startsWith(`entries.${el.dataset.entryTable}.`)));
        const hasErrors=!!panel.querySelector('[aria-invalid="true"]') || hasEntryErrors;
        if(hasErrors) tabs[index].classList.add('er-tab-has-errors');
    }
    const firstError=tabs.findIndex(tab=>tab.classList.contains('er-tab-has-errors'));
    showTab(firstError<0 ? 0 : firstError);
    document.querySelector('.er-error-summary')?.focus();
});

