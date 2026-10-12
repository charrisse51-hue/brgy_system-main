<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Resident Records – Barangay Doyong EDRMS</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/resident_record.css') }}"/>
    
    @vite(['resources/css/resident_record.css'])
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-records-theme.css') }}"/>
<link rel="stylesheet" href="{{ asset('css/disaster-records.css') }}"/>
<link rel="stylesheet" href="{{ asset('css/resident-records-reference.css') }}"/>
<link rel="stylesheet" href="{{ asset('css/resident-individual-form.css') }}"/>
    @include('components.ui-assets')
</head>
<body class="admin-theme disaster-records resident-records">
<div class="app-shell">

    {{-- ═══ SIDEBAR ═══ --}}
    @include('admin.partials.sidebar', ['sidebarSubtitle' => 'RECORDS ADMIN'])

    {{-- ═══ MAIN AREA ═══ --}}
    <div class="main-area">

        {{-- TOPBAR --}}
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <h2>Resident Records Management</h2>
                <p>Manage and track barangay resident information</p>
            </div>
            </header>

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="flash-msg success">
            <span class="material-icons-round">check_circle</span>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="flash-msg error">
            <span class="material-icons-round">error</span>
            {{ session('error') }}
        </div>
        @endif

        {{-- PAGE CONTENT --}}
        <div class="page-content">

            @php
                $selectedResident = $viewResident ?? $editResident ?? ($residents->first() ?? null);
                $parts = $selectedResident ? preg_split('/\s+/', $selectedResident->full_name) : [];
                $selectedInitials = $selectedResident ? collect($parts)->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->implode('') : 'R';
                $rawPhilsys = $selectedResident?->philsys_card_number;
                $maskedPhilsys = $rawPhilsys ? (strlen($rawPhilsys) > 4 ? '••••-••••-' . substr($rawPhilsys, -4) : $rawPhilsys) : '—';
                $incomeSources = !empty($selectedResident?->income_sources) ? (is_array($selectedResident->income_sources) ? implode(', ', $selectedResident->income_sources) : $selectedResident->income_sources) : '—';
                $targetGroups = !empty($selectedResident?->target_groups) ? (is_array($selectedResident->target_groups) ? implode(', ', (array)$selectedResident->target_groups) : $selectedResident->target_groups) : 'None / Not Applicable';
                $motherMaiden = trim(($selectedResident?->mother_first_name ?? '') . ' ' . ($selectedResident?->mother_middle_name ?? '') . ' ' . ($selectedResident?->mother_last_name ?? '')) ?: '—';
            @endphp

            {{-- ════ SPLIT-PANEL CONTAINER ════ --}}
            <div class="res-split-container" id="resSplitContainer">

                {{-- ════ LEFT PANEL: LIST OF REGISTERED RESIDENTS ════ --}}
                <div class="res-split-left res-list-panel" id="resListPanel">
                    <div class="res-panel-header">
                        <div class="res-panel-title">
                            <span class="material-icons-round">badge</span>
                            <span>List of Registered Residents</span>
                        </div>
                        <span class="res-count-badge">{{ number_format($residents->total() ?? 0) }} Total</span>
                    </div>

                    {{-- Search & Filters in Left Panel --}}
                    <div class="res-list-scroll">
                    <div class="res-list-filters-wrap">
                        @include('admin.partials.resident-filters')
                    </div>

                    {{-- Residents Table --}}
                    <div class="res-list-table-wrap">
                        <table class="res-list-table" id="residentsTable">
                            <colgroup>
                                <col class="res-col-id"><col class="res-col-first"><col class="res-col-middle"><col class="res-col-last"><col class="res-col-sitio">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th scope="col">Resident ID</th>
                                    <th scope="col">First Name</th>
                                    <th scope="col">Middle Name</th>
                                    <th scope="col">Last Name</th>
                                    <th scope="col">Sitio</th>
                                </tr>
                            </thead>
                            <tbody id="residentsTableBody">
                                @forelse($residents ?? [] as $resident)
                                @php
                                    $isSelected = $selectedResident && $resident->id === $selectedResident->id;
                                    $hasNameParts = filled($resident->first_name) || filled($resident->middle_name) || filled($resident->last_name);
                                    $sitio = $resident->sitio_purok ?? $resident->purok ?? '—';
                                    $residentPayload = array_merge($resident->only(array_keys(\App\Support\ResidentForm::fields())), [
                                        'id' => $resident->id,
                                        'full_name' => $resident->full_name,
                                        'birthdate' => $resident->birthdate?->format('Y-m-d'),
                                        'age' => $resident->age,
                                        'created_at_formatted' => $resident->created_at?->format('M d, Y h:i A'),
                                        'updated_at_formatted' => $resident->updated_at?->format('M d, Y h:i A'),
                                        'address' => $resident->address,
                                    ]);
                                @endphp
                                <tr class="res-row {{ $isSelected ? 'is-selected' : '' }}"
                                    tabindex="0" aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                                    data-id="{{ $resident->id }}"
                                    data-resident="{{ json_encode($residentPayload) }}"
                                    data-update-url="{{ route(\App\Support\RecordPortal::routeName('residents.update'), $resident) }}"
                                    data-edit-url="{{ route(\App\Support\RecordPortal::routeName('residents.edit'), $resident) }}"
                                >
                                    <td class="res-td-id">
                                        #RES-{{ str_pad($resident->id, 4, '0', STR_PAD_LEFT) }}
                                    </td>
                                    @if($hasNameParts)
                                    <td>{{ filled($resident->first_name) ? $resident->first_name : '—' }}</td>
                                    <td>{{ filled($resident->middle_name) ? $resident->middle_name : '—' }}</td>
                                    <td>{{ filled($resident->last_name) ? $resident->last_name : '—' }}</td>
                                    @else
                                    <td colspan="3" data-label="Original full name"><span class="res-legacy-name">{{ $resident->name ?: '—' }}</span><small class="res-legacy-note">Original full name</small></td>
                                    @endif
                                    <td class="res-sitio">{{ $sitio }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="res-table-empty" style="text-align: center; padding: 36px 16px; color: #64748b;">
                                        <span class="material-icons-round" style="font-size: 36px; color: #94a3b8; display: block; margin-bottom: 8px;">search_off</span>
                                        <p style="margin: 0; font-size: 13px;">No residents found matching criteria.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    </div>{{-- /res-list-scroll --}}

                    {{-- Left List Pagination --}}
                    <div class="res-list-footer">
                        <p class="res-count-text">
                            Showing <strong>{{ $residents->firstItem() ?? 0 }}</strong> to <strong>{{ $residents->lastItem() ?? 0 }}</strong> of <strong>{{ $residents->total() ?? 0 }}</strong>
                        </p>
                        <div class="inc-pagination">
                            @if(isset($residents) && $residents->onFirstPage())
                                <button class="pg-btn" disabled><span class="material-icons-round">chevron_left</span></button>
                            @elseif(isset($residents))
                                <a href="{{ $residents->previousPageUrl() }}" class="pg-btn"><span class="material-icons-round">chevron_left</span></a>
                            @else
                                <button class="pg-btn" disabled><span class="material-icons-round">chevron_left</span></button>
                            @endif

                            @if(isset($residents))
                                @for($p = 1; $p <= min($residents->lastPage(), 5); $p++)
                                    <a href="{{ $residents->url($p) }}"
                                       class="pg-btn {{ $residents->currentPage() === $p ? 'pg-btn--active' : '' }}">
                                        {{ $p }}
                                    </a>
                                @endfor
                                @if($residents->lastPage() > 5)
                                    <span class="pg-btn" style="border:none;cursor:default">…</span>
                                @endif
                            @endif

                            @if(isset($residents) && $residents->hasMorePages())
                                <a href="{{ $residents->nextPageUrl() }}" class="pg-btn"><span class="material-icons-round">chevron_right</span></a>
                            @else
                                <button class="pg-btn" {{ isset($residents) ? 'disabled' : '' }}><span class="material-icons-round">chevron_right</span></button>
                            @endif
                        </div>
                    </div>
                </div>{{-- /res-split-left --}}


                {{-- ════ RIGHT PANEL: RESIDENT PROFILE DETAILS ════ --}}
                <div class="res-split-right res-profile-panel" id="resProfilePanel">
                    {{-- Toolbar with Action Buttons --}}
                    <div class="res-profile-toolbar">
                        <div class="res-profile-toolbar-title">
                            <button type="button" class="res-back-to-list-btn" id="resBackToListBtn" title="Back to Resident List">
                                <span class="material-icons-round">arrow_back</span>
                                <span>Back to List</span>
                            </button>
                            <span class="material-icons-round res-tb-icon">contact_page</span>
                            <h3>Resident Profile Details</h3>
                        </div>
                        <div class="res-profile-actions">
                            <button type="button" class="btn btn-primary" id="splitAddResidentBtn" data-create-url="{{ route(\App\Support\RecordPortal::routeName('residents.create')) }}">
                                <span class="material-icons-round" aria-hidden="true">add</span>
                                <span>Add New Resident</span>
                            </button>
                            <button type="button" class="btn btn-primary" id="splitEditResidentBtn" {{ !$selectedResident ? 'disabled' : '' }}>
                                <span class="material-icons-round" aria-hidden="true">edit</span>
                                <span>Edit Resident</span>
                            </button>
                        </div>
                    </div>

                    @if($selectedResident)
                    <div class="res-profile-content" id="resProfileContent">
                        {{-- Profile Hero Banner --}}
                        <div class="res-profile-hero">
                            <div class="res-profile-avatar" id="resProfileAvatar">{{ $selectedInitials }}</div>
                            <div class="res-profile-identity">
                                <h3 class="res-profile-name" id="resProfileName">{{ $selectedResident->full_name }}</h3>
                                <div class="res-profile-meta">
                                    <span class="res-badge-id" id="resProfileId">#RES-{{ str_pad($selectedResident->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    <span class="res-location-pill" id="resProfileLocationPill">
                                        <span class="material-icons-round">place</span>
                                        <span id="resProfileLocationText">{{ $selectedResident->sitio_purok ?? $selectedResident->purok ?? 'Sitio Not Specified' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- ════ Section A: Address Information ════ --}}
                        <div class="res-card-section">
                            <div class="res-section-header">
                                <span class="material-icons-round">home</span>
                                <h4>Section A: Address Information</h4>
                            </div>
                            <dl class="res-details-grid">
                                <div>
                                    <dt class="res-dt">Barangay</dt>
                                    <dd class="res-dd" id="resVal_barangay">{{ $selectedResident->barangay ?? 'Doyong' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Sitio / Purok</dt>
                                    <dd class="res-dd" id="resVal_sitio_purok">{{ $selectedResident->sitio_purok ?? $selectedResident->purok ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">House Number</dt>
                                    <dd class="res-dd" id="resVal_house_number">{{ $selectedResident->house_number ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Street</dt>
                                    <dd class="res-dd" id="resVal_street">{{ $selectedResident->street ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Subdivision / Village</dt>
                                    <dd class="res-dd" id="resVal_subdivision">{{ $selectedResident->subdivision ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Zone</dt>
                                    <dd class="res-dd" id="resVal_zone">{{ $selectedResident->zone ? ucfirst($selectedResident->zone) : '—' }}</dd>
                                </div>
                                <div class="res-grid-col-full">
                                    <dt class="res-dt">Existing / Alternative Address</dt>
                                    <dd class="res-dd" id="resVal_address">{{ $selectedResident->address ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- ════ Section B: Personal Information ════ --}}
                        <div class="res-card-section">
                            <div class="res-section-header">
                                <span class="material-icons-round">person</span>
                                <h4>Section B: Personal Information</h4>
                            </div>
                            <dl class="res-details-grid">
                                <div>
                                    <dt class="res-dt">Resident ID</dt>
                                    <dd class="res-dd" id="resVal_id">#RES-{{ str_pad($selectedResident->id, 4, '0', STR_PAD_LEFT) }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">PhilSys Card Number</dt>
                                    <dd class="res-dd" id="resVal_philsys_card_number">{{ $maskedPhilsys }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">First Name</dt>
                                    <dd class="res-dd" id="resVal_first_name">{{ $selectedResident->first_name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Middle Name</dt>
                                    <dd class="res-dd" id="resVal_middle_name">{{ $selectedResident->middle_name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Last Name</dt>
                                    <dd class="res-dd" id="resVal_last_name">{{ $selectedResident->last_name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Suffix</dt>
                                    <dd class="res-dd" id="resVal_suffix">{{ $selectedResident->suffix ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Date of Birth</dt>
                                    <dd class="res-dd" id="resVal_birthdate">{{ $selectedResident->birthdate ? $selectedResident->birthdate->format('F d, Y') : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Age</dt>
                                    <dd class="res-dd" id="resVal_age">{{ $selectedResident->age !== null ? $selectedResident->age . ' yrs old' : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Place of Birth</dt>
                                    <dd class="res-dd" id="resVal_birthplace">{{ $selectedResident->birthplace ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Sex</dt>
                                    <dd class="res-dd" id="resVal_sex">{{ $selectedResident->sex ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Religion</dt>
                                    <dd class="res-dd" id="resVal_religion">{{ $selectedResident->religion ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Citizenship</dt>
                                    <dd class="res-dd" id="resVal_citizenship">{{ $selectedResident->citizenship ?? 'Filipino' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Ethnicity</dt>
                                    <dd class="res-dd" id="resVal_ethnicity">{{ $selectedResident->ethnicity ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Civil Status</dt>
                                    <dd class="res-dd" id="resVal_civil_status">{{ $selectedResident->civil_status ? ucfirst($selectedResident->civil_status) : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Highest Educational Attainment</dt>
                                    <dd class="res-dd" id="resVal_educational_attainment">{{ $selectedResident->educational_attainment ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">School Location</dt>
                                    <dd class="res-dd" id="resVal_school_location">{{ $selectedResident->school_location ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Labor Force Status</dt>
                                    <dd class="res-dd" id="resVal_labor_force_status">{{ $selectedResident->labor_force_status ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Occupation</dt>
                                    <dd class="res-dd" id="resVal_occupation">{{ $selectedResident->occupation ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Monthly Income</dt>
                                    <dd class="res-dd" id="resVal_monthly_income">{{ $selectedResident->monthly_income !== null ? '₱' . number_format($selectedResident->monthly_income, 2) : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Source of Income</dt>
                                    <dd class="res-dd" id="resVal_income_sources">{{ $incomeSources }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- ════ Section C: Vulnerable / Target Groups ════ --}}
                        <div class="res-card-section">
                            <div class="res-section-header">
                                <span class="material-icons-round">accessibility_new</span>
                                <h4>Section C: Vulnerable / Target Groups</h4>
                            </div>
                            <dl class="res-details-grid">
                                <div class="res-grid-col-full">
                                    <dt class="res-dt">Target Groups / Vulnerability</dt>
                                    <dd class="res-dd" id="resVal_target_groups">{{ $targetGroups }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">PWD Classification</dt>
                                    <dd class="res-dd" id="resVal_pwd_classification">{{ $selectedResident->pwd_classification ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Other PWD Specification</dt>
                                    <dd class="res-dd" id="resVal_pwd_other">{{ $selectedResident->pwd_other ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Out-of-School Youth</dt>
                                    <dd class="res-dd" id="resVal_out_of_school_youth">{{ $selectedResident->out_of_school_youth === null ? '—' : ($selectedResident->out_of_school_youth ? 'Yes' : 'No') }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Can Read and Write</dt>
                                    <dd class="res-dd" id="resVal_can_read_write">{{ $selectedResident->can_read_write === null ? '—' : ($selectedResident->can_read_write ? 'Yes' : 'No') }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Immunization Status</dt>
                                    <dd class="res-dd" id="resVal_immunization_status">{{ $selectedResident->immunization_status ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Senior Citizen Number</dt>
                                    <dd class="res-dd" id="resVal_senior_citizen_number">{{ $selectedResident->senior_citizen_number ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- ════ Section D: Contact & Identity ════ --}}
                        <div class="res-card-section">
                            <div class="res-section-header">
                                <span class="material-icons-round">badge</span>
                                <h4>Section D: Contact &amp; Identity</h4>
                            </div>
                            <dl class="res-details-grid">
                                <div>
                                    <dt class="res-dt">Email Address</dt>
                                    <dd class="res-dd" id="resVal_email">{{ $selectedResident->email ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Contact Number</dt>
                                    <dd class="res-dd" id="resVal_contact_number">{{ $selectedResident->contact_number ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Blood Type</dt>
                                    <dd class="res-dd" id="resVal_blood_type">{{ $selectedResident->blood_type ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Height</dt>
                                    <dd class="res-dd" id="resVal_height_cm">{{ $selectedResident->height_cm ? $selectedResident->height_cm . ' cm' : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Weight</dt>
                                    <dd class="res-dd" id="resVal_weight_kg">{{ $selectedResident->weight_kg ? $selectedResident->weight_kg . ' kg' : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Registered Voter</dt>
                                    <dd class="res-dd" id="resVal_registered_voter">{{ $selectedResident->registered_voter === null ? '—' : ($selectedResident->registered_voter ? 'Yes' : 'No') }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Last Year Voted</dt>
                                    <dd class="res-dd" id="resVal_last_year_voted">{{ $selectedResident->last_year_voted ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Location Registered</dt>
                                    <dd class="res-dd" id="resVal_voter_location">{{ $selectedResident->voter_location ?? '—' }}</dd>
                                </div>
                                <div class="res-grid-col-full">
                                    <dt class="res-dt">Mother's Maiden Name</dt>
                                    <dd class="res-dd" id="resVal_mother_name">{{ $motherMaiden }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- ════ Section E: Record Information ════ --}}
                        <div class="res-card-section res-record-info-section">
                            <div class="res-section-header">
                                <span class="material-icons-round">history</span>
                                <h4>Section E: Record Information</h4>
                            </div>
                            <dl class="res-details-grid">
                                <div>
                                    <dt class="res-dt">Date Registered</dt>
                                    <dd class="res-dd" id="resVal_created_at">{{ $selectedResident->created_at ? $selectedResident->created_at->format('M d, Y h:i A') : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="res-dt">Last Updated</dt>
                                    <dd class="res-dd" id="resVal_updated_at">{{ $selectedResident->updated_at ? $selectedResident->updated_at->format('M d, Y h:i A') : '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>{{-- /res-profile-content --}}
                    @else
                    <div class="res-empty-profile">
                        <span class="material-icons-round res-empty-icon">person_search</span>
                        <h4>No Residents Found</h4>
                        <p>There are no resident records matching your current search or filter criteria. Click below to register a resident or reset your search filters.</p>
                        <div class="res-empty-actions">
                            <button type="button" class="btn btn-primary" id="emptyAddBtn" data-create-url="{{ route(\App\Support\RecordPortal::routeName('residents.create')) }}"><span class="material-icons-round">add</span> Add New Resident</button>
                            <a href="{{ route(\App\Support\RecordPortal::routeName('residents.index')) }}" class="btn btn-ghost">Reset Filters</a>
                        </div>
                    </div>
                    @endif
                </div>{{-- /res-split-right --}}

            </div>{{-- /res-split-container --}}

        </div>{{-- /page-content --}}
    </div>{{-- /main-area --}}
</div>{{-- /app-shell --}}

{{-- ════════════════════════════════════════
     ADD & EDIT RESIDENT MODALS
════════════════════════════════════════ --}}


@if(isset($viewResident))
<div id="residentViewData" hidden data-resident="{{ json_encode(array_merge($viewResident->only(array_keys(\App\Support\ResidentForm::fields())), ['id' => $viewResident->id, 'birthdate' => $viewResident->birthdate?->format('Y-m-d')])) }}"></div>
@endif
@if(isset($editResident))
<div id="residentEditData" hidden data-update-url="{{ route(\App\Support\RecordPortal::routeName('residents.update'), $editResident) }}" data-resident="{{ json_encode(array_merge($editResident->only(array_keys(\App\Support\ResidentForm::fields())), ['id'=>$editResident->id, 'birthdate'=>$editResident->birthdate?->format('Y-m-d')])) }}"></div>
@endif
<script src="{{ asset('js/resident-individual-form.js') }}" defer></script>
<script src="{{ asset('js/resident-live-search.js') }}" defer></script>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199"></div>
</body>
</html>
