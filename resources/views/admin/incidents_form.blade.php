<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($editingIncident) ? 'Edit Disaster Record' : 'Add Disaster Record' }} – Barangay Doyong EDRMS</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>

    {{-- System and Emergency Record Design System Stylesheets --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/disaster-records.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/emergency-records.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/emergency-record-form.css') }}"/>

    @include('components.ui-assets')
</head>
<body class="admin-theme disaster-records emergency-records {{ request()->routeIs('staff.*') ? 'staff-disaster-records' : '' }}">
<div class="app-shell">
    @include('admin.partials.sidebar', ['sidebarSubtitle' => request()->routeIs('staff.*') ? 'RECORDS STAFF' : 'RECORDS ADMIN'])
    <main class="main-area">
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <a class="topbar-back" href="{{ route(\App\Support\RecordPortal::routeName('incidents.index')) }}" aria-label="Back to Disaster Records" title="Back to Disaster Records">
                    <span class="material-icons-round">arrow_back</span>
                </a>
                <div>
                    <h2>{{ isset($editingIncident) ? 'Edit Disaster Record' : 'Add Disaster Record' }}</h2>
                    <p>Digital version of the existing barangay logbook</p>
                </div>
            </div>
        </header>

        <div class="page-content er-page">
            <nav class="er-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route(\App\Support\RecordPortal::routeName('incidents.index')) }}">Disaster Records</a>
                <span aria-hidden="true">›</span>
                <span>{{ isset($editingIncident) ? 'Edit Disaster Record' : 'Add Disaster Record' }}</span>
            </nav>

            @if(session('success'))
                <div class="flash-msg success" role="status">{{ session('success') }}</div>
            @endif

            <form id="disaster-record-form" method="POST" action="{{ isset($editingIncident) ? route(\App\Support\RecordPortal::routeName('incidents.update'), $editingIncident) : route(\App\Support\RecordPortal::routeName('incidents.store')) }}">
                @csrf
                @isset($editingIncident)
                    @method('PUT')
                @endisset

                <div class="er-card er-tabbed-card">
                    <div class="er-tabs er-tabs-5" role="tablist" aria-label="Disaster record sections">
                        <button type="button" class="er-tab" role="tab" id="er-tab-0" aria-controls="er-panel-0" aria-selected="true" tabindex="0">
                            <span class="er-tab-number" aria-hidden="true">1</span>Record Information
                        </button>
                        <button type="button" class="er-tab" role="tab" id="er-tab-1" aria-controls="er-panel-1" aria-selected="false" tabindex="-1">
                            <span class="er-tab-number" aria-hidden="true">2</span>Complainant / Reporting Person
                        </button>
                        <button type="button" class="er-tab" role="tab" id="er-tab-2" aria-controls="er-panel-2" aria-selected="false" tabindex="-1">
                            <span class="er-tab-number" aria-hidden="true">3</span>Respondent / Person Involved
                        </button>
                        <button type="button" class="er-tab" role="tab" id="er-tab-3" aria-controls="er-panel-3" aria-selected="false" tabindex="-1">
                            <span class="er-tab-number" aria-hidden="true">4</span>Disaster Information
                        </button>
                        <button type="button" class="er-tab" role="tab" id="er-tab-4" aria-controls="er-panel-4" aria-selected="false" tabindex="-1">
                            <span class="er-tab-number" aria-hidden="true">5</span>Additional Details
                        </button>
                    </div>

                    <p class="er-hint">Fields marked <span class="er-required">*</span> are required. Complete all relevant sections before saving.</p>

                    @if($errors->any())
                        <div class="er-error-summary" role="alert" tabindex="-1">
                            <strong>Please correct the following:</strong>
                            <ul style="margin: 6px 0 0 18px; padding: 0;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ── TAB 1: Record Information ── --}}
                    <section class="er-tab-panel" id="er-panel-0" role="tabpanel" aria-labelledby="er-tab-0" tabindex="0">
                        <h3>Record Information</h3>
                        <div class="er-grid-3">
                            <div class="er-field">
                                <label for="record_number">Record Number</label>
                                <input type="text" id="record_number" value="{{ isset($editingIncident) ? $editingIncident->record_number : ($recordPreview ?? 'DR-'.now()->format('Y').'-0001') }}" readonly />
                                <p class="er-hint" style="margin: 3px 0 0; font-size: 11px;">{{ isset($editingIncident) ? 'Retained when editing.' : 'Auto-generated on save. Format: DR-YYYY-XXXX.' }}</p>
                            </div>
                            <div class="er-field">
                                <label for="record_date">Date <span class="er-required">*</span></label>
                                <input type="date" id="record_date" name="record_date" value="{{ old('record_date', $editingIncident->logbook['record_date'] ?? now()->toDateString()) }}" required @error('record_date') aria-invalid="true" aria-describedby="error-record_date" @enderror />
                                @error('record_date')<small class="er-error" id="error-record_date">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field">
                                <label for="record_time">Time <span class="er-required">*</span></label>
                                <input type="time" id="record_time" name="record_time" value="{{ old('record_time', $editingIncident->logbook['record_time'] ?? now()->format('H:i')) }}" required @error('record_time') aria-invalid="true" aria-describedby="error-record_time" @enderror />
                                @error('record_time')<small class="er-error" id="error-record_time">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </section>

                    {{-- ── TAB 2: Complainant / Reporting Person ── --}}
                    <section class="er-tab-panel" id="er-panel-1" role="tabpanel" aria-labelledby="er-tab-1" tabindex="0" hidden>
                        <h3>Complainant / Reporting Person</h3>
                        {{-- Row 1: First Name, Middle Name, Last Name --}}
                        <div class="er-grid-3">
                            <div class="er-field">
                                <label for="complainant_first_name">First Name <span class="er-required">*</span></label>
                                <input type="text" id="complainant_first_name" name="complainant_first_name" maxlength="50" value="{{ old('complainant_first_name', $editingIncident->complainant_first_name ?? '') }}" required @error('complainant_first_name') aria-invalid="true" aria-describedby="error-complainant_first_name" @enderror />
                                @error('complainant_first_name')<small class="er-error" id="error-complainant_first_name">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field">
                                <label for="complainant_middle_name">Middle Name</label>
                                <input type="text" id="complainant_middle_name" name="complainant_middle_name" maxlength="50" value="{{ old('complainant_middle_name', $editingIncident->complainant_middle_name ?? '') }}" @error('complainant_middle_name') aria-invalid="true" aria-describedby="error-complainant_middle_name" @enderror />
                                @error('complainant_middle_name')<small class="er-error" id="error-complainant_middle_name">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field">
                                <label for="complainant_last_name">Last Name <span class="er-required">*</span></label>
                                <input type="text" id="complainant_last_name" name="complainant_last_name" maxlength="50" value="{{ old('complainant_last_name', $editingIncident->complainant_last_name ?? '') }}" required @error('complainant_last_name') aria-invalid="true" aria-describedby="error-complainant_last_name" @enderror />
                                @error('complainant_last_name')<small class="er-error" id="error-complainant_last_name">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        {{-- Row 2: Age, Sitio, Other Address / Landmark --}}
                        <div class="er-grid-3" style="margin-top: 12px;">
                            <div class="er-field">
                                <label for="complainant_age">Age</label>
                                <input type="number" id="complainant_age" name="complainant_age" min="0" max="120" step="1" value="{{ old('complainant_age', $editingIncident->logbook['complainant_age'] ?? '') }}" @error('complainant_age') aria-invalid="true" aria-describedby="error-complainant_age" @enderror />
                                @error('complainant_age')<small class="er-error" id="error-complainant_age">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field">
                                <label for="sitio">Sitio</label>
                                @php
                                    $selectedSitio = old('sitio', $editingIncident->sitio ?? $editingIncident->logbook['purok'] ?? '');
                                @endphp
                                <select id="sitio" name="sitio" @error('sitio') aria-invalid="true" aria-describedby="error-sitio" @enderror>
                                    <option value="">Select Sitio</option>
                                    @foreach(['Sitio Cabuaan', 'Sitio Camanggaan', 'Sitio Gonzales', 'Sitio Basasong', 'Sitio Doyong Centro', 'Sitio Baba', 'Sitio Central'] as $sitioOption)
                                        <option value="{{ $sitioOption }}" @selected($selectedSitio === $sitioOption)>{{ $sitioOption }}</option>
                                    @endforeach
                                </select>
                                @error('sitio')<small class="er-error" id="error-sitio">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field">
                                <label for="other_address">Other Address / Landmark</label>
                                <input type="text" id="other_address" name="other_address" maxlength="255" value="{{ old('other_address', $editingIncident->logbook['other_address'] ?? '') }}" @error('other_address') aria-invalid="true" aria-describedby="error-other_address" @enderror />
                                @error('other_address')<small class="er-error" id="error-other_address">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        {{-- Hidden inputs to maintain 100% database compatibility and legacy test assertions --}}
                        <input type="hidden" id="barangay" name="barangay" value="Doyong" />
                        <input type="hidden" id="purok" name="purok" value="{{ old('purok', $selectedSitio) }}" />
                        <input type="hidden" id="complainant_name" name="complainant_name" value="{{ old('complainant_name', $editingIncident->logbook['complainant_name'] ?? '') }}" />
                    </section>

                    {{-- ── TAB 3: Respondent / Person Involved ── --}}
                    <section class="er-tab-panel" id="er-panel-2" role="tabpanel" aria-labelledby="er-tab-2" tabindex="0" hidden>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <h3 style="margin: 0;">Respondent / Person Involved</h3>
                            <button type="button" class="btn btn-ghost" id="add-logbook-person" style="display: inline-flex; align-items: center; gap: 6px;">
                                <span class="material-icons-round" style="font-size: 16px;">add</span> Add Another Person
                            </button>
                        </div>
                        <p class="er-hint">Leave this section empty if no person is identified. For each person entered, First Name and Last Name are required; Middle Name, Age, and Address are optional.</p>

                        <div id="logbook-persons" style="display: flex; flex-direction: column; gap: 12px;">
                            @php
                                $existingPersons = old('persons', $editingIncident->logbook['persons'] ?? []) ?: [['first_name'=>'', 'middle_name'=>'', 'last_name'=>'', 'age'=>'', 'address'=>'']];
                            @endphp
                            @foreach($existingPersons as $person)
                            @php $person = \App\Support\PersonDetails::names($person); @endphp
                            <div class="logbook-person er-person-card">
                                <div class="er-person-header">
                                    <span class="er-person-title">
                                        <span class="material-icons-round" style="font-size: 16px; color: var(--gray-400);">person_outline</span>
                                        <span>Person Details #<span class="person-num">{{ $loop->iteration }}</span></span>
                                    </span>
                                    <button type="button" class="er-remove-btn" data-remove-person title="Remove Person" aria-label="Remove person">
                                        <span class="material-icons-round" style="font-size: 16px;">delete_outline</span> Remove
                                    </button>
                                </div>
                                <div class="er-person-grid">
                                    @foreach(['first_name'=>'First Name', 'middle_name'=>'Middle Name', 'last_name'=>'Last Name', 'age'=>'Age', 'address'=>'Address'] as $key=>$label)
                                    @php $errorKey = 'persons.'.$loop->parent->index.'.'.$key; $fieldId = 'person-'.$loop->parent->index.'-'.$key; @endphp
                                    <div class="er-field er-person-{{ $key }}">
                                        <label for="{{ $fieldId }}">{{ $label }}</label>
                                        <input id="{{ $fieldId }}" data-person-field="{{ $key }}" type="{{ $key === 'age' ? 'number' : 'text' }}" name="persons[{{ $loop->parent->index }}][{{ $key }}]" value="{{ $person[$key] ?? '' }}" @if($key==='age') min="0" max="120" step="1" @else maxlength="{{ $key==='address' ? 255 : 50 }}" @endif @error($errorKey) aria-invalid="true" aria-describedby="error-{{ $fieldId }}" @enderror />
                                        @error($errorKey)<small class="er-error" id="error-{{ $fieldId }}">{{ $message }}</small>@enderror
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- ── TAB 4: Disaster Information ── --}}
                    <section class="er-tab-panel" id="er-panel-3" role="tabpanel" aria-labelledby="er-tab-3" tabindex="0" hidden>
                        <h3>Disaster Information</h3>
                        <div class="er-grid-3">
                            <div class="er-field">
                                <label for="type">Type of Record <span class="er-required">*</span></label>
                                @php $currentType = old('type', $editingIncident->type ?? ''); @endphp
                                <select id="type" name="type" required @error('type') aria-invalid="true" aria-describedby="error-type" @enderror>
                                    <option value="">Select Type</option>
                                    @foreach(['fire'=>'Fire', 'accident'=>'Accident', 'theft'=>'Theft / Crime', 'flood'=>'Flood', 'others'=>'Others'] as $val => $lbl)
                                        <option value="{{ $val }}" @selected($currentType === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                @error('type')<small class="er-error" id="error-type">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field">
                                <label for="incident_date">Date of Incident <span class="er-required">*</span></label>
                                <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date', $editingIncident->logbook['incident_date'] ?? now()->toDateString()) }}" required @error('incident_date') aria-invalid="true" aria-describedby="error-incident_date" @enderror />
                                @error('incident_date')<small class="er-error" id="error-incident_date">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field">
                                <label for="incident_time">Time of Incident <span class="er-required">*</span></label>
                                <input type="time" id="incident_time" name="incident_time" value="{{ old('incident_time', $editingIncident->logbook['incident_time'] ?? now()->format('H:i')) }}" required @error('incident_time') aria-invalid="true" aria-describedby="error-incident_time" @enderror />
                                @error('incident_time')<small class="er-error" id="error-incident_time">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field er-wide">
                                <label for="location">Place of Incident / Location <span class="er-required">*</span></label>
                                <input type="text" id="location" name="location" maxlength="255" value="{{ old('location', $editingIncident->location ?? '') }}" required @error('location') aria-invalid="true" aria-describedby="error-location" @enderror />
                                @error('location')<small class="er-error" id="error-location">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field er-wide">
                                <label for="description">Narrative / Details <span class="er-required">*</span></label>
                                <textarea id="description" name="description" rows="3" maxlength="20000" required @error('description') aria-invalid="true" aria-describedby="error-description" @enderror>{{ old('description', $editingIncident->description ?? '') }}</textarea>
                                @error('description')<small class="er-error" id="error-description">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </section>

                    {{-- ── TAB 5: Additional Details ── --}}
                    <section class="er-tab-panel" id="er-panel-4" role="tabpanel" aria-labelledby="er-tab-4" tabindex="0" hidden>
                        <h3>Additional Details</h3>
                        <div class="er-grid-3">
                            <div class="er-field er-wide">
                                <label for="action_taken">Action Taken</label>
                                <textarea id="action_taken" name="action_taken" rows="2" maxlength="20000">{{ old('action_taken', $editingIncident->logbook['action_taken'] ?? '') }}</textarea>
                            </div>
                        </div>

                        <h4 class="er-subheading">Scheduled Confrontation / Meeting</h4>
                        <div class="er-grid-3">
                            <div class="er-field">
                                <label for="meeting_date">Meeting Date</label>
                                <input type="date" id="meeting_date" name="meeting_date" value="{{ old('meeting_date', $editingIncident->logbook['meeting_date'] ?? '') }}" @error('meeting_date') aria-invalid="true" aria-describedby="error-meeting_date" @enderror />
                                @error('meeting_date')<small class="er-error" id="error-meeting_date">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field">
                                <label for="meeting_time">Meeting Time</label>
                                <input type="time" id="meeting_time" name="meeting_time" value="{{ old('meeting_time', $editingIncident->logbook['meeting_time'] ?? '') }}" @error('meeting_time') aria-invalid="true" aria-describedby="error-meeting_time" @enderror />
                                @error('meeting_time')<small class="er-error" id="error-meeting_time">{{ $message }}</small>@enderror
                            </div>
                            <div class="er-field er-wide">
                                <label for="remarks">Remarks / Result</label>
                                <textarea id="remarks" name="remarks" rows="2" maxlength="20000">{{ old('remarks', $editingIncident->logbook['remarks'] ?? '') }}</textarea>
                            </div>
                        </div>

                        <h4 class="er-subheading">Official Recording Information</h4>
                        <div class="er-grid-3">
                            <div class="er-field">
                                <label for="recorded_by">Recorded By</label>
                                <input type="text" id="recorded_by" value="{{ $editingIncident->logbook['recorded_by'] ?? auth()->user()->name }}" readonly />
                                <p class="er-hint" style="margin: 3px 0 0; font-size: 11px;">Automatically recorded using your signed-in account.</p>
                            </div>
                            <div class="er-field">
                                <label for="officer">Official / Officer</label>
                                <input type="text" id="officer" name="officer" maxlength="255" value="{{ old('officer', $editingIncident->logbook['officer'] ?? '') }}" />
                            </div>
                            <div class="er-field">
                                <label for="position">Position</label>
                                <input type="text" id="position" name="position" maxlength="255" value="{{ old('position', $editingIncident->logbook['position'] ?? '') }}" />
                            </div>
                        </div>
                    </section>

                    {{-- ── FOOTER STEP ACTIONS ── --}}
                    <div class="er-actions er-step-actions">
                        <button type="button" class="btn btn-ghost" id="er-previous" hidden>Previous</button>
                        <span id="er-step-label" class="er-hint" aria-live="polite">Section 1 of 5</span>
                        <button type="button" class="btn btn-primary" id="er-next">Next <span class="material-icons-round" aria-hidden="true">arrow_forward</span></button>
                        <a class="btn btn-ghost" href="{{ route(\App\Support\RecordPortal::routeName('incidents.index')) }}" data-discard hidden>Cancel</a>
                        <button class="btn btn-primary" id="save-disaster" type="submit" hidden><span class="material-icons-round" aria-hidden="true">save</span> {{ isset($editingIncident) ? 'Save Disaster Record' : 'Save Disaster Record' }}</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

{{-- ── TAB NAVIGATION, REPEATER & REAL-TIME VALIDATION LOGIC ── --}}
<script src="{{ asset('js/person-validation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('disaster-record-form');
    if (!form) return;

    const tabs = [...form.querySelectorAll('[role="tab"]')];
    const panels = [...form.querySelectorAll('[role="tabpanel"]')];
    const previous = document.getElementById('er-previous');
    const next = document.getElementById('er-next');
    const save = document.getElementById('save-disaster');
    const cancel = form.querySelector('[data-discard]');
    const stepLabel = document.getElementById('er-step-label');
    let active = 0;

    const SITIOS = [
        'Sitio Cabuaan',
        'Sitio Camanggaan',
        'Sitio Gonzales',
        'Sitio Basasong',
        'Sitio Doyong Centro',
        'Sitio Baba',
        'Sitio Central'
    ];

    function showTab(index, focus = false) {
        if (index < 0 || index >= tabs.length) return;
        active = index;

        tabs.forEach((tab, i) => {
            const isCurrent = (i === index);
            tab.setAttribute('aria-selected', String(isCurrent));
            tab.tabIndex = isCurrent ? 0 : -1;
            panels[i].hidden = !isCurrent;
        });

        previous.hidden = (index === 0);
        const isLast = (index === tabs.length - 1);
        next.hidden = isLast;
        if (cancel) cancel.hidden = !isLast;
        if (save) save.hidden = !isLast;

        if (stepLabel) {
            stepLabel.textContent = `Section ${index + 1} of ${tabs.length}`;
        }

        if (focus) {
            tabs[index].focus();
        }
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

    if (previous) {
        previous.addEventListener('click', () => showTab(active - 1, true));
    }

    function syncHiddenFields() {
        const firstName = form.querySelector('[name="complainant_first_name"]')?.value.trim() || '';
        const middleName = form.querySelector('[name="complainant_middle_name"]')?.value.trim() || '';
        const lastName = form.querySelector('[name="complainant_last_name"]')?.value.trim() || '';
        const fullName = [firstName, middleName, lastName].filter(Boolean).join(' ');

        const hiddenName = form.querySelector('#complainant_name');
        if (hiddenName) hiddenName.value = fullName;

        const sitioVal = form.querySelector('#sitio')?.value || '';
        const hiddenPurok = form.querySelector('#purok');
        if (hiddenPurok) hiddenPurok.value = sitioVal;
    }

    function validateField(input) {
        if (!input || !input.name || input.type === 'hidden' || input.disabled || input.readOnly) return null;

        const val = input.value.trim();
        const name = input.name;
        const isRequired = input.required || input.hasAttribute('required');

        if (input.dataset.personField) return window.PersonValidation.validate(input, input.closest('.logbook-person'));

        // Required check
        if (isRequired && val === '') {
            if (name === 'complainant_first_name') return 'First name is required.';
            if (name === 'complainant_last_name') return 'Last name is required.';
            if (name === 'record_date') return 'Please select a valid date.';
            if (name === 'record_time') return 'Please select a valid time.';
            if (name === 'incident_date') return 'Please select a valid date.';
            if (name === 'incident_time') return 'Please select a valid time.';
            if (name === 'type') return 'Please select a record type.';
            if (name === 'location') return 'Place of incident is required.';
            if (name === 'description') return 'Narrative details are required.';
            return 'This field is required.';
        }

        // Complainant Name validation
        if (name === 'complainant_first_name' || name === 'complainant_last_name') {
            if (val !== '') {
                if (/\d/.test(val)) return 'Numbers are not allowed in the name.';
                if (/[^a-zA-Z\u00C0-\u024F\u1E00-\u1EFF\s\-\']/.test(val)) return 'Special characters are not allowed in the name.';
                if (val.length < 2) return (name.includes('first') ? 'First name' : 'Last name') + ' must contain at least 2 characters.';
                if (val.length > 50) return (name.includes('first') ? 'First name' : 'Last name') + ' cannot exceed 50 characters.';
            }
        }

        if (name === 'complainant_middle_name' && val !== '') {
            if (/\d/.test(val)) return 'Numbers are not allowed in the name.';
            if (/[^a-zA-Z\u00C0-\u024F\u1E00-\u1EFF\s\-\']/.test(val)) return 'Special characters are not allowed in the name.';
            if (val.length > 50) return 'Middle name cannot exceed 50 characters.';
        }

        // Sitio validation
        if (name === 'sitio') {
            if (val !== '' && !SITIOS.includes(val)) {
                return 'Please select a valid sitio.';
            }
        }

        // Age validation
        if (name === 'complainant_age' || name.endsWith('[age]')) {
            if (val !== '') {
                const num = Number(val);
                if (!/^\d+$/.test(val) || isNaN(num) || num < 0 || num > 120) {
                    return 'Please enter a valid age (0–120).';
                }
            }
        }

        // Incident Date & Time future check
        if (name === 'incident_date' || name === 'incident_time') {
            const dateInput = form.querySelector('[name="incident_date"]');
            const timeInput = form.querySelector('[name="incident_time"]');
            if (dateInput && timeInput && dateInput.value && timeInput.value) {
                const incidentDt = new Date(`${dateInput.value}T${timeInput.value}`);
                if (!isNaN(incidentDt.getTime()) && incidentDt > new Date()) {
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
            const hasErr = !!panel.querySelector('[aria-invalid="true"]');
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

    // Attach real-time validation
    form.addEventListener('blur', event => {
        const target = event.target;
        if (target && target.form === form && target.name && target.type !== 'hidden') {
            syncHiddenFields();
            const error = validateField(target);
            setFieldError(target, error);
        }
    }, true);

    form.addEventListener('input', event => {
        const target = event.target;
        if (target && target.form === form && target.name) {
            syncHiddenFields();
            if (target.getAttribute('aria-invalid') === 'true') {
                const error = validateField(target);
                setFieldError(target, error);
            }
        }
    });

    form.addEventListener('change', event => {
        const target = event.target;
        if (target && target.form === form && target.name) {
            syncHiddenFields();
            if (target.getAttribute('aria-invalid') === 'true' || target.tagName === 'SELECT') {
                const error = validateField(target);
                setFieldError(target, error);
            }
        }
    });

    // Next step button validation
    if (next) {
        next.addEventListener('click', () => {
            syncHiddenFields();
            const currentPanel = panels[active];
            const firstInvalid = validatePanel(currentPanel);

            if (firstInvalid) {
                firstInvalid.focus();
                return;
            }

            showTab(active + 1, true);
        });
    }

    // Full form submit validation
    form.noValidate = true;
    form.addEventListener('submit', event => {
        syncHiddenFields();
        let firstInvalid = null;
        let firstInvalidPanel = null;

        panels.forEach((panel, idx) => {
            const invalid = validatePanel(panel);
            if (invalid && !firstInvalid) {
                firstInvalid = invalid;
                firstInvalidPanel = panel;
            }
        });

        if (firstInvalid) {
            event.preventDefault();
            const tabIdx = panels.indexOf(firstInvalidPanel);
            if (tabIdx >= 0) showTab(tabIdx);
            firstInvalid.focus();
            return;
        }

        if (active !== tabs.length - 1) {
            event.preventDefault();
            showTab(active + 1, true);
            return;
        }

        if (save) save.disabled = true;
        HTMLFormElement.prototype.submit.call(form);
    });

    // Repeatable Persons Logic
    const personContainer = document.getElementById('logbook-persons');
    const addPersonBtn = document.getElementById('add-logbook-person');

    function updatePersonNumbers() {
        if (!personContainer) return;
        const personCards = personContainer.querySelectorAll('.logbook-person');
        personCards.forEach((card, idx) => {
            const numSpan = card.querySelector('.person-num');
            if (numSpan) numSpan.textContent = idx + 1;
            card.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace(/persons\[\d+\]/, `persons[${idx}]`);
                input.id = `person-${idx}-${input.dataset.personField}`;
                input.closest('.er-field').querySelector('label').htmlFor = input.id;
                const error = input.closest('.er-field').querySelector('.er-error');
                if (error) { error.id = `error-${input.id}`; input.setAttribute('aria-describedby', error.id); }
                else input.removeAttribute('aria-describedby');
            });
        });
    }

    if (personContainer && addPersonBtn) {
        addPersonBtn.addEventListener('click', function() {
            if (personContainer.children.length >= 50) return;
            const currentCount = personContainer.children.length;
            const template = personContainer.firstElementChild ? personContainer.firstElementChild.cloneNode(true) : null;
            if (!template) return;

            template.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace(/persons\[\d+\]/, `persons[${currentCount}]`);
                input.value = '';
                input.removeAttribute('aria-invalid');
                input.removeAttribute('aria-describedby');
            });
            template.querySelectorAll('.er-error').forEach(el => el.remove());

            personContainer.appendChild(template);
            updatePersonNumbers();

            const firstInput = template.querySelector('input');
            if (firstInput) firstInput.focus();
        });

        personContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('[data-remove-person]');
            if (!removeBtn) return;
            const card = removeBtn.closest('.logbook-person');
            if (personContainer.children.length === 1) {
                card.querySelectorAll('input').forEach(input => {
                    input.value = '';
                    input.removeAttribute('aria-invalid');
                    input.removeAttribute('aria-describedby');
                });
                card.querySelectorAll('.er-error').forEach(el => el.remove());
            } else {
                card.remove();
                updatePersonNumbers();
            }
            updateTabErrors();
        });
    }

    // Highlight tabs with validation errors on load
    updateTabErrors();
    const firstError = tabs.findIndex(tab => tab.classList.contains('er-tab-has-errors'));
    showTab(firstError < 0 ? 0 : firstError);
    document.querySelector('.er-error-summary')?.focus();
});
</script>
</body>
</html>
