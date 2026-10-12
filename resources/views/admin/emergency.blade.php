<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Emergency Records – Barangay Doyong EDRMS</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/disaster-records.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/emergency-records.css') }}"/>
    @include('components.ui-assets')
</head>
<body class="admin-theme disaster-records emergency-records">
<div class="app-shell">
    @include('admin.partials.sidebar', ['sidebarSubtitle' => 'RECORDS ADMIN'])
    <main class="main-area">
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <h2>Emergency Records</h2>
                <p>Post-incident emergency response details submitted by staff</p>
            </div>
            <a class="btn btn-primary" href="{{ route(\App\Support\RecordPortal::routeName('emergency.create')) }}">
                <span class="material-icons-round">add</span> New Record
            </a>
        </header>
        @if(session('success'))
        <div class="flash-msg success">{{ session('success') }}</div>
        @endif
        <div class="page-content">
            <section class="em-record-card" aria-labelledby="recordsHeading">
                <div class="em-record-header">
                    <div>
                        <h3 id="recordsHeading">Emergency Response Records</h3>
                        <p>Details of emergency incidents and response actions</p>
                    </div>
                    <form method="GET" action="{{ route(\App\Support\RecordPortal::routeName('emergency.index')) }}" role="search">
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search records..." aria-label="Search emergency records"/>
                    </form>
                </div>
                <div class="table-wrap">
                    @php $emergencyTypes = request()->routeIs('staff.*') ? \App\Support\EmergencyRecordForm::types() : []; @endphp
                    <table class="data-table">
                        <thead><tr>
                            @if(request()->routeIs('staff.*'))
                            <th>Record ID</th><th>Report Date &amp; Time</th><th>Report Source</th>
                            <th>Emergency Type</th><th>Location</th><th>Incident Date &amp; Time</th>
                            <th>Reporter Name</th><th>Verification Status</th><th>Action</th>
                            @else
                            <th>Record ID</th><th>Incident Type</th><th>Response Action</th>
                            <th>Submitted By</th><th>Date Submitted</th><th>Status</th><th>Action</th>
                            @endif
                        </tr></thead>
                        <tbody>
                            @foreach($alerts as $alert)
                            <tr>
                                <td class="record-id">#EMG-{{ str_pad($alert->id, 4, '0', STR_PAD_LEFT) }}</td>
                                @if(request()->routeIs('staff.*'))
                                <td class="record-date">{{ $alert->report_date_time?->format('M d, Y · g:i A') ?? 'Not specified' }}</td>
                                <td>{{ $alert->report_source ?: 'Not specified' }}</td>
                                <td>{{ $emergencyTypes[$alert->emergency_type] ?? ($alert->emergency_type ?: $alert->title) }}</td>
                                <td>{{ $alert->location ?: 'Not specified' }}</td>
                                <td class="record-date">{{ $alert->incident_date?->format('M d, Y') ?? 'Not specified' }}<br>{{ $alert->incident_time ?? '' }}</td>
                                <td>{{ $alert->reporter_name ?: 'Not specified' }}</td>
                                <td>{{ $alert->is_record ? ucfirst($alert->verification_status ?: 'pending') : 'Not applicable' }}</td>
                                @else
                                <td><span class="record-dot"></span>{{ $alert->title }}</td>
                                <td>{{ $alert->is_record ? $alert->actions_taken : $alert->message }}</td>
                                <td>{{ $alert->creator->name ?? '—' }}</td>
                                <td class="record-date">{{ $alert->created_at->format('M d, Y · g:i A') }}</td>
                                <td><span class="record-level level-{{ $alert->level }}">{{ $alert->is_record ? ucfirst($alert->verification_status) : strtoupper($alert->level) }}</span></td>
                                @endif
                                <td>
                                    @if($alert->is_record)
                                    <a class="record-view" href="{{ route(\App\Support\RecordPortal::routeName('emergency.show'),$alert) }}"><span class="material-icons-round">visibility</span> View</a>
                                    <a class="record-view" href="{{ route(\App\Support\RecordPortal::routeName('emergency.edit'),$alert) }}"><span class="material-icons-round">edit</span> Edit</a>
                                    @else
                                    <button type="button" class="record-view" data-record="{{ json_encode(['id' => $alert->id, 'title' => $alert->title, 'message' => $alert->message, 'level' => strtoupper($alert->level)]) }}">
                                        <span class="material-icons-round">visibility</span> View
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @if($alerts->isEmpty())<tr><td colspan="{{ request()->routeIs('staff.*') ? 9 : 7 }}">No emergency records found.</td></tr>@endif
                        </tbody>
                    </table>
                </div>
                @if($alerts->hasPages())
                <div class="record-pagination">{{ $alerts->links() }}</div>
                @endif
            </section>
        </div>
    </main>
</div>
<dialog id="viewRecordDialog" class="record-dialog" aria-labelledby="viewRecordTitle">
    <div class="dialog-heading"><h3 id="viewRecordTitle"></h3><button type="button" data-close aria-label="Close">&times;</button></div>
    <p id="viewRecordLevel"></p>
    <p id="viewRecordMessage"></p>
</dialog>
<script src="{{ asset('js/emergency-records.js') }}" defer></script>

</body>
</html>
