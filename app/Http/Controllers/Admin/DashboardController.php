<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Returns view: resources/views/admin/dashboard.blade.php
     */
    public function index()
    {
        // Initialize data arrays with safe defaults
        $totalIncidents = 0;
        $pendingCount   = 0;
        $ongoingCount   = 0;
        $resolvedCount  = 0;
        $totalAlerts    = 0;
        $totalResidents = 0;
        $recentIncidents = [];
        $activeAlerts    = collect();

        // Safely attempt to load data from models if they exist
        try {
            $incidentClass = 'App\Models\Incident';
            if (class_exists($incidentClass)) {
                // @phpstan-ignore-next-line
                $totalIncidents = $incidentClass::count();
                // @phpstan-ignore-next-line
                $pendingCount   = $incidentClass::where('status', 'pending')->count();
                // @phpstan-ignore-next-line
                $ongoingCount   = $incidentClass::where('status', 'ongoing')->count();
                // @phpstan-ignore-next-line
                $resolvedCount  = $incidentClass::where('status', 'resolved')->count();
                // @phpstan-ignore-next-line
                $recentIncidents = $incidentClass::with('user')->latest()->take(5)->get();
            }
        } catch (\Exception $e) {
            // Models not yet created, continue with defaults
        }

        try {
            $alertClass = 'App\Models\Alert';
            if (class_exists($alertClass)) {
                // @phpstan-ignore-next-line
                $totalAlerts = $alertClass::count();
                // @phpstan-ignore-next-line
                $activeAlerts = $alertClass::where('is_active', true)->latest()->take(10)->get();
            }
        } catch (\Exception $e) {
            // Model not yet created, continue with defaults
        }

        try {
            $residentClass = 'App\Models\Resident';
            if (class_exists($residentClass)) {
                // @phpstan-ignore-next-line
                $totalResidents = $residentClass::count();
            }
        } catch (\Exception $e) {
            // Model not yet created, continue with defaults
        }

        $totalRecords = $totalResidents + $totalIncidents + $totalAlerts;

        $monthlyRecords = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $start = now()->startOfMonth()->subMonths($offset);
            $end = $start->copy()->addMonth();
            $count = 0;
            foreach ([\App\Models\Incident::class, \App\Models\Alert::class, \App\Models\Resident::class] as $model) {
                try {
                    $count += $model::where('created_at', '>=', $start)->where('created_at', '<', $end)->count();
                } catch (\Exception $e) {
                    // Match the dashboard's defaults when a model is unavailable.
                }
            }
            $monthlyRecords[] = ['label' => $start->format('M'), 'count' => $count];
        }

        return view('admin.dashboard', compact(
            'totalIncidents', 'pendingCount', 'ongoingCount',
            'resolvedCount', 'totalAlerts', 'totalResidents', 'totalRecords',
            'recentIncidents', 'activeAlerts', 'monthlyRecords'
        ));
    }
}
