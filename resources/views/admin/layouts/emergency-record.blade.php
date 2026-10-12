<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title') - Barangay Doyong</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/disaster-records.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/emergency-records.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/emergency-record-form.css') }}"/>
    @include('components.ui-assets')
</head>
<body class="admin-theme disaster-records emergency-records {{ request()->routeIs('staff.*') ? 'staff-emergency-records' : '' }}">
<div class="app-shell">
    @include('admin.partials.sidebar', ['sidebarSubtitle' => request()->routeIs('staff.*') ? 'RECORDS STAFF' : 'RECORDS ADMIN'])
    <main class="main-area">
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <a class="topbar-back" href="{{ route(\App\Support\RecordPortal::routeName('emergency.index')) }}" aria-label="Back to emergency records" title="Back to Emergency Records">
                    <span class="material-icons-round">arrow_back</span>
                </a>
                <div>
                    <h2>@yield('title')</h2>
                    <p>Record information about an emergency incident based on the barangay's existing paper incident record form.</p>
                </div>
            </div>
        </header>
        <div class="page-content er-page">
            <nav class="er-breadcrumb" aria-label="Breadcrumb"><a href="{{ route(\App\Support\RecordPortal::routeName('emergency.index')) }}">Emergency Records</a><span aria-hidden="true">›</span><span>@yield('title')</span></nav>
            @if(session('success'))<div class="flash-msg success" role="status">{{ session('success') }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
@yield('dialogs')
@yield('scripts')
</body></html>
