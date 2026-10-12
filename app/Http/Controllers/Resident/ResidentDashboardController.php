<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResidentDashboardController extends Controller
{
    /**
     * Barangay emergency hotlines.
     * You can move these to a config file or database table later.
     */
    private array $hotlines = [
        'barangay_hall' => '0917-123-4567',
        'mdrrmo'        => '0918-987-6543',
    ];

    /**
     * Show the resident dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        // Fetch active alerts (active_alerts = true)
        $alerts = null;
        if (class_exists(\App\Models\Alert::class)) {
            $alerts = \App\Models\Alert::where('is_active', true)->where('is_record', false)
                ->latest()
                ->take(10)
                ->get();
        }

        // Count unread notifications for the bell dot indicator.
        $unreadNotifications = 0;

        // Safe fallback: only query if notifications table exists
        try {
            if (method_exists($user, 'unreadNotifications')) {
                $unreadNotifications = $user->unreadNotifications()->count();
            }
        } catch (\Exception $e) {
            // notifications table may not exist yet
            $unreadNotifications = 0;
        }

        return view('resident.dashboard', [
            'user'                => $user,
            'hotlines'            => $this->hotlines,
            'alerts'              => $alerts,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }

    /**
     * Show community alerts page.
     */

    public function alerts(Request $request)
    {
        $user = Auth::user();

        // Fetch active alerts
        $alerts = null;
        if (class_exists(\App\Models\Alert::class)) {
            $alerts = \App\Models\Alert::where('is_active', true)->where('is_record', false)
                ->latest()
                ->paginate(20)
                ->withQueryString();
        }

        // JSON response for auto-refresh (comalerts.js)
        if ($request->expectsJson() || $request->boolean('json')) {
            return response()->json([
                'total' => $alerts ? $alerts->total() : 0,
                'html'  => view('resident.partials.alerts_feed', compact('alerts'))->render(),
            ]);
        }

        return view('resident.alerts', compact('user', 'alerts'));
    }

    /**
     * Show the resident's personal profile page.
     */
    public function profile()
    {
        $user     = Auth::user();
        $resident = $user->resident; // assumes User hasOne Resident relationship

        return view('resident.profile', compact('user', 'resident'));
    }

    /**
     * Update the resident's personal profile.
     */
    public function updateProfile(Request $request)
    {
        $user     = Auth::user();
        $resident = $user->resident;

        $validated = $request->validate([
            'first_name'   => 'nullable|string|max:255',
            'middle_name'  => 'nullable|string|max:255',
            'last_name'    => 'nullable|string|max:255',
            'birthdate'    => 'nullable|date',
            'civil_status' => 'nullable|string|max:50',
        ]);

        if ($resident) {
            $resident->update($validated);
        }

        return back()->with('success', 'Profile updated successfully.');
    }


}
