<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /admin/incidents
     * Returns view: resources/views/admin/incidents.blade.php
     */
    public function index(Request $request)
    {
        $isArchive = $request->routeIs('admin.archives.index');
        $baseQuery = Incident::where('archived', $isArchive);
        $query = (clone $baseQuery)->with('user')->latest();

        // Filter by status tab
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by incident type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by ID or location
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name','like',"%{$search}%"));
                if (request()->routeIs('staff.*')) {
                    $q->orWhere('logbook->complainant_name', 'like', "%{$search}%")
                      ->orWhere('logbook->recorded_by', 'like', "%{$search}%");
                    if (preg_match('/^DR-(\d{4})-(\d+)$/i', $search, $match)) {
                        $q->orWhere(fn ($record) => $record->where('id', (int) $match[2])->whereYear('created_at', (int) $match[1]));
                    }
                }
            });
        }

        $incidents = $query->paginate(10)->withQueryString();

        // Counts for filter tab badges
        $totalCount    = (clone $baseQuery)->count();
        $pendingCount  = (clone $baseQuery)->where('status', 'pending')->count();
        $ongoingCount  = (clone $baseQuery)->where('status', 'ongoing')->count();
        $resolvedCount = (clone $baseQuery)->where('status', 'resolved')->count();

        return view('admin.incidents', compact(
            'isArchive',
            'incidents',
            'totalCount',
            'pendingCount',
            'ongoingCount',
            'resolvedCount'
        ));
    }

    /**
     * GET /admin/incidents/{id}
     * Show single incident detail (for a show page or modal)
     */
    public function show(Incident $incident)
    {
        $incident->load('user');
        return view('admin.incidents_show', compact('incident'));
    }

    /**
     * GET /admin/incidents/create
     * Show the incident report form
     */
    public function create(Request $request)
    {
        $recordPreview = 'DR-'.now()->format('Y').'-'.str_pad((Incident::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);
        return view('admin.incidents_form', compact('recordPreview'));
    }

    /**
     * POST /admin/incidents
     * Store a barangay logbook record.
     */
    public function store(Request $request)
    {
        return $this->saveRecord($request, new Incident());
    }

    public function edit(Request $request, Incident $incident)
    {
        $recordPreview = $incident->record_number;
        $editingIncident = $incident;
        return view('admin.incidents_form', compact('recordPreview', 'editingIncident'));
    }

    public function update(Request $request, Incident $incident)
    {
        return $this->saveRecord($request, $incident);
    }

    private function saveRecord(Request $request, Incident $incident)
    {
        // Support legacy complainant_name and purok payloads from tests / integrations
        if ($request->filled('complainant_name') && !$request->filled('complainant_first_name')) {
            $parts = preg_split('/\s+/', trim($request->complainant_name));
            $request->merge([
                'complainant_first_name' => $parts[0] ?? '',
                'complainant_middle_name' => count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : null,
                'complainant_last_name' => count($parts) > 1 ? end($parts) : ($parts[0] ?? ''),
            ]);
        }
        if ($request->filled('purok') && !$request->filled('sitio') && in_array($request->purok, array_keys(\App\Support\EmergencyRecordForm::sitios()), true)) {
            $request->merge(['sitio' => $request->purok]);
        }

        $persons = $request->input('persons');
        if (is_array($persons)) {
            foreach ($persons as &$person) {
                if (!is_array($person)) continue;
                $person = \App\Support\PersonDetails::names($person);
                foreach ($person as &$value) if (is_string($value)) $value = preg_replace('/^\s+|\s+$/u', '', $value);
                unset($value);
            }
            unset($person);
            $request->merge(['persons'=>$persons]);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type' => 'required|in:fire,accident,theft,flood,others',
            'location' => 'required|string|max:255',
            'description' => 'required|string|max:20000',
            'record_date' => 'required|date_format:Y-m-d',
            'record_time' => 'required|date_format:H:i',
            'complainant_first_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\p{L}\s\-\']+$/u'],
            'complainant_middle_name' => ['nullable', 'string', 'max:50', 'regex:/^[\p{L}\s\-\']+$/u'],
            'complainant_last_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\p{L}\s\-\']+$/u'],
            'complainant_name' => 'nullable|string|max:255',
            'complainant_age' => 'nullable|integer|min:0|max:120',
            'sitio' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Support\EmergencyRecordForm::sitios()))],
            'purok' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'other_address' => 'nullable|string|max:255',
            'persons' => 'nullable|array|max:50',
            'persons.*' => 'array:name,first_name,middle_name,last_name,age,address',
            ...\App\Support\PersonDetails::rules('persons.*'),
            'persons.*.name' => 'nullable|string|max:255',
            'incident_date' => 'required|date_format:Y-m-d',
            'incident_time' => 'required|date_format:H:i',
            'action_taken' => 'nullable|string|max:20000',
            'meeting_date' => 'nullable|date_format:Y-m-d|required_with:meeting_time',
            'meeting_time' => 'nullable|date_format:H:i|required_with:meeting_date',
            'remarks' => 'nullable|string|max:20000',
            'officer' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ], [
            'complainant_first_name.required' => 'First name is required.',
            'complainant_first_name.min' => 'First name must contain at least 2 characters.',
            'complainant_first_name.max' => 'First name cannot exceed 50 characters.',
            'complainant_first_name.regex' => 'Numbers and inappropriate special characters are not allowed in the first name.',
            'complainant_middle_name.max' => 'Middle name cannot exceed 50 characters.',
            'complainant_middle_name.regex' => 'Numbers and inappropriate special characters are not allowed in the middle name.',
            'complainant_last_name.required' => 'Last name is required.',
            'complainant_last_name.min' => 'Last name must contain at least 2 characters.',
            'complainant_last_name.max' => 'Last name cannot exceed 50 characters.',
            'complainant_last_name.regex' => 'Numbers and inappropriate special characters are not allowed in the last name.',
            'sitio.in' => 'Please select a valid sitio.',
            'complainant_age.integer' => 'Please enter a valid age.',
            'complainant_age.min' => 'Please enter a valid age.',
            'complainant_age.max' => 'Please enter a valid age.',
        ] + \App\Support\PersonDetails::messages('persons.*'));

        $validator->after(function ($validator) use ($request) {
            if ($request->has('complainant_name') && trim((string) $request->complainant_name) === '') {
                $validator->errors()->add('complainant_name', 'Complainant name is required.');
            }
            if ($request->filled('incident_date') && $request->filled('incident_time')) {
                try {
                    $dt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->incident_date.' '.$request->incident_time);
                    if ($dt && $dt->isFuture()) {
                        $validator->errors()->add('incident_time', 'Incident date and time cannot be in the future.');
                    }
                } catch (\Exception $e) {}
            }
        });

        $validated = $validator->validate();

        $first = $validated['complainant_first_name'];
        $middle = $validated['complainant_middle_name'] ?? '';
        $last = $validated['complainant_last_name'];
        $fullName = trim("{$first} " . ($middle ? "{$middle} " : "") . $last);
        $sitio = $validated['sitio'] ?? ($validated['purok'] ?? '');

        $logbook = \Illuminate\Support\Arr::except($validated, ['type', 'location', 'description']);
        $logbook['complainant_first_name'] = $first;
        $logbook['complainant_middle_name'] = $middle ?: null;
        $logbook['complainant_last_name'] = $last;
        $logbook['complainant_name'] = $fullName;
        $logbook['sitio'] = $sitio ?: null;
        $logbook['purok'] = $sitio ?: ($validated['purok'] ?? null);
        $logbook['barangay'] = 'Doyong';
        $logbook['other_address'] = $validated['other_address'] ?? null;
        $logbook['complainant_age'] = $validated['complainant_age'] ?? null;
        $logbook['persons'] = array_values(array_filter($logbook['persons'] ?? [], fn ($person) => collect($person)->contains(fn ($value) => $value !== null && $value !== '')));
        foreach ($logbook['persons'] as &$person) $person['name'] = \App\Support\PersonDetails::fullName($person);
        unset($person);
        $logbook['recorded_by'] = $incident->logbook['recorded_by'] ?? $request->user()->name;

        $editing = $incident->exists;
        $incident->fill([
            'user_id' => $editing ? $incident->user_id : auth()->id(),
            'type' => $request->type,
            'location' => $request->location,
            'description' => $request->description,
            'logbook' => $logbook,
            'status' => $editing ? $incident->status : 'pending',
        ])->save();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $editing ? 'Disaster record updated' : 'New incident report submitted',
            'description' => ucfirst($incident->type) . ' at ' . $incident->location,
            'action_type' => 'update',
            'model_type' => 'Incident',
            'model_id' => $incident->id,
        ]);

        return redirect()->route(\App\Support\RecordPortal::routeName('incidents.index'))
                         ->with('success', $incident->record_number.' saved successfully.');
    }

    /**
     * PATCH /admin/incidents/{id}/status
     * Update status via AJAX (called by incidents.js)
     */
    public function updateStatus(Request $request, Incident $incident)
    {
        $request->validate([
            'status' => 'required|in:pending,ongoing,resolved',
        ]);

        $oldStatus = $incident->status;
        $incident->update(['status' => $request->status]);

        // Log it
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => "Incident #{$incident->id} status changed to {$request->status}",
            'description' => "Changed from {$oldStatus} to {$request->status}",
            'action_type' => $request->status === 'resolved' ? 'resolved' : 'update',
            'model_type'  => 'Incident',
            'model_id'    => $incident->id,
        ]);

        // Return JSON for AJAX, redirect for non-AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status'  => $incident->status,
                'message' => 'Status updated successfully.',
            ]);
        }

        return back()->with('success', 'Status updated to ' . $request->status . '.');
    }

    /**
     * PATCH /admin/incidents/{id}/archive
     * Archive a resolved incident
     */
    public function archive(Incident $incident)
    {
        if ($incident->status !== 'resolved') {
            return back()->with('error', 'Only resolved incidents can be archived.');
        }

        $incident->update(['archived' => true]);

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => "Incident #{$incident->id} archived",
            'description' => 'Incident moved to archive',
            'action_type' => 'update',
            'model_type'  => 'Incident',
            'model_id'    => $incident->id,
        ]);

        return back()->with('success', 'Incident archived successfully.');
    }

    /**
     * GET /admin/dashboard/stats  (JSON endpoint for JS auto-refresh)
     */
    public function stats()
    {
        return response()->json([
            'totalIncidents' => Incident::count(),
            'pendingCount'   => Incident::where('status', 'pending')->count(),
            'ongoingCount'   => Incident::where('status', 'ongoing')->count(),
            'resolvedCount'  => Incident::where('status', 'resolved')->count(),
        ]);
    }
}
