<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>System Settings – Barangay Doyong EDRMS</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/incidents.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-records-theme.css') }}"/>
    <style>
        .settings-content { display: grid; gap: 24px; max-width: 900px; }
        .settings-card { padding: 24px; }
        .settings-card h3 { margin: 0 0 8px; font-size: 16px; }
        .settings-card p { font-size: 13px; color: var(--gray-500); line-height: 1.6; }
        .settings-values { margin: 20px 0 0; }
        .settings-values div { display: grid; grid-template-columns: minmax(120px, 1fr) 2fr; gap: 16px; padding: 14px 0; border-top: 1px solid var(--gray-200); font-size: 13px; }
        .settings-values dt { color: var(--gray-500); }
        .settings-values dd { margin: 0; overflow-wrap: anywhere; }
        .settings-card .btn { margin-top: 16px; }
        @media (max-width: 600px) { .settings-values div { grid-template-columns: 1fr; gap: 6px; } }
    </style>
    @include('components.ui-assets')
</head>
<body class="admin-theme">
<div class="app-shell">
    @include('admin.partials.sidebar')
    <main class="main-area">
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <h2>System Settings</h2>
                <p>Review system configuration and manage administrator access</p>
            </div>
        </header>
        <div class="page-content settings-content">
            <section class="inc-table-card settings-card" aria-labelledby="general-settings-title">
                <h3 id="general-settings-title">General Configuration</h3>
                <p>These values are managed in the server configuration and are shown here for reference.</p>
                <dl class="settings-values">
                    <div><dt>Barangay</dt><dd>Barangay Doyong</dd></div>
                    <div><dt>Application name</dt><dd>{{ config('app.name') }}</dd></div>
                    <div><dt>Time zone</dt><dd>{{ config('app.timezone') }}</dd></div>
                    <div><dt>Language code</dt><dd>{{ config('app.locale') }}</dd></div>
                    <div><dt>Session lifetime</dt><dd>{{ config('session.lifetime') }} minutes of inactivity</dd></div>
                </dl>
            </section>
            <section class="inc-table-card settings-card" aria-labelledby="access-settings-title">
                <h3 id="access-settings-title">Accounts &amp; Access</h3>
                <p>Manage staff accounts, roles, and passwords in User Management.</p>
                <a class="btn btn-primary" href="{{ route('admin.users.index') }}">Open User Management</a>
            </section>
        </div>
    </main>
</div>
</body>
</html>
