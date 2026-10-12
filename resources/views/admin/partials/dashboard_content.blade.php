@php
    $totalIncidents = $totalIncidents ?? 0;
    $totalAlerts = $totalAlerts ?? 0;
    $totalResidents = $totalResidents ?? 0;
    $totalRecords = $totalRecords ?? ($totalResidents + $totalIncidents + $totalAlerts);
    $categoryChart = [
        'type' => 'doughnut',
        'labels' => ['Disaster', 'Emergency', 'Residents'],
        'values' => [(int) $totalIncidents, (int) $totalAlerts, (int) $totalResidents],
        'colors' => ['#ef4444', '#f59e0b', '#10b981'],
    ];
    $monthlyChart = ['type' => 'bar', 'labels' => array_column($monthlyRecords ?? [], 'label'), 'values' => array_column($monthlyRecords ?? [], 'count')];
    $recentResidents = \App\Models\Resident::latest()->take(5)->get();
@endphp

<div class="dashboard-content flex-1 overflow-y-auto p-8">
    @if(request()->routeIs('staff.dashboard'))
        <section class="staff-welcome mb-8" aria-label="Staff workspace">
            <div>
                <span class="staff-eyebrow">BARANGAY DOYONG · STAFF WORKSPACE</span>
                <h2>Your records at a glance</h2>
                <p>Keep barangay records organized and find the information you need.</p>
            </div>
            <a class="staff-report-link" href="{{ route('staff.summary-reports.index') }}">
                <span class="material-icons-round" aria-hidden="true">summarize</span>View Summary Reports
            </a>
        </section>
    @endif



    {{-- ══════════════════════════════════════
         RECORD STATISTICS CARDS
    ══════════════════════════════════════ --}}
    <div class="dashboard-summary grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Card 1: Total Residents --}}
        <div class="summary-card summary-card-residents bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-shadow">
            <div class="summary-icon w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3">
                <span class="material-icons-round text-xl">groups</span>
            </div>
            <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Residents</h3>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($totalResidents) }}</p>
        </div>

        {{-- Card 2: Total Emergency Records --}}
        <div class="summary-card summary-card-emergency bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-shadow">
            <div class="summary-icon w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3">
                <span class="material-icons-round text-xl">notification_important</span>
            </div>
            <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Emergency Records</h3>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($totalAlerts) }}</p>
        </div>

        {{-- Card 3: Total Disaster Records --}}
        <div class="summary-card summary-card-disaster bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-shadow">
            <div class="summary-icon w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3">
                <span class="material-icons-round text-xl">report_problem</span>
            </div>
            <div class="stat-card-title text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Disaster Records</div>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($totalIncidents) }}</p>
        </div>

        {{-- Card 4: All Records / Total Records --}}
        <div class="summary-card summary-card-all bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-shadow">
            <div class="summary-icon w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3">
                <span class="material-icons-round text-xl">archive</span>
            </div>
            <h3 class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">All Records</h3>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ number_format($totalRecords) }}</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════
         MAIN GRID
    {{-- High Alert Banner --}}
    @php
        $highAlert = isset($activeAlerts) ? $activeAlerts->where('level','high')->first() : null;
    @endphp
    @if($highAlert)
    <div class="system-update rounded-2xl p-4 bg-amber-50 border border-amber-200 mb-6">
        <div class="flex items-start gap-3">
            <span class="material-icons-round text-amber-600 text-lg">warning</span>
            <div>
                <p class="text-xs font-bold text-amber-800 uppercase tracking-wide">SYSTEM UPDATE</p>
                <p class="text-xs font-semibold text-amber-900 mt-0.5">{{ $highAlert->title }}</p>
                <p class="text-[10px] text-amber-600 mt-1">{{ $highAlert->created_at->diffForHumans() }}</p>
            </div>
            <a href="{{ route((request()->routeIs('staff.*') ? 'staff' : 'admin').'.emergency.index') }}" class="ml-auto text-amber-600 hover:text-amber-800">
                <span class="material-icons-round text-sm">open_in_new</span>
            </a>
        </div>
    </div>
    @endif

    <div class="space-y-6">

            {{-- ══════════════════════════════════════
                 RECENTLY ADDED RESIDENTS SECTION (Inspired by Reference Design)
            ══════════════════════════════════════ --}}
            <div class="dashboard-panel bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="dashboard-table-header">
                    <div class="flex items-center gap-2">
                        <span class="material-icons-round text-emerald-200 text-lg">how_to_reg</span>
                        <span class="text-sm font-bold tracking-tight">Recently Added Residents</span>
                    </div>
                    <a href="{{ route(\App\Support\RecordPortal::routeName('residents.index')) }}" class="text-xs text-emerald-100 hover:text-white font-semibold flex items-center gap-1 transition-colors">
                        <span>View all residents</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
                <div class="table-wrap overflow-x-auto">
                    <table class="data-table w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3 px-4">Resident ID</th>
                                <th class="py-3 px-4">Full Name</th>
                                <th class="py-3 px-4">Age / Sex</th>
                                <th class="py-3 px-4">Address / Purok</th>
                                <th class="py-3 px-4">Date Added</th>
                                <th class="py-3 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            @forelse($recentResidents as $resident)
                                <tr class="hover:bg-emerald-50/30 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-emerald-800 whitespace-nowrap">
                                        #RES-{{ str_pad($resident->id, 4, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-800 whitespace-nowrap">
                                        {{ $resident->full_name ?: $resident->name }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                        {{ $resident->birthdate ? $resident->birthdate->age.' yrs' : '—' }} / {{ ucfirst($resident->sex ?: '—') }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 max-w-[200px] truncate" title="{{ $resident->sitio_purok ?: ($resident->address ?: 'Barangay Doyong') }}">
                                        {{ $resident->sitio_purok ?: ($resident->address ?: 'Barangay Doyong') }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 font-medium whitespace-nowrap">
                                        {{ $resident->created_at ? $resident->created_at->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <span class="ui-status">{{ ucfirst($resident->status ?: 'Active') }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">
                                        <span class="material-icons-round text-3xl text-slate-300 block mb-1">group_off</span>
                                        No resident records found. Newly added residents will appear here.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Charts Row --}}
            <div class="dashboard-charts grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Donut Chart – Incident by Type --}}
                <div class="dashboard-panel bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="font-bold text-slate-800">Records by Category</h4>
                    </div>
                    <div class="flex flex-col items-center justify-center py-2">
                        <div class="ui-chart w-full">
                            <canvas aria-label="Records by category" role="img" data-record-chart='@json($categoryChart)'>Disaster: {{ $totalIncidents }}, Emergency: {{ $totalAlerts }}, Residents: {{ $totalResidents }}</canvas>
                        </div>
                    </div>
                </div>

                {{-- Bar Chart – Monthly --}}
                <div class="dashboard-panel bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="font-bold text-slate-800">Monthly Records Trend</h4>
                        <span class="trend-period">Last 6 Months</span>
                    </div>
                    <div class="ui-chart">
                        <canvas aria-label="Monthly records for the last six months" role="img" data-record-chart='@json($monthlyChart)'>
                            @foreach($monthlyRecords ?? [] as $month) {{ $month['label'] }}: {{ $month['count'] }}; @endforeach
                        </canvas>
                    </div>
                </div>
            </div>{{-- /charts row --}}

            {{-- Recent Activity --}}
            <section class="dashboard-panel ui-activity p-6 bg-white rounded-2xl border border-slate-200 shadow-xs" aria-label="Recent activity">
                <div class="ui-activity-title flex items-center justify-between mb-4">
                    <h4 class="font-bold text-slate-800">Recent activity</h4>
                    <a class="ui-header-link" href="{{ route((request()->routeIs('staff.*') ? 'staff' : 'admin').'.incidents.index') }}">
                        View disaster records <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
                @forelse($recentIncidents ?? [] as $incident)
                    <div class="ui-activity-row">
                        <div>
                            <strong>{{ ucfirst($incident->type) }}</strong>
                            <small>{{ $incident->location }} &middot; {{ $incident->created_at->diffForHumans() }}</small>
                        </div>
                        <span class="ui-status">{{ $incident->status }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 py-6">No recent incidents. New records will appear here.</p>
                @endforelse
            </section>
    </div>{{-- /space-y-6 --}}
</div>{{-- /dashboard-content --}}
