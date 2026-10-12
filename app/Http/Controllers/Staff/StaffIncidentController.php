<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;

class StaffIncidentController extends Controller
{
    /**
     * Display a listing of incidents.
     */
    public function index()
    {
        $totalIncidents = Incident::count();
        $pendingCount   = Incident::where('status', 'pending')->count();
        $ongoingCount   = Incident::where('status', 'ongoing')->count();
        $resolvedCount  = Incident::where('status', 'resolved')->count();

        $incidents = Incident::with('user')
            ->orderByDesc('reported_at')
            ->paginate(12);

        return view('staff.staffincident', compact(
            'totalIncidents',
            'pendingCount',
            'ongoingCount',
            'resolvedCount',
            'incidents'
        ));
    }

    /**
     * Return incident detail data (JSON) for modal.
     */
    public function show(Incident $incident)
    {
        return response()->json([
            'id'           => $incident->id,
            'type'         => $incident->type,
            'reporter'     => optional($incident->user)->name,
            'location'     => $incident->location,
            'description'  => $incident->description,
            'details'      => $incident->details,
            'status'       => $incident->status,
            'reported_at'  => optional($incident->reported_at)->format('F j, Y g:i A'),
            'created_at'   => optional($incident->created_at)->toDateTimeString(),
            'updated_at'   => optional($incident->updated_at)->toDateTimeString(),
        ]);
    }

    /**
     * Update incident status.
     */
    public function updateStatus(Request $request, Incident $incident)
    {
        $request->validate([
            'status' => ['required', 'in:pending,ongoing,resolved'],
        ]);

        $incident->status = $request->input('status');
        $incident->save();

        return redirect()->route('staff.incidents.index')
            ->with('success', 'Incident status updated successfully.');
    }
}
