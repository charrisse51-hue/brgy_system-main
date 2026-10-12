<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Resident, Incident, Alert};
use Illuminate\Http\Request;
use Carbon\Carbon;

class SummaryReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'staff'], true), 403);
        abort_if($request->user()->role === 'staff' && !$request->user()->can_generate_reports, 403);
        $reportAudience = $request->routeIs('staff.*') ? 'Staff' : 'Administrative';

        $filters = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'category' => 'nullable|in:all,residents,disasters,emergencies',
            'format' => 'nullable|in:html,csv',
            'report_type' => 'nullable|in:administrative,summary,monthly_disaster,monthly_emergency,monthly_incident',
            'paper_size' => 'nullable|in:short,a4,legal',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
            'prepared_by' => 'nullable|string|max:150',
            'prepared_title' => 'nullable|string|max:150',
            'reviewed_by' => 'nullable|string|max:150',
            'reviewed_title' => 'nullable|string|max:150',
            'approved_by' => 'nullable|string|max:150',
            'approved_title' => 'nullable|string|max:150',
        ]);

        $reportType = $filters['report_type'] ?? 'administrative';
        if ($reportType === 'summary') {
            $reportType = 'administrative';
        }
        $paperSize = $filters['paper_size'] ?? 'a4';

        $nowManila = now('Asia/Manila');
        $selectedYear = (int) ($filters['year'] ?? $nowManila->year);
        $selectedMonth = (int) ($filters['month'] ?? $nowManila->month);

        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';
        $category = $filters['category'] ?? 'all';
        $generatedAt = $nowManila;

        // Signatories configuration
        $signatories = [
            'prepared_by' => $request->input('prepared_by', ''),
            'prepared_title' => $request->input('prepared_title', 'Barangay Secretary'),
            'reviewed_by' => $request->input('reviewed_by', ''),
            'reviewed_title' => $request->input('reviewed_title', 'Authorized Reviewing Officer'),
            'approved_by' => $request->input('approved_by', ''),
            'approved_title' => $request->input('approved_title', 'Punong Barangay'),
        ];

        // Month metadata
        $monthCarbon = Carbon::createFromDate($selectedYear, $selectedMonth, 1, 'Asia/Manila');
        $monthName = $monthCarbon->format('F');
        $daysInMonth = $monthCarbon->daysInMonth;
        $startOfMonth = $monthCarbon->copy()->startOfMonth();
        $endOfMonth = $monthCarbon->copy()->endOfMonth();

        // ══════════════════════════════════════════════════════
        // REPORT TYPE 1: ADMINISTRATIVE SUMMARY / CATEGORY DETAILED RECORDS DATA
        // ══════════════════════════════════════════════════════
        $sections = [];
        $definitions = [
            'residents' => [
                Resident::class,
                'Resident Records',
                'SECTION A: RESIDENT RECORDS SUMMARY',
                ['zone' => 'Sitio / Zone Breakdown', 'civil_status' => 'Civil Status Breakdown']
            ],
            'disasters' => [
                Incident::class,
                'Disaster Records',
                'SECTION B: DISASTER RECORDS SUMMARY',
                ['type' => 'Disaster Type Breakdown', 'status' => 'Status Breakdown', 'archived' => 'Archive State Breakdown']
            ],
            'emergencies' => [
                Alert::class,
                'Emergency Records',
                'SECTION C: EMERGENCY RECORDS SUMMARY',
                ['level' => 'Emergency Level Breakdown', 'is_active' => 'Status Breakdown']
            ],
        ];

        foreach ($definitions as $key => [$model, $title, $sectionHeading, $groups]) {
            if ($category !== 'all' && $category !== $key) continue;
            $query = $model::query();
            if ($from) $query->where('created_at', '>=', Carbon::parse($from, 'Asia/Manila')->startOfDay());
            if ($to) $query->where('created_at', '<', Carbon::parse($to, 'Asia/Manila')->addDay()->startOfDay());
            if (!$from && !$to) {
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            }
            $breakdowns = [];
            foreach ($groups as $column => $label) {
                $breakdowns[$label] = (clone $query)->select($column)->selectRaw('COUNT(*) as aggregate')->groupBy($column)->orderBy($column)->get()->map(function ($row) use ($column) {
                    $value = $row->getRawOriginal($column);
                    $name = match ($column) {
                        'archived' => $value ? 'Archived' : 'Not archived',
                        'is_active' => $value ? 'Active' : 'Inactive',
                        default => ($value === null || $value === '') ? 'Not specified' : ucfirst($value),
                    };
                    return ['label' => $name, 'count' => (int) $row->aggregate];
                })->all();
            }
            $sections[] = [
                'title' => $title,
                'heading' => $sectionHeading,
                'total' => (clone $query)->count(),
                'breakdowns' => $breakdowns
            ];
        }

        // Detailed records collections when specific category is selected
        $detailedResidents = collect();
        $detailedDisasters = collect();
        $detailedEmergencies = collect();

        if ($category === 'residents') {
            $rQuery = Resident::query();
            if ($from) $rQuery->where('created_at', '>=', Carbon::parse($from, 'Asia/Manila')->startOfDay());
            if ($to) $rQuery->where('created_at', '<', Carbon::parse($to, 'Asia/Manila')->addDay()->startOfDay());
            if (!$from && !$to) {
                $rQuery->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            }
            $detailedResidents = $rQuery->orderBy('id')->get()->map(function ($res, $idx) {
                $sitio = $res->sitio_purok ?: ($res->zone ? ucfirst($res->zone) : '—');
                if ($sitio && !str_starts_with(strtolower($sitio), 'sitio') && !str_starts_with(strtolower($sitio), 'zone') && $sitio !== '—') {
                    $sitio = $sitio;
                }
                return [
                    'index' => $idx + 1,
                    'resident_id' => 'RES-' . str_pad($res->id, 4, '0', STR_PAD_LEFT),
                    'first_name' => $res->first_name ?: ($res->name ?: '—'),
                    'middle_name' => $res->middle_name ?: '—',
                    'last_name' => $res->last_name ?: '—',
                    'sitio' => $sitio,
                    'date_registered' => $res->created_at?->timezone('Asia/Manila')->format('Y-m-d') ?? '—',
                ];
            });
        } elseif ($category === 'disasters') {
            $dQuery = Incident::query();
            if ($from) $dQuery->where('created_at', '>=', Carbon::parse($from, 'Asia/Manila')->startOfDay());
            if ($to) $dQuery->where('created_at', '<', Carbon::parse($to, 'Asia/Manila')->addDay()->startOfDay());
            $allDisasterRecords = $dQuery->orderBy('id')->get();
            if (!$from && !$to) {
                $allDisasterRecords = $allDisasterRecords->filter(function ($inc) use ($selectedYear, $selectedMonth) {
                    $date = null;
                    if (!empty($inc->logbook['incident_date'])) {
                        try {
                            $date = Carbon::parse($inc->logbook['incident_date'], 'Asia/Manila');
                        } catch (\Exception $e) {}
                    }
                    if (!$date) {
                        $date = $inc->created_at?->timezone('Asia/Manila');
                    }
                    return $date && $date->year === $selectedYear && $date->month === $selectedMonth;
                })->values();
            }
            $detailedDisasters = $allDisasterRecords->map(function ($inc, $idx) {
                $occDate = null;
                if (!empty($inc->logbook['incident_date'])) {
                    try {
                        $occDate = Carbon::parse($inc->logbook['incident_date'], 'Asia/Manila');
                    } catch (\Exception $e) {}
                }
                if (!$occDate) $occDate = $inc->created_at?->timezone('Asia/Manila');
                $place = $inc->location ?: ($inc->logbook['location'] ?? ($inc->logbook['sitio'] ?? 'Barangay Doyong'));
                $severity = $inc->logbook['severity'] ?? ($inc->logbook['emergency_level'] ?? 'Normal');
                $status = ucfirst($inc->status ?? 'Recorded');
                if ($inc->archived) $status .= ' [Archived]';

                return [
                    'index' => $idx + 1,
                    'disaster_id' => $inc->record_number,
                    'type' => ucfirst($inc->type ?: ($inc->logbook['type'] ?? 'Disaster Incident')),
                    'date' => $occDate ? $occDate->format('Y-m-d') : '—',
                    'location' => $place,
                    'severity' => ucfirst($severity),
                    'status' => $status,
                ];
            });
        } elseif ($category === 'emergencies') {
            $eQuery = Alert::query();
            if ($from) $eQuery->where('created_at', '>=', Carbon::parse($from, 'Asia/Manila')->startOfDay());
            if ($to) $eQuery->where('created_at', '<', Carbon::parse($to, 'Asia/Manila')->addDay()->startOfDay());
            $allEmergencyRecords = $eQuery->orderBy('id')->get();
            if (!$from && !$to) {
                $allEmergencyRecords = $allEmergencyRecords->filter(function ($alt) use ($selectedYear, $selectedMonth) {
                    $date = null;
                    if ($alt->incident_date) {
                        try {
                            $date = Carbon::parse($alt->incident_date, 'Asia/Manila');
                        } catch (\Exception $e) {}
                    }
                    if (!$date) {
                        $date = $alt->created_at?->timezone('Asia/Manila');
                    }
                    return $date && $date->year === $selectedYear && $date->month === $selectedMonth;
                })->values();
            }
            $detailedEmergencies = $allEmergencyRecords->map(function ($alt, $idx) {
                $occDate = null;
                if ($alt->incident_date) {
                    try {
                        $occDate = Carbon::parse($alt->incident_date, 'Asia/Manila');
                    } catch (\Exception $e) {}
                }
                if (!$occDate) $occDate = $alt->created_at?->timezone('Asia/Manila');
                $place = $alt->location ?: ($alt->reporter_sitio ?: 'Barangay Doyong');
                $type = $alt->emergency_type ?: ($alt->level ? ucfirst($alt->level) . ' Alert' : 'Emergency Incident');
                $status = ucfirst($alt->verification_status ?: ($alt->is_active ? 'Active' : 'Resolved'));

                return [
                    'index' => $idx + 1,
                    'emergency_id' => 'EMG-' . str_pad($alt->id, 4, '0', STR_PAD_LEFT),
                    'type' => ucfirst($type),
                    'date' => $occDate ? $occDate->format('Y-m-d') : '—',
                    'location' => $place,
                    'level' => ucfirst($alt->level ?: 'Normal'),
                    'status' => $status,
                ];
            });
        }

        // ══════════════════════════════════════════════════════
        // MONTHLY INCIDENT / DISASTER / EMERGENCY DATA
        // ══════════════════════════════════════════════════════
        // 1. Disasters documented for the selected month
        $allDisasters = Incident::query()->get()->filter(function ($inc) use ($selectedYear, $selectedMonth) {
            $date = null;
            if (!empty($inc->logbook['incident_date'])) {
                try {
                    $date = Carbon::parse($inc->logbook['incident_date'], 'Asia/Manila');
                } catch (\Exception $e) {}
            }
            if (!$date) {
                $date = $inc->created_at?->timezone('Asia/Manila');
            }
            return $date && $date->year === $selectedYear && $date->month === $selectedMonth;
        });

        $disasterEntries = $allDisasters->map(function ($inc) {
            $occDate = null;
            if (!empty($inc->logbook['incident_date'])) {
                try {
                    $occDate = Carbon::parse($inc->logbook['incident_date'], 'Asia/Manila');
                } catch (\Exception $e) {}
            }
            if (!$occDate) $occDate = $inc->created_at?->timezone('Asia/Manila') ?? now('Asia/Manila');

            $time = $inc->logbook['incident_time'] ?? '';
            $place = $inc->location ?: ($inc->logbook['location'] ?? ($inc->logbook['sitio'] ?? 'Barangay Doyong'));
            $type = $inc->type ?: ($inc->logbook['type'] ?? 'Disaster Incident');
            $desc = $inc->description ?: ($inc->details ?: ($inc->logbook['narrative'] ?? ''));
            $details = trim(ucfirst($type) . ($desc ? ' — ' . $desc : ''));
            if (!empty($inc->logbook['affected_count'])) {
                $details .= " (Affected: {$inc->logbook['affected_count']})";
            }
            if (!empty($inc->logbook['rescued_count'])) {
                $details .= " (Rescued: {$inc->logbook['rescued_count']})";
            }

            $status = ucfirst($inc->status ?? 'Recorded');
            if ($inc->archived) {
                $status .= ' [Archived]';
            }

            return [
                'id' => $inc->id,
                'record_number' => $inc->record_number,
                'kind' => 'Disaster',
                'day' => (int) $occDate->day,
                'date' => $occDate->format('Y-m-d'),
                'time' => $time,
                'place' => $place,
                'type' => $type,
                'details' => $details,
                'status' => $status,
                'sort_key' => $occDate->format('Y-m-d') . ' ' . ($time ?: '00:00') . ' D' . $inc->id,
            ];
        });

        // 2. Emergencies recorded for the selected month
        $allEmergencies = Alert::query()->get()->filter(function ($alert) use ($selectedYear, $selectedMonth) {
            $date = null;
            if ($alert->incident_date) {
                try {
                    $date = Carbon::parse($alert->incident_date, 'Asia/Manila');
                } catch (\Exception $e) {}
            }
            if (!$date) {
                $date = $alert->created_at?->timezone('Asia/Manila');
            }
            return $date && $date->year === $selectedYear && $date->month === $selectedMonth;
        });

        $emergencyEntries = $allEmergencies->map(function ($alert) {
            $occDate = null;
            if ($alert->incident_date) {
                try {
                    $occDate = Carbon::parse($alert->incident_date, 'Asia/Manila');
                } catch (\Exception $e) {}
            }
            if (!$occDate) $occDate = $alert->created_at?->timezone('Asia/Manila') ?? now('Asia/Manila');

            $time = $alert->incident_time ?? '';
            $place = $alert->location ?: ($alert->reporter_sitio ?: 'Barangay Doyong');
            $type = $alert->emergency_type ?: ($alert->level ? ucfirst($alert->level) . ' Alert' : 'Emergency Incident');
            $desc = $alert->title ?: ($alert->message ?: '');
            if ($alert->title && $alert->message && $alert->title !== $alert->message) {
                $desc = $alert->title . ' — ' . $alert->message;
            }
            $details = trim(ucfirst($type) . ($desc ? ' — ' . $desc : ''));
            $status = ucfirst($alert->verification_status ?: ($alert->is_active ? 'Active' : 'Resolved'));

            return [
                'id' => $alert->id,
                'record_number' => 'EM-' . $alert->id,
                'kind' => 'Emergency',
                'day' => (int) $occDate->day,
                'date' => $occDate->format('Y-m-d'),
                'time' => $time,
                'place' => $place,
                'type' => $type,
                'details' => $details,
                'status' => $status,
                'sort_key' => $occDate->format('Y-m-d') . ' ' . ($time ?: '00:00') . ' E' . $alert->id,
            ];
        });

        // Resolve active monthly collection and report title
        if ($reportType === 'monthly_disaster') {
            $reportTitle = 'BARANGAY DISASTER RECORDS MONTHLY REPORT';
            $monthlyEntries = $disasterEntries->sortBy('sort_key')->values();
        } elseif ($reportType === 'monthly_emergency') {
            $reportTitle = 'BARANGAY EMERGENCY RECORDS MONTHLY REPORT';
            $monthlyEntries = $emergencyEntries->sortBy('sort_key')->values();
        } elseif ($reportType === 'monthly_incident') {
            $reportTitle = 'BARANGAY EMERGENCY AND DISASTER INCIDENT REPORT';
            $monthlyEntries = $disasterEntries->concat($emergencyEntries)->sortBy('sort_key')->values();
        } else {
            if ($category === 'residents') {
                $reportTitle = 'RESIDENT RECORDS REPORT';
            } elseif ($category === 'disasters') {
                $reportTitle = 'DISASTER RECORDS REPORT';
            } elseif ($category === 'emergencies') {
                $reportTitle = 'EMERGENCY RECORDS REPORT';
            } else {
                $reportTitle = 'ADMINISTRATIVE SUMMARY REPORT';
            }
            $monthlyEntries = collect();
        }

        // Group calendar days (1 to daysInMonth)
        $calendarDays = [];
        $groupedByDay = $monthlyEntries->groupBy('day');
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $calendarDays[$d] = $groupedByDay->get($d, collect());
        }

        // ══════════════════════════════════════════════════════
        // CSV EXPORT HANDLING
        // ══════════════════════════════════════════════════════
        if (($filters['format'] ?? '') === 'csv') {
            return response()->streamDownload(function () use ($sections, $from, $to, $category, $generatedAt, $reportAudience, $reportType, $reportTitle, $monthName, $selectedYear, $daysInMonth, $monthlyEntries, $detailedResidents, $detailedDisasters, $detailedEmergencies) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                $write = function (array $row) use ($file) {
                    $row = array_map(fn ($cell) => is_string($cell) && preg_match('/^[=+@\-\t\r\n]/', $cell) ? "'".$cell : $cell, $row);
                    fputcsv($file, $row, ',', '"', '');
                };

                if ($reportType === 'administrative') {
                    $write(['REPUBLIC OF THE PHILIPPINES', 'MUNICIPALITY OF CALASIAO', 'BARANGAY DOYONG']);
                    $write([$reportTitle]);
                    $write(['FOR THE MONTH OF '.strtoupper($monthName).', '.$selectedYear]);
                    $write(['Barangay Doyong - '.$reportAudience.' Summary']);
                    $write(['Generated at', $generatedAt->toDateTimeString()]);
                    $write(['Reporting Period', $monthName.' 1–'.$daysInMonth.', '.$selectedYear]);
                    $write(['Record Scope', ($category === 'all' ? 'All records' : ucfirst($category).' Records')]);
                    $write(['Category', 'Grouping', 'Value', 'Count']);
                    foreach ($sections as $section) {
                        $write([$section['title'], 'Total', 'All records', $section['total']]);
                        foreach ($section['breakdowns'] as $group => $rows) {
                            foreach ($rows as $row) $write([$section['title'], $group, $row['label'], $row['count']]);
                        }
                    }

                    if ($category === 'residents') {
                        $write([]);
                        $write(['RESIDENT RECORDS DETAILED LIST']);
                        $write(['No.', 'Resident ID', 'First Name', 'Middle Name', 'Last Name', 'Sitio', 'Date Registered']);
                        foreach ($detailedResidents as $r) {
                            $write([$r['index'], $r['resident_id'], $r['first_name'], $r['middle_name'], $r['last_name'], $r['sitio'], $r['date_registered']]);
                        }
                    } elseif ($category === 'disasters') {
                        $write([]);
                        $write(['DISASTER RECORDS DETAILED LIST']);
                        $write(['No.', 'Disaster ID', 'Disaster Type', 'Date of Occurrence', 'Location / Affected Area', 'Severity Level', 'Status']);
                        foreach ($detailedDisasters as $d) {
                            $write([$d['index'], $d['disaster_id'], $d['type'], $d['date'], $d['location'], $d['severity'], $d['status']]);
                        }
                    } elseif ($category === 'emergencies') {
                        $write([]);
                        $write(['EMERGENCY RECORDS DETAILED LIST']);
                        $write(['No.', 'Emergency ID', 'Emergency Type', 'Date of Occurrence', 'Location', 'Emergency Level', 'Status']);
                        foreach ($detailedEmergencies as $e) {
                            $write([$e['index'], $e['emergency_id'], $e['type'], $e['date'], $e['location'], $e['level'], $e['status']]);
                        }
                    }
                } else {
                    $write(['REPUBLIC OF THE PHILIPPINES', 'MUNICIPALITY OF CALASIAO', 'BARANGAY DOYONG']);
                    $write([$reportTitle]);
                    $write(['FOR THE MONTH OF '.strtoupper($monthName).', '.$selectedYear]);
                    $write(['Generated at', $generatedAt->format('Y-m-d H:i:s T')]);
                    $write([]);
                    $write(['Day', 'Date', 'Type', 'Place / Location', 'Incident / Disaster Details', 'Remarks / Status']);
                    if ($monthlyEntries->isEmpty()) {
                        $write(['No recorded incidents for the selected period.']);
                    } else {
                        foreach ($monthlyEntries as $entry) {
                            $write([
                                $entry['day'],
                                $entry['date'],
                                $entry['type'],
                                $entry['place'],
                                $entry['details'],
                                $entry['status'],
                            ]);
                        }
                    }
                }
                fclose($file);
            }, strtolower($reportAudience).'-'.strtolower(str_replace([' ', '_'], '-', $reportType)).'-'.$generatedAt->format('Y-m-d-His').'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store'
            ]);
        }

        return response()->view('admin.summary_reports', compact(
            'sections',
            'from',
            'to',
            'category',
            'generatedAt',
            'reportAudience',
            'reportType',
            'selectedMonth',
            'selectedYear',
            'monthName',
            'daysInMonth',
            'reportTitle',
            'monthlyEntries',
            'calendarDays',
            'signatories',
            'paperSize',
            'detailedResidents',
            'detailedDisasters',
            'detailedEmergencies'
        ))->header('Cache-Control', 'no-store');
    }
}
