@if(request()->routeIs('staff.*') || auth()->user()?->role === 'staff')
@include('staff.partials.sidebar')
@else
    <aside class="sidebar">
        @include('components.sidebar-brand', ['subtitle' => $sidebarSubtitle ?? 'RECORDS ADMIN'])

        <nav class="sidebar-nav">
            <a href="{{ url('/admin/dashboard') }}"
               class="nav-item {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                <span class="material-icons-round">dashboard</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ url('/admin/incidents') }}"
               class="nav-item {{ request()->is('admin/incidents*') ? 'active' : '' }}">
                <span class="material-icons-round">report_problem</span>
                <span>Disaster Records</span>
            </a>

            <a href="{{ url('/admin/emergency') }}"
               class="nav-item {{ request()->is('admin/emergency*') ? 'active' : '' }}">
                <span class="material-icons-round">notification_important</span>
                <span>Emergency Records</span>
            </a>


            <a href="{{ url('/admin/residents') }}"
               class="nav-item {{ request()->is('admin/residents*') ? 'active' : '' }}">
                <span class="material-icons-round">groups</span>
                <span>Resident Records</span>
            </a>

            <a href="{{ url('/admin/users') }}"
               class="nav-item {{ request()->is('admin/users*') ? 'active' : '' }}">
                <span class="material-icons-round">manage_accounts</span>
                <span>User Management</span>
            </a>

            <a href="{{ route('admin.activity-logs.index') }}"
               class="nav-item {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                <span class="material-icons-round">history</span>
                <span>Activity Logs</span>
            </a>

                    @if(auth()->user()?->role === 'admin')
            <a href="{{ route('admin.summary-reports.index') }}" class="nav-item nav-link {{ request()->is('admin/summary-reports*') ? 'active sidebar-item-active' : '' }}">
                <span class="material-icons-round">summarize</span>
                <span>Summary Reports</span>
            </a>
            <a href="{{ route('admin.settings.index') }}"
               class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <span class="material-icons-round">settings</span>
                <span>System Settings</span>
            </a>
            @endif
</nav>

        @include('components.account-menu')
    </aside>

@endif
