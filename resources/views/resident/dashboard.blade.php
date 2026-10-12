@extends('layouts.app')

@section('title', 'Dashboard - Barangay Doyong Resident Portal')

{{-- ── Styles ── --}}
@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/resident/dashboard.css') }}"/>
@endpush

{{-- ── Content ── --}}
@section('content')
<div class="layout-wrapper">

    {{-- ════════════════════════════
         SIDEBAR
         ════════════════════════════ --}}
    <aside class="sidebar">

        {{-- Brand --}}
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <span class="material-symbols-outlined">gpp_maybe</span>
            </div>
            <div>
                <div class="sidebar-brand-title">Brgy. Doyong</div>
                <div class="sidebar-brand-sub">Resident Portal</div>
            </div>
        </div>

        {{-- Emergency Button --}}
        <div class="sidebar-emergency-wrap">
            <a href="{{ route('resident.emergency.create') }}" class="btn-emergency">
                <span class="material-symbols-outlined">emergency_share</span>
                <span>REPORT EMERGENCY</span>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">
            <a href="{{ route('resident.dashboard') }}"
               class="nav-link {{ request()->routeIs('resident.dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('resident.profile') }}"
               class="nav-link {{ request()->routeIs('resident.profile') ? 'active' : '' }}">
                <span class="material-symbols-outlined">person</span>
                <span>Personal Profile</span>
            </a>

            <a href="{{ route('resident.alerts') }}"
               class="nav-link {{ request()->routeIs('resident.alerts') ? 'active' : '' }}">
                <span class="material-symbols-outlined">campaign</span>
                <span>Community Alerts</span>
            </a>

        </nav>

        {{-- Footer: Hotline + Sign Out --}}
        <div class="sidebar-footer">
            <div class="hotline-card">
                <div class="hotline-label">Emergency Hotline</div>
                <div class="hotline-number">911-DOYONG</div>
            </div>

            {{-- Hidden sign-out form --}}
            <form id="signout_form" method="POST" action="{{ route('logout') }}">
                @csrf
            </form>

            <button id="btn_signout" type="button" class="btn-signout">
                <span class="material-symbols-outlined">logout</span>
                <span>Sign Out</span>
            </button>
        </div>

    </aside>

    {{-- ════════════════════════════
         MAIN
         ════════════════════════════ --}}
    <main class="main-content">

        {{-- ── Sticky Header ── --}}
        <header class="page-header">
            @include('components.navigation-toggle')

            {{-- Breadcrumb --}}
            <div class="breadcrumb">
                <span class="breadcrumb-item">Resident</span>
                <span class="breadcrumb-sep material-symbols-outlined">chevron_right</span>
                <span class="breadcrumb-item current">Dashboard Overview</span>
            </div>

            {{-- Right: Notifications + User --}}
            <div class="header-right">

                <button id="btn_notif" type="button" class="btn-notif" title="Notifications">
                    <span class="material-symbols-outlined">notifications</span>
                    {{-- Show dot only if there are unread notifications --}}
                    @if(isset($unreadNotifications) && $unreadNotifications > 0)
                        <span class="notif-dot"></span>
                    @endif
                </button>

                <div class="header-divider"></div>

                <div class="header-user">
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name ?? 'Juan Dela Cruz' }}</div>
                        <div class="user-role">
                            Purok {{ Auth::user()->resident->purok ?? '—' }}, Resident
                        </div>
                    </div>

                    @if(Auth::user()->profile_photo ?? null)
                        <img src="{{ Storage::url(Auth::user()->profile_photo) }}"
                             alt="{{ Auth::user()->name }}"
                             class="user-avatar"/>
                    @else
                        <div class="user-avatar-initials">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                        </div>
                    @endif
                </div>

            </div>
        </header>

        {{-- ── Page Body ── --}}
        <div class="page-inner">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success">
                    <span class="material-symbols-outlined">check_circle</span>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    <span class="material-symbols-outlined">error</span>
                    {{ session('error') }}
                </div>
            @endif

            {{-- ══════════════════════════
                 SECTION: Emergency Hotlines
                 ══════════════════════════ --}}
            <section class="hotlines-section">
                <div class="hotlines-card">
                    <div class="hotlines-card-header">
                        <div class="hotlines-card-title">
                            <span class="material-symbols-outlined">call</span>
                            Emergency Hotlines
                        </div>
                        <span class="hotlines-badge">Available 24/7</span>
                    </div>

                    <div class="hotlines-grid">
                        {{--
                            These can be made dynamic by passing $hotlines
                            from the controller. For now they are static.
                            To make dynamic: @foreach($hotlines as $hotline)
                        --}}
                        <div class="hotline-item">
                            <div class="hotline-item-label">Barangay Hall</div>
                            <div class="hotline-item-number">
                                {{ $hotlines['barangay_hall'] ?? '0917-123-4567' }}
                            </div>
                        </div>

                        <div class="hotline-item">
                            <div class="hotline-item-label">MDRRMO</div>
                            <div class="hotline-item-number">
                                {{ $hotlines['mdrrmo'] ?? '0918-987-6543' }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ══════════════════════════
                 SECTION: Active Alerts Feed
                 ══════════════════════════ --}}
            @if(isset($alerts) && $alerts->count() > 0)
            <section class="alerts-section">
                <div class="alerts-card">
                    <div class="alerts-card-header">
                        <div class="alerts-card-title">
                            <span class="material-symbols-outlined">campaign</span>
                            Active Alerts Feed
                        </div>
                        <span class="alerts-badge">{{ $alerts->count() }} active</span>
                    </div>

                    <div class="alerts-list">
                        @foreach($alerts as $alert)
                        <div class="alert-item">
                            <div class="alert-item-icon">
                                @if(($alert->priority ?? 'low') === 'high')
                                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">error</span>
                                @elseif(($alert->priority ?? 'low') === 'medium')
                                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">warning</span>
                                @else
                                    <span class="material-symbols-outlined">info</span>
                                @endif
                            </div>
                            <div class="alert-item-content">
                                <div class="alert-item-title">{{ $alert->title ?? 'Emergency Alert' }}</div>
                                <div class="alert-item-message">{{ $alert->message ?? 'Please stay alert and follow safety protocols.' }}</div>
                                <div class="alert-item-meta">
                                    <span class="alert-item-date">
                                        <span class="material-symbols-outlined">schedule</span>
                                        {{ $alert->created_at->format('M d, Y g:i A') }}
                                    </span>
                                    @if($alert->priority ?? null)
                                    <span class="alert-item-priority {{ 'priority-' . strtolower($alert->priority) }}">
                                        {{ ucfirst($alert->priority) }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif



        </div>{{-- /page-inner --}}

        {{-- ── Footer ── --}}
        <footer class="page-footer">
            <p>© {{ date('Y') }} Barangay Doyong Emergency and Records Management System. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">System Status</a>
            </div>
        </footer>

    </main>

</div>
@endsection

{{-- ── Scripts ── --}}
@push('scripts')
    @vite('resources/js/resident/resident.js')
@endpush