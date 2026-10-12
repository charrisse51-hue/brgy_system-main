<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $mode === 'edit' ? 'Edit Resident' : 'Add New Resident' }} – Barangay Doyong EDRMS</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/resident_record.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/emergency-record-form.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/resident-individual-form.css') }}"/>
    @include('components.ui-assets')
</head>
<body class="admin-theme resident-form-workspace">
<div class="app-shell">
    @include('admin.partials.sidebar', ['sidebarSubtitle' => request()->routeIs('staff.*') ? 'RECORDS STAFF' : 'RECORDS ADMIN'])
    <main class="main-area">
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <a class="topbar-back" href="{{ route(\App\Support\RecordPortal::routeName('residents.index')) }}" aria-label="Back to Resident Records"><span class="material-icons-round">arrow_back</span></a>
                <div><h2>{{ $mode === 'edit' ? 'Edit Resident' : 'Add New Resident' }}</h2><p>Complete the individual resident record</p></div>
            </div>
        </header>
        <div class="page-content er-page">
            <nav class="er-breadcrumb" aria-label="Breadcrumb"><a href="{{ route(\App\Support\RecordPortal::routeName('residents.index')) }}">Resident Records</a><span aria-hidden="true">›</span><span>{{ $mode === 'edit' ? 'Edit Resident' : 'Add New Resident' }}</span></nav>
            @include('admin.partials.resident-individual-modal', ['fullPage' => true])
        </div>
    </main>
</div>
<script src="{{ asset('js/resident-individual-form.js') }}" defer></script>
</body>
</html>
