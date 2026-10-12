@extends('layouts.app')

@section('title', 'Community Alerts – Barangay Doyong')

{{-- ════════════════════════════
     STYLES  (same stack as resident_dashboard)
     ════════════════════════════ --}}
@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    {{-- page-specific stylesheet: prefer Vite build but fall back when manifest missing --}}
    @php
        $manifest = [];
        if (file_exists(public_path('build/manifest.json'))) {
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true) ?: [];
        }
    @endphp

    @if(array_key_exists('resources/css/resident/comalerts.css', $manifest))
        @vite('resources/css/resident/comalerts.css')
    @else
        <link rel="stylesheet" href="{{ asset('css/resident/comalerts.css') }}"/>
    @endif
@endpush

@section('content')
<div class="layout-wrapper">

    {{-- ════════════════════════════
         SIDEBAR  — identical to resident_dashboard
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
            <a href="{{ route('resident.emergency.create') }}"
               id="btn_emergency"
               class="btn-emergency">
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

            <form id="signout_form" method="POST" action="{{ route('logout') }}" style="display:none">
                @csrf
            </form>

            <button id="btn_signout" type="button" class="btn-signout">
                <span class="material-symbols-outlined">logout</span>
                <span>Sign Out</span>
            </button>
        </div>

    </aside>

    {{-- ════════════════════════════
         MAIN CONTENT
         ════════════════════════════ --}}
    <main class="main-content">

        {{-- ── Sticky Header ── --}}
        <header class="page-header">
            @include('components.navigation-toggle')

            {{-- Breadcrumb --}}
            <div class="breadcrumb">
                <span class="breadcrumb-item">Resident</span>
                <span class="breadcrumb-sep material-symbols-outlined">chevron_right</span>
                <span class="breadcrumb-item current">Community Alerts</span>
            </div>

            {{-- Right: Notifications + User --}}
            <div class="header-right">
                <button id="btn_notif" type="button" class="btn-notif" title="Notifications">
                    <span class="material-symbols-outlined">notifications</span>
                    @if($alerts->count() > 0)
                        <span class="notif-dot"></span>
                    @endif
                </button>

                <div class="header-divider"></div>

                <div class="header-user">
                    <div class="user-info">
                        <div class="user-name">
                            {{ ($resident->first_name ?? '') . ' ' . ($resident->last_name ?? Auth::user()->name) }}
                        </div>
                        <div class="user-role">
                            Purok {{ $resident->purok ?? '—' }}, Resident
                        </div>
                    </div>
                    @if(Auth::user()->profile_photo ?? null)
                        <img src="{{ Storage::url(Auth::user()->profile_photo) }}"
                             alt="{{ Auth::user()->name }}"
                             class="user-avatar"/>
                    @else
                        <div class="user-avatar-initials">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                    @endif
                </div>
            </div>

        </header>

        {{-- ── Page Body ── --}}
        <div class="page-inner">

            {{-- Flash messages --}}
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
                 ALERTS FEED
                 ══════════════════════════ --}}
            <section>

                <div class="alerts-section-header">
                    <h3 class="alerts-section-title">
                        <span class="material-symbols-outlined">rss_feed</span>
                        Active Alerts Feed
                    </h3>
                    <span id="alerts_count_badge" class="alerts-count-badge">
                        {{ $alerts->total() }} Active Alert{{ $alerts->total() !== 1 ? 's' : '' }}
                    </span>
                </div>

                {{-- Feed list --}}
                <div class="alerts-feed" id="alerts_feed">

                @forelse($alerts as $alert)
                    @php
                        /*
                         * severity values: high | medium | low | info
                         * card  border   : alert-card-{severity}
                         * icon  bg/color : alert-icon-{severity}
                         * badge bg/color : badge-{severity}
                         *
                         * icon mapping — extend as needed
                         */
                        $severity = strtolower($alert->severity ?? 'info');

                        $iconMap = [
                            'fire'     => 'local_fire_department',
                            'flood'    => 'water_drop',
                            'power'    => 'bolt',
                            'weather'  => 'thunderstorm',
                            'medical'  => 'medical_services',
                            'crime'    => 'gpp_bad',
                            'typhoon'  => 'cyclone',
                            'default'  => 'campaign',
                        ];

                        $type = strtolower($alert->type ?? 'default');
                        $icon = $iconMap[$type] ?? $iconMap['default'];

                        $timeAgo = $alert->created_at
                            ? $alert->created_at->diffForHumans()
                            : '—';
                    @endphp

                    <div class="alert-card alert-card-{{ $severity }}">
                        <div class="alert-card-inner">

                            {{-- Top row: icon + meta --}}
                            <div class="alert-card-top">
                                <div class="alert-card-left">
                                    <div class="alert-icon-wrap alert-icon-{{ $severity }}">
                                        <span class="material-symbols-outlined">{{ $icon }}</span>
                                    </div>
                                    <div>
                                        <div class="alert-meta">
                                            <span class="severity-badge badge-{{ $severity }}">
                                                {{ ucfirst($severity) }} Severity
                                            </span>
                                            <span class="alert-timestamp">• {{ $timeAgo }}</span>
                                        </div>
                                        <h4 class="alert-card-title">{{ $alert->title }}</h4>
                                    </div>
                                </div>
                            </div>

                            {{-- Body --}}
                            <p class="alert-description">{{ $alert->message }}</p>

                            {{-- Location --}}
                            <div class="alert-location">
                                <span class="material-symbols-outlined">location_on</span>
                                {{ $alert->location ?? 'Barangay Doyong' }}
                            </div>

                        </div>
                    </div>

                @empty
                    <div class="empty-state">
                        <div class="empty-icon-wrap">
                            <span class="material-symbols-outlined">campaign</span>
                        </div>
                        <p class="empty-title">No active alerts</p>
                        <p class="empty-sub">There are currently no community alerts. Stay safe!</p>
                    </div>
                @endforelse

                </div>{{-- /alerts_feed --}}

                {{-- Pagination --}}
                @if($alerts->hasPages())
                    <div class="pagination-wrap" style="margin-top:1.5rem;">
                        <span class="pagination-info">
                            Showing {{ $alerts->firstItem() }}–{{ $alerts->lastItem() }} of {{ $alerts->total() }} alerts
                        </span>
                        <div class="pagination">

                            @if($alerts->onFirstPage())
                                <button class="pg-btn" disabled>
                                    <span class="material-symbols-outlined">chevron_left</span>
                                    Prev
                                </button>
                            @else
                                <a href="{{ $alerts->previousPageUrl() }}" class="pg-btn">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                    Prev
                                </a>
                            @endif

                            @foreach($alerts->getUrlRange(1, $alerts->lastPage()) as $page => $url)
                                <a href="{{ $url }}"
                                   class="pg-btn {{ $page == $alerts->currentPage() ? 'active' : '' }}">
                                    {{ $page }}
                                </a>
                            @endforeach

                            @if($alerts->hasMorePages())
                                <a href="{{ $alerts->nextPageUrl() }}" class="pg-btn">
                                    Next
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </a>
                            @else
                                <button class="pg-btn" disabled>
                                    Next
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </button>
                            @endif

                        </div>
                    </div>
                @endif

            </section>

            {{-- ── Footer ── --}}
            <footer class="page-footer">
                <p>© {{ now()->year }} Barangay Doyong Emergency and Records Management System. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">System Status</a>
                </div>
            </footer>

        </div>{{-- /page-inner --}}

    </main>

</div>{{-- /layout-wrapper --}}

{{-- Toast --}}
<div id="toast" class="toast">
    <span class="material-symbols-outlined">check_circle</span>
    <span>Updated successfully</span>
</div>

@endsection

{{-- ════════════════════════════
     SCRIPTS
     ════════════════════════════ --}}
@push('scripts')
    {{-- common resident script --}}
    @vite('resources/js/resident/resident.js')
    {{-- page-specific script: prefer Vite build but fall back when manifest missing --}}
    @if(array_key_exists('resources/js/resident/comalerts.js', $manifest))
        @vite('resources/js/resident/comalerts.js')
    @else
        <script src="{{ asset('js/resident/comalerts.js') }}" defer></script>
    @endif
@endpush