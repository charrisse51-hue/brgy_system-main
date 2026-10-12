<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Dashboard – Barangay Doyong EDRMS</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>


    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/staff-dashboard.css') }}"/>
    @include('components.ui-assets')
</head>
<body class="admin-theme admin-dashboard staff-dashboard bg-background-light font-[Public_Sans] text-slate-800">

<div class="flex h-screen overflow-hidden">

    {{-- ═══════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════ --}}
    @include('staff.partials.sidebar')

    {{-- ═══════════════════════════════════
         MAIN CONTENT AREA
    ═══════════════════════════════════ --}}
    <main class="flex-1 min-w-0 flex flex-col overflow-hidden">
        {{-- Top Header --}}
        @include('components.workspace-header', ['heading' => 'Records overview'])

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="mx-8 mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-700 text-sm font-medium">
            <span class="material-icons-round text-lg">check_circle</span>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mx-8 mt-4 p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-3 text-rose-700 text-sm font-medium">
            <span class="material-icons-round text-lg">error</span>
            {{ session('error') }}
        </div>
        @endif

        {{-- Scrollable Content --}}
        @include('admin.partials.dashboard_content')
    </main>
</div>

</body>
</html>
