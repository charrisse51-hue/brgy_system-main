<form id="residentFilters" class="resident-filters" method="GET" action="{{ route(\App\Support\RecordPortal::routeName('residents.index')) }}">
    <div class="res-toolbar">
        <div class="res-search-wrap">
            <input class="res-search-input" name="search" type="search" maxlength="150" value="{{ request('search') }}" placeholder="Search resident ID or name..." aria-label="Search residents"/>
        </div>
        <select name="sitio" class="res-filter-select" aria-label="Sitio">
            <option value="">All Sitios</option>
            @foreach(\App\Support\EmergencyRecordForm::sitios() as $value => $label)
            <option value="{{ $value }}" @selected(request('sitio') === $value)>{{ $value === 'Sitio Baba' ? 'Sito Baba' : $label }}</option>
            @endforeach
        </select>
        <a class="btn btn-primary res-add-btn" href="{{ route(\App\Support\RecordPortal::routeName('residents.create')) }}"><span class="material-icons-round">add</span>Add New Resident</a>
    </div>
    <details class="advanced-filters">
        <summary><span class="material-icons-round">tune</span> Advanced search &amp; filters</summary>
        <div class="advanced-filter-grid">
            <label>Civil status<select name="civil_status">
                <option value="">All civil statuses</option>
                @foreach(\App\Support\ResidentForm::CIVIL_STATUSES as $status)
                <option value="{{ $status }}" @selected(request('civil_status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select></label>
            <label>Registered from<input type="date" name="registered_from" value="{{ request('registered_from') }}"/></label>
            <label>Registered through<input type="date" name="registered_to" value="{{ request('registered_to') }}"/></label>
            <label>Contact number<select name="contact">
                <option value="">Any</option><option value="with" @selected(request('contact') === 'with')>Has contact number</option><option value="without" @selected(request('contact') === 'without')>Missing contact number</option>
            </select></label>
            <label>Sort by<select name="sort">
                @foreach(['newest' => 'Newest first', 'oldest' => 'Oldest first', 'name_asc' => 'Name A–Z', 'name_desc' => 'Name Z–A'] as $value => $label)
                <option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>{{ $label }}</option>
                @endforeach
            </select></label>
            <label>Results per page<select name="per_page">
                @foreach([15, 25, 50] as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }}</option>
                @endforeach
            </select></label>
        </div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary">Apply Filters</button><a href="{{ route(\App\Support\RecordPortal::routeName('residents.index')) }}" class="btn btn-ghost">Clear All</a></div>
    </details>
    @if($errors->any())
    <p class="filter-error" role="alert">{{ $errors->first() }}</p>
    @endif
    <p id="residentSearchFeedback" class="filter-error" role="status" aria-live="polite" hidden></p>
</form>
