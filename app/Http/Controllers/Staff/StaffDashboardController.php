<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Alert;
use App\Models\Resident;

class StaffDashboardController extends Controller
{
    /**
     * Show the staff command-centre dashboard.
     */
    public function index()
    {
        $totalIncidents = Incident::count();

        $totalResidents = Resident::count();
        $totalAlerts = Alert::count();
        $totalRecords = $totalResidents + $totalAlerts + $totalIncidents;
        $activeAlerts = Alert::where('is_active', true)->latest()->take(10)->get();
        $monthlyRecords = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $start = now()->startOfMonth()->subMonths($offset);
            $end = $start->copy()->addMonth();
            $count = 0;
            foreach ([Incident::class, Alert::class, Resident::class] as $model) {
                $count += $model::where('created_at', '>=', $start)->where('created_at', '<', $end)->count();
            }
            $monthlyRecords[] = ['label' => $start->format('M'), 'count' => $count];
        }
        return view('staff.staffdashboard', compact(
            'totalIncidents',
            'totalResidents',
            'totalAlerts',
            'totalRecords',
            'activeAlerts',
            'monthlyRecords'
        ));
    }
}
