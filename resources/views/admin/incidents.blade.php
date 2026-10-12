<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isArchive ? 'Archives' : 'Disaster Records' }} – Barangay Doyong EDRMS</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    {{-- Shared dashboard styles (sidebar, topbar, badges, buttons) --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/incidents.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-records-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/disaster-records.css') }}"/>
    @if($showCreateModal ?? false)
    <link rel="stylesheet" href="{{ asset('css/incident-logbook.css') }}"/>
    @endif
    @include('components.ui-assets')
</head>
<body class="admin-theme disaster-records {{ request()->routeIs('staff.*') ? 'staff-disaster-records' : '' }}" data-incidents-url="{{ route(\App\Support\RecordPortal::routeName('incidents.index')) }}" data-stats-url="{{ route(\App\Support\RecordPortal::routeName('dashboard.stats')) }}">
<div class="app-shell" @if($showCreateModal ?? false) inert @endif>

    {{-- ═══════════════════════════════════════
         SIDEBAR  (same as dashboard.blade.php)
    ═══════════════════════════════════════ --}}
    @include('admin.partials.sidebar', ['sidebarSubtitle' => request()->routeIs('staff.*') ? 'RECORDS STAFF' : 'RECORDS ADMIN'])

    {{-- ═══════════════════════════════════════
         MAIN AREA
    ═══════════════════════════════════════ --}}
    <div class="main-area">

        {{-- TOPBAR --}}
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <h2>{{ $isArchive ? 'Archives' : 'Disaster Records' }}</h2>
                <p>{{ $isArchive ? 'Browse archived incident reports' : 'Manage and track barangay disaster records' }}</p>
            </div>
            <div class="topbar-right">
                @if(!$isArchive)
                <a class="btn btn-primary" href="{{ route(\App\Support\RecordPortal::routeName('incidents.create')) }}">
                    <span class="material-icons-round">add</span> New Record
                </a>
                @endif
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

            {{-- ── FILTER TABS + SEARCH BAR ── --}}
            

            {{-- ── INCIDENTS TABLE CARD ── --}}
            <form class="incidents-toolbar" method="GET" action="{{ $isArchive ? route('admin.archives.index') : route(\App\Support\RecordPortal::routeName('incidents.index')) }}">
                <div class="incidents-search-group">
                    <div class="inc-search-wrap"><span class="material-icons-round" aria-hidden="true">search</span><input class="inc-search-input" type="search" name="search" value="{{ request('search') }}" placeholder="Search location, ID or reporter..." aria-label="Search disaster records"/></div>
                    <select class="inc-filter-select" name="type" aria-label="Disaster type"><option value="">All Types</option>@foreach(['fire','accident','theft','flood','others'] as $type)<option value="{{ $type }}" @selected(request('type')===$type)>{{ ucfirst($type) }}</option>@endforeach</select>
                    <select class="inc-filter-select" name="status" aria-label="Incident status"><option value="">All Statuses</option>@foreach(['pending','ongoing','resolved'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select>
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
            <div class="inc-table-card">

                <div class="table-wrap">
                    <table class="data-table" id="incidentsTable">
                        <thead>
                            <tr>
                                @if(request()->routeIs('staff.*'))
                                <th>Record Number</th>
                                <th>Record Date &amp; Time</th>
                                <th>Complainant / Reporting Person</th>
                                <th>Type of Incident</th>
                                <th>Incident Date &amp; Time</th>
                                <th>Location of Incident</th>
                                <th>Recorded By</th>
                                @else
                                <th>Disaster ID</th>
                                <th>Disaster Type</th>
                                <th>Date of Occurrence</th>
                                <th>Location / Affected Area</th>
                                <th>Severity Level</th>
                                <th class="text-center">Affected Residents</th>
                                <th class="text-center">Rescued</th>
                                @endif
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="incidentsTableBody">
                            @foreach($incidents ?? [] as $incident)
                            <tr class="inc-row">
                                <td class="td-id">{{ $incident->record_number }}</td>
                                @if(request()->routeIs('staff.*'))
                                <td>{{ $incident->logbook['record_date'] ?? 'Not specified' }}<br>{{ $incident->logbook['record_time'] ?? '' }}</td>
                                <td>{{ $incident->logbook['complainant_name'] ?? 'Not specified' }}</td>
                                <td>{{ $incident->type === 'theft' ? 'Theft / Crime' : ucfirst($incident->type) }}</td>
                                <td>{{ $incident->logbook['incident_date'] ?? 'Not specified' }}<br>{{ $incident->logbook['incident_time'] ?? '' }}</td>
                                <td>{{ $incident->location ?: 'Not specified' }}</td>
                                <td>{{ $incident->logbook['recorded_by'] ?? $incident->user->name ?? 'Not specified' }}</td>
                                @else
                                <td><div class="td-type"><span class="type-dot dot-crime"></span>{{ ucfirst($incident->type) }}</div></td>
                                <td class="td-date">{{ ($incident->occurred_at ?? $incident->created_at)->format('M d, Y · g:i A') }}</td>
                                <td>{{ $incident->location ?: 'Not specified' }}</td>
                                <td><span class="ui-severity" data-severity="{{ strtolower($incident->severity ?? '') }}">{{ $incident->severity ? ucfirst($incident->severity) : '—' }}</span></td>
                                <td class="text-center">{{ $incident->affected_residents ?? '—' }}</td>
                                <td class="text-center">{{ $incident->rescued ?? '—' }}</td>
                                @endif
                                <td class="text-center">
                                    <a href="{{ route(\App\Support\RecordPortal::routeName('incidents.show'), $incident) }}" class="inc-action-btn" title="View record" aria-label="View disaster record {{ $incident->id }}">
                                        <span class="material-icons-round">visibility</span>
                                    </a>
                                    @if(!$isArchive)
                                    <a href="{{ route(\App\Support\RecordPortal::routeName('incidents.edit'), $incident) }}" class="inc-action-btn" title="Edit record" aria-label="Edit disaster record {{ $incident->id }}"><span class="material-icons-round">edit</span></a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @if($incidents->isEmpty())<tr><td colspan="8" class="inc-empty-state">{{ $isArchive ? 'No archived reports found' : 'No disaster records found' }}</td></tr>@endif
                        </tbody>
                    </table>
                </div>

                {{-- No-search-results row (hidden by default, shown via JS) --}}
                <div id="noSearchResults" class="inc-empty-state" style="display:none;padding:48px">
                    <div class="inc-empty-icon">
                        <span class="material-icons-round" style="font-size:40px">search_off</span>
                    </div>
                    <h3>No results found</h3>
                    <p>Try a different search term or clear the filter.</p>
                </div>

                {{-- TABLE FOOTER / PAGINATION --}}
                <div class="inc-table-footer">
                    <p class="inc-count-text">
                        Showing
                        <strong>{{ $incidents->firstItem() ?? 0 }}</strong>
                        to
                        <strong>{{ $incidents->lastItem() ?? 0 }}</strong>
                        of
                        <strong>{{ $incidents->total() ?? 0 }}</strong>
                        reports
                    </p>
                    <div class="inc-pagination">
                        {{-- Previous --}}
                        @if(isset($incidents) && $incidents->onFirstPage())
                        <button class="pg-btn" disabled>
                            <span class="material-icons-round">chevron_left</span>
                        </button>
                        @else
                        <a href="{{ $incidents->previousPageUrl() }}" class="pg-btn">
                            <span class="material-icons-round">chevron_left</span>
                        </a>
                        @endif

                        {{-- Page numbers --}}
                        @if(isset($incidents))
                        @for($p = 1; $p <= $incidents->lastPage(); $p++)
                        <a href="{{ $incidents->url($p) }}"
                           class="pg-btn {{ $incidents->currentPage() === $p ? 'pg-btn--active' : '' }}">
                            {{ $p }}
                        </a>
                        @endfor
                        @endif

                        {{-- Next --}}
                        @if(isset($incidents) && $incidents->hasMorePages())
                        <a href="{{ $incidents->nextPageUrl() }}" class="pg-btn">
                            <span class="material-icons-round">chevron_right</span>
                        </a>
                        @else
                        <button class="pg-btn" disabled>
                            <span class="material-icons-round">chevron_right</span>
                        </button>
                        @endif
                    </div>
                </div>

            </div>{{-- /inc-table-card --}}

        </div>{{-- /page-content --}}
    </div>{{-- /main-area --}}
</div>{{-- /app-shell --}}

{{-- ═══════════════════════════════════════════════════
     STATUS UPDATE MODAL
     Opens when admin clicks the ✏️ Edit Status button
═══════════════════════════════════════════════════ --}}
<div id="statusModal" class="modal-overlay" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Update Incident Status</h3>
                <p class="modal-sub" id="modalIncidentId"></p>
            </div>
            <button class="modal-close-btn" id="closeModal">
                <span class="material-icons-round">close</span>
            </button>
        </div>
        <div class="modal-body">
            <label class="modal-label">New Status</label>
            <div class="status-options" id="statusOptions">
                <label class="status-option">
                    <input type="radio" name="incidentStatus" value="pending">
                    <span class="status-option-card">
                        <span class="material-icons-round">hourglass_empty</span>
                        <span>Pending</span>
                        <span class="badge badge-pending" style="margin-left:auto">Pending</span>
                    </span>
                </label>
                <label class="status-option">
                    <input type="radio" name="incidentStatus" value="ongoing">
                    <span class="status-option-card">
                        <span class="material-icons-round">sync</span>
                        <span>Ongoing</span>
                        <span class="badge badge-ongoing" style="margin-left:auto">Ongoing</span>
                    </span>
                </label>
                <label class="status-option">
                    <input type="radio" name="incidentStatus" value="resolved">
                    <span class="status-option-card">
                        <span class="material-icons-round">task_alt</span>
                        <span>Resolved</span>
                        <span class="badge badge-resolved" style="margin-left:auto">Resolved</span>
                    </span>
                </label>
            </div>
            <div class="modal-note">
                <span class="material-icons-round">info</span>
                {{ request()->routeIs('staff.*') ? 'Choose the current incident status.' : 'Archived status is available after marking Resolved.' }}
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" id="cancelModal">Cancel</button>
            <button class="btn btn-primary" id="confirmStatusBtn">
                <span class="material-icons-round">save</span>
                Save Changes
            </button>
        </div>
    </div>
</div>

{{-- Shared JS --}}
@if($showCreateModal ?? false)
    @include('admin.partials.incident-logbook-modal')
    <script src="{{ asset('js/incident-logbook.js') }}" defer></script>
@endif

{{-- Page-specific JS --}}
<script src="{{ asset('js/incidents.js') }}" defer></script>

</body>
</html>
