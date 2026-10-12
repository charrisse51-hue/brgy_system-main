<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Summary Reports – Barangay Doyong</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/disaster-records.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/summary-reports.css') }}"/>
    @include('components.ui-assets')
</head>
<body class="admin-theme disaster-records summary-reports">
@php 
    $reportRoute = (request()->routeIs('staff.*') ? 'staff' : 'admin').'.summary-reports.index'; 
    $isMonthly = in_array($reportType, ['monthly_disaster', 'monthly_emergency', 'monthly_incident']);
@endphp
<div class="app-shell">
    @include('admin.partials.sidebar', ['sidebarSubtitle' => (request()->routeIs('staff.*') ? 'RECORDS STAFF' : 'RECORDS ADMIN')])
    <main class="main-area">
        <header class="topbar">
            @include('components.navigation-toggle')
            <div class="topbar-title">
                <h2>Summary Reports</h2>
                <p>{{ $reportAudience }} review of barangay records</p>
            </div>
        </header>

        <div class="page-content">

            {{-- ════════════════════════════════════════
                 SECTION 1: REPORT CONFIGURATION TOOLBAR
            ════════════════════════════════════════ --}}
            <section class="report-controls-card no-print">
                <div class="controls-card-header">
                    <div class="controls-title">
                        <span class="material-icons-round">tune</span>
                        <h3>Report Configuration</h3>
                    </div>
                    <span class="controls-hint">Select record category, month, and year</span>
                </div>

                <form class="report-filters-form" id="reportFilterForm" method="GET" action="{{ route($reportRoute) }}">
                    <div class="controls-grid">
                        {{-- Record Category --}}
                        <div class="form-group">
                            <label for="reportCategorySelect">Record Category</label>
                            <select name="category" id="reportCategorySelect" class="form-select">
                                @foreach(['all'=>'All records', 'residents'=>'Residents', 'disasters'=>'Disasters', 'emergencies'=>'Emergencies'] as $val => $lbl)
                                    <option value="{{ $val }}" @selected($category === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Month --}}
                        <div class="form-group">
                            <label for="reportMonthSelect">Month</label>
                            <select name="month" id="reportMonthSelect" class="form-select">
                                @for($m = 1; $m <= 12; $m++)
                                    @php $mDate = \Carbon\Carbon::create(2026, $m, 1); @endphp
                                    <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ $mDate->format('F') }}</option>
                                @endfor
                            </select>
                        </div>

                        {{-- Year --}}
                        <div class="form-group">
                            <label for="reportYearSelect">Year</label>
                            <select name="year" id="reportYearSelect" class="form-select">
                                @for($y = now('Asia/Manila')->year + 1; $y >= 2020; $y--)
                                    <option value="{{ $y }}" @selected($selectedYear === $y)>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="report-filter-error" role="alert">
                            <span class="material-icons-round">error_outline</span>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    {{-- Toolbar Action Buttons --}}
                    <div class="controls-actions-bar">
                        <div class="primary-actions">
                            <button class="btn btn-primary" type="submit">
                                <span class="material-icons-round">analytics</span>
                                <span>Generate Report</span>
                            </button>
                            <a class="btn btn-ghost" href="{{ route($reportRoute) }}">
                                <span class="material-icons-round">refresh</span>
                                <span>Reset Filters</span>
                            </a>
                        </div>
                        <div class="export-actions">
                            <button class="btn btn-outline" type="button" onclick="window.print()" title="Print report formatted for Short, A4, or Legal paper">
                                <span class="material-icons-round">print</span>
                                <span>Print Report</span>
                            </button>
                            <button class="btn btn-outline" type="button" onclick="window.print()" title="Save report as formal PDF">
                                <span class="material-icons-round">picture_as_pdf</span>
                                <span>Download PDF</span>
                            </button>
                            <a class="btn btn-outline btn-csv" href="{{ route($reportRoute, array_merge(request()->query(), ['format' => 'csv'])) }}" title="Download raw data in CSV">
                                <span class="material-icons-round">table_view</span>
                                <span>Download CSV</span>
                            </a>
                        </div>
                    </div>
                </form>
            </section>

            {{-- ════════════════════════════════════════
                 SECTION 2: REPORT DOCUMENT PREVIEW (WORD-STYLE MULTI-PAGE VIEW)
            ════════════════════════════════════════ --}}
            <div class="report-preview-outer" id="reportPreviewOuter">
                {{-- Raw Unpaginated Content Container (Used as source by paginator, hidden when paginated) --}}
                <div class="report-raw-source" id="reportRawSource">

                    {{-- ════ OFFICIAL DOCUMENT HEADER (IMAGE 3) ════ --}}
                    <header class="official-report-header" id="rawOfficialHeader">
                        <div class="official-logo-box official-logo-left">
                            <img src="{{ asset('images/calasiao-logo.png') }}" alt="Municipality of Calasiao Official Logo" class="official-logo-img"/>
                        </div>
                        <div class="official-header-text">
                            <p class="gov-line-1">REPUBLIC OF THE PHILIPPINES</p>
                            <p class="gov-line-2">PROVINCE OF PANGASINAN</p>
                            <p class="gov-line-3">MUNICIPALITY OF CALASIAO</p>
                            <h2 class="gov-line-4">BARANGAY DOYONG</h2>
                        </div>
                        <div class="official-logo-box official-logo-right">
                            <img src="{{ asset('images/barangay-doyong-logo.png') }}" alt="Barangay Doyong Official Logo" class="official-logo-img"/>
                        </div>
                    </header>
                    <hr class="official-header-divider" id="rawHeaderDivider"/>

                    {{-- ════ REPORT TITLE & METADATA BLOCK ════ --}}
                    <div class="formal-report-title-block" id="rawTitleBlock">
                        <h3 class="formal-doc-title">{{ $reportTitle }}</h3>
                        <h4 class="formal-doc-subtitle">FOR THE MONTH OF {{ strtoupper($monthName) }}, {{ $selectedYear }}</h4>
                        <div class="formal-meta-strip">
                            <span><strong>Reporting Period:</strong> {{ $monthName }} 1–{{ $daysInMonth }}, {{ $selectedYear }}</span>
                            <span class="meta-separator">•</span>
                            <span><strong>Date Generated:</strong> {{ $generatedAt->format('F d, Y · g:i A') }}</span>
                        </div>
                        @if($category === 'all' && !$isMonthly)
                            <p class="report-note">Counts reflect stored records for {{ $monthName }} {{ $selectedYear }} and their current status. Disaster totals include archived records.</p>
                        @endif
                    </div>

                    {{-- ════ REPORT CONTENT: MONTHLY TABULAR INCIDENT REPORT (IMAGE 2) ════ --}}
                    @if($isMonthly)
                        @if($monthlyEntries->isEmpty())
                            <div class="empty-monthly-notice" data-report-block="true">
                                <span class="material-icons-round no-print">info</span>
                                <span>No records found for the selected reporting period.</span>
                            </div>
                        @endif

                        <div class="formal-table-container" data-report-table-container="true">
                            <table class="formal-logbook-table" id="rawMonthlyTable">
                                    <thead>
                                        <tr>
                                            <th class="col-day">DAY / DATE</th>
                                            <th class="col-place">PLACE</th>
                                            <th class="col-accomplishment">INCIDENT DETAILS</th>
                                            <th class="col-remarks">REMARKS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for($day = 1; $day <= $daysInMonth; $day++)
                                            @php $dayEntries = $calendarDays[$day] ?? collect(); @endphp
                                            @if($dayEntries->isEmpty())
                                                <tr class="row-empty-day" data-row-day="{{ $day }}">
                                                    <td class="cell-day">{{ $day }}</td>
                                                    <td class="cell-place">—</td>
                                                    <td class="cell-desc cell-empty-text">No incident recorded</td>
                                                    <td class="cell-remarks">—</td>
                                                </tr>
                                            @else
                                                @foreach($dayEntries as $entry)
                                                    <tr class="row-entry-day" data-row-day="{{ $day }}">
                                                        <td class="cell-day font-bold">
                                                            @if($loop->first)
                                                                {{ $day }}
                                                                <div class="cell-date-sub">{{ $entry['date'] }}</div>
                                                            @else
                                                                <span class="sub-entry-day">"</span>
                                                            @endif
                                                        </td>
                                                        <td class="cell-place">
                                                            <strong>{{ $entry['place'] }}</strong>
                                                            @if(!empty($entry['time']))
                                                                <div class="cell-sub-time"><span class="material-icons-round no-print">schedule</span> {{ $entry['time'] }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="cell-desc">
                                                            <span class="entry-kind-pill kind-{{ strtolower($entry['kind']) }}">[{{ $entry['kind'] }}]</span>
                                                            <span class="entry-details-text">{{ $entry['details'] }}</span>
                                                            @if(!empty($entry['record_number']))
                                                                <span class="entry-ref-id">Ref: {{ $entry['record_number'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="cell-remarks">
                                                            <span class="remarks-status">{{ $entry['status'] }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endfor
                                    </tbody>
                                </table>
                            </div>

                            <div class="monthly-summary-totals" id="rawMonthlyTotals" data-report-block="true">
                                <span>Total Documented Incidents in {{ $monthName }} {{ $selectedYear }}: <strong>{{ $monthlyEntries->count() }}</strong></span>
                            </div>

                    {{-- ════ REPORT CONTENT: RESIDENT RECORDS REPORT (ACTUAL REGISTERED RESIDENTS) ════ --}}
                    @elseif($category === 'residents')
                        @if($detailedResidents->isEmpty())
                            <div class="empty-monthly-notice" data-report-block="true">
                                <span class="material-icons-round no-print">info</span>
                                <span>No records found for the selected reporting period.</span>
                            </div>
                        @else
                            <div class="formal-table-container" data-report-table-container="true">
                                <table class="formal-gov-records-table" id="rawCategoryTable">
                                    <thead>
                                        <tr>
                                            <th class="col-num" style="width: 45px; text-align: center;">NO.</th>
                                            <th class="col-resid" style="width: 110px;">RESIDENT ID</th>
                                            <th class="col-name">FIRST NAME</th>
                                            <th class="col-name">MIDDLE NAME</th>
                                            <th class="col-name">LAST NAME</th>
                                            <th class="col-sitio" style="width: 150px;">SITIO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($detailedResidents as $res)
                                            <tr>
                                                <td style="text-align: center;">{{ $res['index'] }}</td>
                                                <td class="font-bold">{{ $res['resident_id'] }}</td>
                                                <td>{{ $res['first_name'] }}</td>
                                                <td>{{ $res['middle_name'] }}</td>
                                                <td>{{ $res['last_name'] }}</td>
                                                <td>{{ $res['sitio'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="monthly-summary-totals" id="rawCategoryTotals" data-report-block="true">
                                <span>TOTAL REGISTERED RESIDENTS: <strong>{{ $detailedResidents->count() }}</strong></span>
                            </div>
                        @endif

                    {{-- ════ REPORT CONTENT: DISASTER RECORDS REPORT (ACTUAL DISASTERS) ════ --}}
                    @elseif($category === 'disasters')
                        @if($detailedDisasters->isEmpty())
                            <div class="empty-monthly-notice" data-report-block="true">
                                <span class="material-icons-round no-print">info</span>
                                <span>No records found for the selected reporting period.</span>
                            </div>
                        @else
                            <div class="formal-table-container" data-report-table-container="true">
                                <table class="formal-gov-records-table" id="rawCategoryTable">
                                    <thead>
                                        <tr>
                                            <th class="col-num" style="width: 45px; text-align: center;">NO.</th>
                                            <th class="col-disid" style="width: 120px;">DISASTER ID</th>
                                            <th class="col-distype">DISASTER TYPE</th>
                                            <th class="col-disdate" style="width: 120px;">DATE OF OCCURRENCE</th>
                                            <th class="col-disloc">LOCATION / AFFECTED AREA</th>
                                            <th class="col-dissev" style="width: 110px;">SEVERITY LEVEL</th>
                                            <th class="col-disstat" style="width: 110px;">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($detailedDisasters as $dis)
                                            <tr>
                                                <td style="text-align: center;">{{ $dis['index'] }}</td>
                                                <td class="font-bold">{{ $dis['disaster_id'] }}</td>
                                                <td>{{ $dis['type'] }}</td>
                                                <td>{{ $dis['date'] }}</td>
                                                <td>{{ $dis['location'] }}</td>
                                                <td>{{ $dis['severity'] }}</td>
                                                <td>{{ $dis['status'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="monthly-summary-totals" id="rawCategoryTotals" data-report-block="true">
                                <span>TOTAL DISASTER RECORDS: <strong>{{ $detailedDisasters->count() }}</strong></span>
                            </div>
                        @endif

                    {{-- ════ REPORT CONTENT: EMERGENCY RECORDS REPORT (ACTUAL EMERGENCIES) ════ --}}
                    @elseif($category === 'emergencies')
                        @if($detailedEmergencies->isEmpty())
                            <div class="empty-monthly-notice" data-report-block="true">
                                <span class="material-icons-round no-print">info</span>
                                <span>No records found for the selected reporting period.</span>
                            </div>
                        @else
                            <div class="formal-table-container" data-report-table-container="true">
                                <table class="formal-gov-records-table" id="rawCategoryTable">
                                    <thead>
                                        <tr>
                                            <th class="col-num" style="width: 45px; text-align: center;">NO.</th>
                                            <th class="col-emgid" style="width: 120px;">EMERGENCY ID</th>
                                            <th class="col-emgtype">EMERGENCY TYPE</th>
                                            <th class="col-emgdate" style="width: 120px;">DATE OF OCCURRENCE</th>
                                            <th class="col-emgloc">LOCATION</th>
                                            <th class="col-emglvl" style="width: 110px;">EMERGENCY LEVEL</th>
                                            <th class="col-emgstat" style="width: 110px;">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($detailedEmergencies as $emg)
                                            <tr>
                                                <td style="text-align: center;">{{ $emg['index'] }}</td>
                                                <td class="font-bold">{{ $emg['emergency_id'] }}</td>
                                                <td>{{ $emg['type'] }}</td>
                                                <td>{{ $emg['date'] }}</td>
                                                <td>{{ $emg['location'] }}</td>
                                                <td>{{ $emg['level'] }}</td>
                                                <td>{{ $emg['status'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="monthly-summary-totals" id="rawCategoryTotals" data-report-block="true">
                                <span>TOTAL EMERGENCY RECORDS: <strong>{{ $detailedEmergencies->count() }}</strong></span>
                            </div>
                        @endif

                    {{-- ════ REPORT CONTENT: ALL RECORDS ADMINISTRATIVE SUMMARY ════ --}}
                    @else
                        @foreach($sections as $section)
                            <section class="formal-admin-section" data-report-block="true">
                                <div class="admin-section-header">
                                    <h4 class="admin-section-title">{{ $section['heading'] ?? $section['title'] }}</h4>
                                    <span class="admin-section-total">Total: <strong>{{ number_format($section['total']) }}</strong></span>
                                </div>

                                @if($section['total'] === 0)
                                    <p class="empty-section-text">No records found for the selected reporting period.</p>
                                @else
                                    <div class="admin-breakdowns-list">
                                        @foreach($section['breakdowns'] as $groupName => $rows)
                                            <div class="admin-table-group">
                                                <div class="admin-table-caption">{{ $groupName }}</div>
                                                <table class="formal-admin-table">
                                                    <thead>
                                                        <tr>
                                                            <th class="col-cat">Category / Classification</th>
                                                            <th class="col-count">Count</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php $groupSum = 0; @endphp
                                                        @forelse($rows as $row)
                                                            @php $groupSum += $row['count']; @endphp
                                                            <tr>
                                                                <td class="cell-cat">{{ $row['label'] }}</td>
                                                                <td class="cell-count">{{ number_format($row['count']) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td class="cell-cat text-muted">No records</td>
                                                                <td class="cell-count">0</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="row-total">
                                                            <td class="cell-cat font-bold">TOTAL</td>
                                                            <td class="cell-count font-bold">{{ number_format($groupSum) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </section>
                        @endforeach
                    @endif

                    {{-- ════ OFFICIAL SIGNATURE SECTION (IMAGE 2) ════ --}}
                    <div class="official-signature-container" id="rawSignatureContainer" data-report-block="true">
                        <hr class="official-footer-divider"/>
                        <div class="official-signature-section">
                            <div class="sig-block">
                                <p class="sig-role-label">Prepared by:</p>
                                <div class="sig-line"></div>
                                <p class="sig-title">Barangay Secretary</p>
                            </div>

                            <div class="sig-block">
                                <p class="sig-role-label">Approved by:</p>
                                <div class="sig-line"></div>
                                <p class="sig-title">Punong Barangay</p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- The container where paginated sheets are dynamically assembled --}}
                <div class="paginated-sheets-wrapper" id="paginatedSheetsWrapper">
                    {{-- Generated sheets will be inserted here by JavaScript --}}
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Multi-Page Document Engine (Print Layout Pagination)
    function paginateReport() {
        const rawSource = document.getElementById('reportRawSource');
        const sheetsWrapper = document.getElementById('paginatedSheetsWrapper');
        if (!rawSource || !sheetsWrapper) return;

        // Reset wrapper
        sheetsWrapper.style.zoom = 1;
        sheetsWrapper.innerHTML = '';

        // Target content height for A4 page with 20mm/18mm margins
        // An A4 sheet is 297mm high (~1122px at 96 DPI).
        // With top padding 36px, bottom padding 36px, footer ~35px, the usable content area is ~960px.
        const TARGET_USABLE_HEIGHT = 980;

        // Helper to create a new sheet element
        function createSheet(pageNum) {
            const sheet = document.createElement('article');
            sheet.className = 'report-paper formal-document-sheet print-page-sheet';
            sheet.dataset.pageNumber = pageNum;

            const contentArea = document.createElement('div');
            contentArea.className = 'sheet-content-area';

            const footer = document.createElement('footer');
            footer.className = 'sheet-page-footer';
            footer.innerHTML = `
                <div class="footer-meta-left">Barangay Doyong Official Report</div>
                <div class="footer-page-number">Page <span class="current-page-num">${pageNum}</span> of <span class="total-page-num">1</span></div>
            `;

            sheet.appendChild(contentArea);
            sheet.appendChild(footer);
            sheetsWrapper.appendChild(sheet);

            return { sheet, contentArea, footer };
        }

        let currentPageNum = 1;
        let current = createSheet(currentPageNum);

        // Header and Title elements
        const rawHeader = document.getElementById('rawOfficialHeader');
        const rawDivider = document.getElementById('rawHeaderDivider');
        const rawTitleBlock = document.getElementById('rawTitleBlock');

        if (rawHeader) current.contentArea.appendChild(rawHeader.cloneNode(true));
        if (rawDivider) current.contentArea.appendChild(rawDivider.cloneNode(true));
        if (rawTitleBlock) current.contentArea.appendChild(rawTitleBlock.cloneNode(true));

        function startNewPage() {
            currentPageNum++;
            current = createSheet(currentPageNum);

            if (rawHeader) current.contentArea.appendChild(rawHeader.cloneNode(true));
            if (rawDivider) current.contentArea.appendChild(rawDivider.cloneNode(true));

        }

        // Check if report has a large table (Monthly Incident Logbook or Category Records Table)
        const activeRawTable = document.getElementById('rawCategoryTable') || document.getElementById('rawMonthlyTable');
        const activeRawTotals = document.getElementById('rawCategoryTotals') || document.getElementById('rawMonthlyTotals');
        const emptyNotice = rawSource.querySelector('.empty-monthly-notice');

        if (emptyNotice) {
            current.contentArea.appendChild(emptyNotice.cloneNode(true));
        } else if (activeRawTable) {
            const tableHead = activeRawTable.querySelector('thead');
            const tableRows = Array.from(activeRawTable.querySelectorAll('tbody tr'));

            // Create first table on first page
            let activeTable = document.createElement('table');
            activeTable.className = activeRawTable.className;
            if (tableHead) activeTable.appendChild(tableHead.cloneNode(true));
            let activeTbody = document.createElement('tbody');
            activeTable.appendChild(activeTbody);

            let tableWrapper = document.createElement('div');
            tableWrapper.className = 'formal-table-container';
            tableWrapper.appendChild(activeTable);
            current.contentArea.appendChild(tableWrapper);

            tableRows.forEach(row => {
                const clonedRow = row.cloneNode(true);
                activeTbody.appendChild(clonedRow);

                // Check if adding this row caused height to exceed target
                // If this is the very first row on the page, don't split it off to prevent blank pages
                if (activeTbody.children.length > 1 && current.contentArea.scrollHeight > TARGET_USABLE_HEIGHT) {
                    // Remove from current page
                    activeTbody.removeChild(clonedRow);

                    // Start new page
                    startNewPage();

                    // Create new table on next page with cloned thead
                    activeTable = document.createElement('table');
                    activeTable.className = activeRawTable.className;
                    if (tableHead) activeTable.appendChild(tableHead.cloneNode(true));
                    activeTbody = document.createElement('tbody');
                    activeTable.appendChild(activeTbody);

                    tableWrapper = document.createElement('div');
                    tableWrapper.className = 'formal-table-container';
                    tableWrapper.appendChild(activeTable);
                    current.contentArea.appendChild(tableWrapper);

                    // Append row to new page
                    activeTbody.appendChild(clonedRow);
                }
            });

            // Append totals if present
            if (activeRawTotals) {
                const clonedTotals = activeRawTotals.cloneNode(true);
                current.contentArea.appendChild(clonedTotals);
                if (current.contentArea.scrollHeight > TARGET_USABLE_HEIGHT) {
                    current.contentArea.removeChild(clonedTotals);
                    startNewPage();
                    current.contentArea.appendChild(clonedTotals);
                }
            }
        } else {
            // Administrative summary blocks (All records)
            const adminSections = Array.from(rawSource.querySelectorAll('.formal-admin-section'));
            adminSections.forEach(sec => {
                const groups = [...sec.querySelectorAll('.admin-table-group')];
                if (!groups.length) {
                    const emptySection = sec.cloneNode(true);
                    current.contentArea.appendChild(emptySection);
                    if (current.contentArea.scrollHeight > TARGET_USABLE_HEIGHT) {
                        emptySection.remove();
                        startNewPage();
                        current.contentArea.appendChild(emptySection);
                    }
                    return;
                }

                let section, list;
                function appendSection(includeHeading = true) {
                    section = sec.cloneNode(false);
                    const heading = sec.querySelector('.admin-section-header');
                    if (includeHeading && heading) section.appendChild(heading.cloneNode(true));
                    list = document.createElement('div');
                    list.className = 'admin-breakdowns-list';
                    section.appendChild(list);
                    current.contentArea.appendChild(section);
                }
                appendSection();
                groups.forEach(group => {
                    const tableGroup = group.cloneNode(true);
                    list.appendChild(tableGroup);
                    if (current.contentArea.scrollHeight > TARGET_USABLE_HEIGHT) {
                        tableGroup.remove();
                        const isFirstTable = !list.children.length;
                        if (isFirstTable) section.remove();
                        startNewPage();
                        appendSection(isFirstTable);
                        list.appendChild(tableGroup);
                    }
                });
            });
        }

        // Place Signature Section at the very end
        const rawSignature = document.getElementById('rawSignatureContainer');
        if (rawSignature) {
            const clonedSignature = rawSignature.cloneNode(true);
            current.contentArea.appendChild(clonedSignature);

            // If signature overflows the current page, move it cleanly to the next page
            if (current.contentArea.scrollHeight > TARGET_USABLE_HEIGHT) {
                // Recover excess paragraph/table spacing before using another sheet.
                // Keep the same readable font sizes and blank signature lines.
                current.sheet.classList.add('compact-report-page');
                const paperStyle = getComputedStyle(current.sheet);
                const availableHeight = parseFloat(paperStyle.minHeight)
                    - parseFloat(paperStyle.paddingTop) - parseFloat(paperStyle.paddingBottom)
                    - current.footer.offsetHeight - 12;
                if (current.contentArea.scrollHeight > availableHeight) {
                    current.sheet.classList.remove('compact-report-page');
                    current.contentArea.removeChild(clonedSignature);
                    startNewPage();
                    current.contentArea.appendChild(clonedSignature);
                }
            }
        }

        // Update total page counts across all sheets
        const allSheets = sheetsWrapper.querySelectorAll('.print-page-sheet');
        const totalPages = allSheets.length;
        allSheets.forEach(sheet => {
            const totalSpan = sheet.querySelector('.total-page-num');
            if (totalSpan) totalSpan.textContent = totalPages;
        });

        // Hide raw source container once paginated
        rawSource.style.display = 'none';
        // Measure at the physical paper width, then scale only the screen preview.
        const availableWidth = sheetsWrapper.parentElement.clientWidth;
        sheetsWrapper.style.zoom = Math.min(1, availableWidth / (210 * 96 / 25.4));
    }

    // Run pagination immediately and ensure print reflects pagination
    paginateReport();
    window.addEventListener('load', paginateReport);
    // Keep the same measured sheets for screen, print, and Save as PDF.
    // Rebuilding in print media changes the available width and page breaks.

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(paginateReport, 200);
    });
});
</script>
</body>
</html>
