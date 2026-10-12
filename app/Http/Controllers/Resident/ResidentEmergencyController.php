<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ResidentEmergencyController extends Controller
{
    /**
     * Show the incident report form.
     */
    public function create()
    {
        return view('resident.emergency_report');
    }

    /**
     * Validate, store photos, and save the incident report.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'incident_type' => ['required', Rule::in(['fire', 'accident', 'theft', 'flood', 'others'])],
            'description'   => ['nullable', 'string', 'max:2000'],
            'latitude'      => ['required', 'numeric', 'between:-90,90'],
            'longitude'     => ['required', 'numeric', 'between:-180,180'],
            'photos'        => ['nullable', 'array', 'max:5'],
            'photos.*'      => ['image', 'mimes:png,jpg,jpeg', 'max:5120'], // 5 MB each
        ]);

        // ── Upload photos ──────────────────────────────────────
        $photoPaths = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                // Stored in storage/app/public/incident_photos/{year}/{month}/
                $path         = $photo->store('incident_photos/' . now()->format('Y/m'), 'public');
                $photoPaths[] = $path;
            }
        }

        // ── Save incident report ───────────────────────────────
        Incident::create([
            'user_id'       => Auth::id(),
            'type'          => $validated['incident_type'],
            'description'   => $validated['description'] ?? null,
            'latitude'      => $validated['latitude'],
            'longitude'     => $validated['longitude'],
            'photos'        => $photoPaths,
            'status'        => 'pending',
        ]);

        return redirect()
            ->route('resident.dashboard')
            ->with('success', 'Your incident report has been submitted. Our response team will act on it shortly.');
    }
}