<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        if (! in_array($perPage, [5, 10, 20, 50], true)) {
            $perPage = 20;
        }

        $logs = ActivityLog::with('user')->latest()->orderByDesc('id')
            ->paginate($perPage)->withQueryString();

        return view('admin.activity_logs', compact('logs'));
    }
}
