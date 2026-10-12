<aside class="sidebar">
    @include('components.sidebar-brand', ['subtitle' => 'RECORDS STAFF'])
    <nav class="sidebar-nav" aria-label="Staff navigation">
        <a href="{{ route('staff.dashboard') }}" class="nav-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}"><span class="material-icons-round">dashboard</span><span>Dashboard</span></a>
        @foreach(['incidents'=>['report_problem','Disaster Records'], 'emergency'=>['notification_important','Emergency Records'], 'residents'=>['groups','Resident Records']] as $module=>$item)
        <a href="{{ route('staff.'.$module.'.index') }}" class="nav-item {{ request()->routeIs('staff.'.$module.'.*') ? 'active' : '' }}" @if(request()->routeIs('staff.'.$module.'.*')) aria-current="page" @endif>
            <span class="material-icons-round">{{ $item[0] }}</span><span>{{ $item[1] }}</span>
        </a>
        @endforeach
        <a href="{{ route('staff.summary-reports.index') }}" class="nav-item {{ request()->routeIs('staff.summary-reports.*') ? 'active' : '' }}" @if(request()->routeIs('staff.summary-reports.*')) aria-current="page" @endif>
            <span class="material-icons-round">summarize</span><span>Summary Reports</span>
        </a>
    </nav>
    @include('components.account-menu')
</aside>
