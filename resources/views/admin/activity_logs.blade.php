<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Activity Logs – Barangay Doyong EDRMS</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/incidents.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-records-theme.css') }}"/>
    <style>
        .activity-log-footer { flex-wrap: wrap; gap: 12px; }
        .activity-log-controls { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
        .activity-log-page-size { display: flex; align-items: center; gap: 8px; font-size: 12px; }
        .activity-log-page-size select { padding: 6px 8px; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); background: white; color: inherit; }
        .activity-log-controls .inc-pagination { flex-wrap: wrap; }
        .activity-log-controls .pg-btn:focus-visible { outline: 2px solid #237b31; outline-offset: 2px; }
    </style>
    @include('components.ui-assets')
</head>
<body class="admin-theme">
<div class="app-shell">
    @include('admin.partials.sidebar')
    <main class="main-area">
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <h2>Activity Logs</h2>
                <p>Review recorded actions, with the most recent first</p>
            </div>
        </header>
        <div class="page-content">
            <div class="inc-table-card">
                <div class="table-wrap">
                    <table class="data-table" data-ui-search-table>
                        <thead>
                            <tr>
                                <th>Date &amp; Time (Asia/Manila)</th>
                                <th>Performed By</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Record</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}</td>
                                    <td>{{ $log->user->name ?? 'Unknown user' }}</td>
                                    <td>{{ $log->action }}</td>
                                    <td>{{ $log->description ?: '—' }}</td>
                                    <td>
                                        @if($log->model_type)
                                            {{ class_basename($log->model_type) }}{{ $log->model_id ? ' #' . $log->model_id : '' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="inc-empty-state">
                                            <h3>No activity logs yet</h3>
                                            <p>Recorded actions will appear here as users manage records.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="inc-table-footer activity-log-footer">
                    <p class="inc-count-text">
                        Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} actions
                    </p>
                    <div class="activity-log-controls">
                    <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="activity-log-page-size">
                        <label for="activity-log-page-size">Rows per page</label>
                        <select id="activity-log-page-size" name="per_page" onchange="this.form.submit()">
                            @foreach([5, 10, 20, 50] as $size)
                                <option value="{{ $size }}" @selected($logs->perPage() === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="pg-btn">Apply</button></noscript>
                    </form>
                    <nav class="inc-pagination" aria-label="Activity log pages">
                        @if($logs->previousPageUrl())
                            <a class="pg-btn" href="{{ $logs->previousPageUrl() }}" rel="prev">Previous</a>
                        @else
                            <button type="button" class="pg-btn" disabled>Previous</button>
                        @endif
                        @foreach(\Illuminate\Pagination\UrlWindow::make($logs) as $pages)
                            @if(is_array($pages))
                                @foreach($pages as $page => $url)
                                    @if($page === $logs->currentPage())
                                        <span class="pg-btn pg-btn--active" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                                    @else
                                        <a class="pg-btn" href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                                    @endif
                                @endforeach
                            @else
                                <span aria-hidden="true">&hellip;</span>
                            @endif
                        @endforeach
                        @if($logs->hasMorePages())
                            <a class="pg-btn" href="{{ $logs->nextPageUrl() }}" rel="next">Next</a>
                        @else
                            <button type="button" class="pg-btn" disabled>Next</button>
                        @endif
                    </nav>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
