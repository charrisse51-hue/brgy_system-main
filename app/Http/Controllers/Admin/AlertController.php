<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request,$next) {
            abort_unless(in_array($request->user()?->role,['admin','staff'],true),403);
            return $next($request);
        });
    }

    /**
     * GET /admin/emergency
     * View: resources/views/admin/emergency.blade.php
     */
    public function index()
    {
        $query = Alert::with('creator')->latest();
        if (request()->filled('search')) {
            $search = request()->string('search')->toString();
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('reporter_name', 'like', "%{$search}%")
                    ->orWhere('actions_taken', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                if (request()->routeIs('staff.*')) {
                    $query->orWhere('report_source', 'like', "%{$search}%")
                        ->orWhere('emergency_type', 'like', "%{$search}%");
                    if (preg_match('/^#?EMG-(\d+)$/i', $search, $match)) {
                        $query->orWhere('id', (int) $match[1]);
                    }
                }
            });
        }
        $alerts = $query->paginate(10)->withQueryString();

        $activeAlertsCount = Alert::where('is_active', true)->count();
        $highAlertCount    = Alert::where('level', 'high')->count();
        $liveSensors       = 24; // static or from config

        return view('admin.emergency', compact(
            'alerts',
            'activeAlertsCount',
            'highAlertCount',
            'liveSensors'
        ));
    }

    /**
     * PUT /admin/emergency/{id}
     * Update an existing alert (from Edit modal)
     */
    public function update(Request $request, Alert $emergency)
    {
        abort_if($emergency->is_record,403);
        $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'level'   => 'required|in:low,medium,high',
        ]);

        $emergency->update([
            'title'   => $request->title,
            'message' => $request->message,
            'level'   => $request->level,
        ]);

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => "Alert #{$emergency->id} updated",
            'description' => "Updated: \"{$emergency->title}\"",
            'action_type' => 'update',
            'model_type'  => 'Alert',
            'model_id'    => $emergency->id,
        ]);

        return redirect()
            ->route('admin.emergency.index')
            ->with('success', 'Alert updated successfully.');
    }

    /**
     * PATCH /admin/emergency/{id}/toggle
     * Toggle is_active flag
     */
    public function toggle(Alert $emergency)
    {
        abort_if($emergency->is_record,403);
        $emergency->update(['is_active' => !$emergency->is_active]);

        $status = $emergency->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Alert \"{$emergency->title}\" has been {$status}.");
    }

    /**
     * DELETE /admin/emergency/{id}
     * Delete an alert permanently
     */
    public function destroy(Alert $emergency)
    {
        $title = $emergency->title;
        $emergency->delete();

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => "Alert deleted",
            'description' => "Alert \"{$title}\" was permanently deleted.",
            'action_type' => 'update',
            'model_type'  => 'Alert',
            'model_id'    => null,
        ]);

        return back()->with('success', "Alert \"{$title}\" deleted successfully.");
    }
}
