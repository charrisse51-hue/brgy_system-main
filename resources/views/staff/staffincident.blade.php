<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Incident Management – Barangay Doyong EDRMS</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>


    <style>
        body { font-family: 'Public Sans', sans-serif; }
        .sidebar-item-active {
            background-color: rgba(21,128,61, 0.1);
            border-right: 4px solid #15803d;
            color: #15803d !important;
        }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px; border-radius: 8px;
            font-weight: 500; transition: all 0.2s;
            color: #475569; text-decoration: none;
        }
        .nav-link:hover { background-color: #f1f5f9; color: #1e293b; }
    </style>

    <link rel="stylesheet" href="{{ asset('css/staff/incidentmanagement.css') }}"/>    @include('components.ui-assets')
</head>
<body class="bg-[#f6f7f8] font-[Public_Sans] text-slate-800">

@php
    $user = auth()->user();
@endphp

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    @include('staff.partials.sidebar')

    {{-- MAIN CONTENT --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Header --}}
        <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between z-10 flex-shrink-0">
@include('components.navigation-toggle')
            <div>
                <h2 class="text-xl font-bold text-slate-800">Incident Management</h2>
                <p class="text-xs text-slate-500">
                    Manage and monitor reported emergency incidents in Barangay Doyong.
                </p>
            </div>
        </header>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto p-8">

            {{-- STAT CARDS --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600">
                            <span class="material-icons-round">summarize</span>
                        </div>
                    </div>
                    <h3 class="text-slate-500 text-[10px] font-semibold uppercase tracking-wider">Total Incidents</h3>
                    <p class="text-3xl font-bold mt-1">{{ $totalIncidents ?? 0 }}</p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-rose-50 rounded-lg flex items-center justify-center text-rose-600">
                            <span class="material-icons-round">hourglass_empty</span>
                        </div>
                        @if(($pendingCount ?? 0) > 0)
                        <span class="text-xs font-semibold text-rose-500">Action needed</span>
                        @endif
                    </div>
                    <h3 class="text-slate-500 text-[10px] font-semibold uppercase tracking-wider">Pending Incidents</h3>
                    <p class="text-3xl font-bold mt-1 {{ ($pendingCount ?? 0) > 0 ? 'text-rose-600' : '' }}">
                        {{ str_pad($pendingCount ?? 0, 2, '0', STR_PAD_LEFT) }}
                    </p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center text-amber-600">
                            <span class="material-icons-round">sync</span>
                        </div>
                    </div>
                    <h3 class="text-slate-500 text-[10px] font-semibold uppercase tracking-wider">Ongoing Incidents</h3>
                    <p class="text-3xl font-bold mt-1">{{ str_pad($ongoingCount ?? 0, 2, '0', STR_PAD_LEFT) }}</p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600">
                            <span class="material-icons-round">task_alt</span>
                        </div>
                    </div>
                    <h3 class="text-slate-500 text-[10px] font-semibold uppercase tracking-wider">Resolved Incidents</h3>
                    <p class="text-3xl font-bold mt-1">{{ $resolvedCount ?? 0 }}</p>
                </div>

            </div>{{-- /stat cards --}}

            {{-- INCIDENT TABLE --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h4 class="font-bold text-slate-800">Reported Incidents</h4>
                    <span class="text-slate-500 text-xs font-semibold">
                        {{ $incidents->total() }} incident(s) found.
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs font-semibold border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3 uppercase tracking-wider">Incident ID</th>
                                <th class="px-6 py-3 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 uppercase tracking-wider">Reporter</th>
                                <th class="px-6 py-3 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 uppercase tracking-wider">Date Reported</th>
                                <th class="px-6 py-3 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($incidents as $incident)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-semibold text-[#15803d]">#INC-{{ str_pad($incident->id, 3, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $typeDot = [
                                            'fire'     => 'bg-rose-500',
                                            'accident' => 'bg-amber-400',
                                            'theft'    => 'bg-green-500',
                                            'flood'    => 'bg-[#15803d]',
                                            'others'   => 'bg-slate-400',
                                        ];
                                        $dot = $typeDot[$incident->type] ?? 'bg-slate-400';
                                    @endphp
                                    <span class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full {{ $dot }} flex-shrink-0"></span>
                                        {{ ucfirst($incident->type ?? 'Others') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-700">{{ optional($incident->user)->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $incident->location ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-amber-100 text-amber-700',
                                            'ongoing' => 'bg-green-100 text-green-700',
                                            'resolved'=> 'bg-emerald-100 text-emerald-700',
                                        ];
                                        $badge = $statusClasses[$incident->status] ?? 'bg-slate-100 text-slate-600';
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold {{ $badge }}">
                                        {{ ucfirst($incident->status ?? 'pending') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-700">{{ optional($incident->reported_at)->format('M d, Y') ?? optional($incident->created_at)->format('M d, Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            class="btn-view text-xs font-semibold text-primary hover:text-green-700"
                                            data-incident-id="{{ $incident->id }}"
                                            data-incident-type="{{ $incident->type }}"
                                            data-incident-reporter="{{ optional($incident->user)->name }}"
                                            data-incident-location="{{ $incident->location }}"
                                            data-incident-status="{{ $incident->status }}"
                                            data-incident-description="{{ e($incident->description) }}"
                                            data-incident-reported="{{ optional($incident->reported_at)->format('F j, Y g:i A') }}"
                                        >
                                            View
                                        </button>

                                        <button
                                            class="btn-update-status text-xs font-semibold text-slate-500 hover:text-slate-800"
                                            data-incident-id="{{ $incident->id }}"
                                            data-incident-status="{{ $incident->status }}"
                                        >
                                            Update Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                    No incidents found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-slate-100">
                    {{ $incidents->links() }}
                </div>
            </div>

        </div>{{-- /content --}}

    </main>
</div>

{{-- MODAL --}}
<div id="incidentModal" class="hidden">
    <div class="modal-backdrop"></div>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-card">
            <div class="flex items-start justify-between p-6 border-b border-slate-100">
                <div>
                    <h3 id="modalTitle" class="text-lg font-bold text-slate-800">Incident Details</h3>
                    <p id="modalSubtitle" class="text-xs text-slate-500 mt-1">Review and update the incident status.</p>
                </div>
                <button id="modalClose" class="text-slate-500 hover:text-slate-800">
                    <span class="material-icons-round">close</span>
                </button>
            </div>

            <div class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Incident Type</div>
                        <div id="modalIncidentType" class="mt-1 text-sm font-semibold text-slate-800"></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Reporter</div>
                        <div id="modalReporter" class="mt-1 text-sm text-slate-800"></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Location</div>
                        <div id="modalLocation" class="mt-1 text-sm text-slate-800"></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Reported</div>
                        <div id="modalReportedAt" class="mt-1 text-sm text-slate-800"></div>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</div>
                    <div id="modalDescription" class="mt-2 text-sm text-slate-700 bg-slate-50 rounded-lg p-4"></div>
                </div>

                <form id="modalStatusForm" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="incident_id" id="modalIncidentId">

                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider" for="modalStatus">Status</label>
                        <select id="modalStatus" name="status" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm">
                            <option value="pending">Pending</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="button" id="modalCancel" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white hover:bg-[#166534]">
                            Save Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/staff/incidentmanagement.js') }}" defer></script>

</body>
</html>
