<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Requests\ResidentRecordRequest;

class ResidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /admin/residents
     * View: resources/views/admin/resident_record.blade.php
     */
    public function index(Request $request)
    {
        if ($request->boolean('add')) return redirect()->route(\App\Support\RecordPortal::routeName('residents.create'));
        if ($request->filled('edit_id')) return redirect()->route(\App\Support\RecordPortal::routeName('residents.edit'), Resident::findOrFail($request->integer('edit_id')));
        $filters = $request->validate([
            'search' => 'nullable|string|max:150',
            'sitio' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Support\EmergencyRecordForm::sitios()))],
            'zone' => 'nullable|in:zone1,zone2,zone3,zone4',
            'civil_status' => 'nullable|in:single,married,annulled,separated,divorced,widowed,live-in',
            'registered_from' => 'nullable|date_format:Y-m-d',
            'registered_to' => 'nullable|date_format:Y-m-d'.($request->filled('registered_from') ? '|after_or_equal:registered_from' : ''),
            'contact' => 'nullable|in:with,without',
            'sort' => 'nullable|in:newest,oldest,name_asc,name_desc',
            'per_page' => 'nullable|integer|in:15,25,50',
        ]);
        $query = Resident::query()->filtered($filters);
        $residents = $query->paginate($filters['per_page'] ?? 15)->withQueryString();
        $viewResident = $request->filled('view_id') ? Resident::findOrFail($request->integer('view_id')) : null;
        $editResident = $request->filled('edit_id') ? Resident::findOrFail($request->integer('edit_id')) : null;
        return view('admin.resident_record', compact(
            'residents',
            'viewResident',
            'editResident'
        ));
    }

    /**
     * GET /admin/residents/create
     * Show form to create new resident
     */
    public function create()
    {
        return view('admin.resident_form', ['mode' => 'add', 'editingResident' => null]);
    }

    /**
     * GET /admin/residents/{id}
     * Show single resident detail page
     */
    public function edit(Resident $resident)
    {
        return view('admin.resident_form', ['mode' => 'edit', 'editingResident' => $resident]);
    }

    public function show(Resident $resident)
    {
        return redirect()->route(\App\Support\RecordPortal::routeName('residents.index'), ['view_id' => $resident->id]);
    }

    /**
     * POST /admin/residents
     * Store new resident
     */
    public function store(ResidentRecordRequest $request)
    {
        $resident = Resident::create($request->residentData());

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'New Resident Registered',
            'description' => "Resident record created for {$resident->name}.",
            'action_type' => 'register',
            'model_type'  => 'Resident',
            'model_id'    => $resident->id,
        ]);

        return redirect()
            ->route(\App\Support\RecordPortal::routeName('residents.index'), ['new_id' => $resident->id])
            ->with('success', "Resident \"{$resident->name}\" added successfully.");
    }

    /**
     * PUT /admin/residents/{id}
     * Update resident record (from Edit modal)
     */
    public function update(ResidentRecordRequest $request, Resident $resident)
    {
        $resident->update($request->residentData());

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => "Resident record updated",
            'description' => "Updated record for {$resident->name}.",
            'action_type' => 'update',
            'model_type'  => 'Resident',
            'model_id'    => $resident->id,
        ]);

        return redirect()->route(\App\Support\RecordPortal::routeName('residents.index'), ['view_id' => $resident->id])
            ->with('success', "Resident \"{$resident->name}\" updated successfully.");
    }

}

