<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $incident->record_number }} - Barangay Doyong</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/disaster-records.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/incident-logbook.css') }}"/>
    @include('components.ui-assets')
</head>
<body class="admin-theme disaster-records"><div class="app-shell">
@include('admin.partials.sidebar', ['sidebarSubtitle'=>'RECORDS ADMIN'])
<main class="main-area">
    <header class="topbar">
            @include('components.navigation-toggle')<div class="topbar-title"><h2>Disaster Record</h2><p>{{ $incident->record_number }}</p></div></header>
    <div class="page-content"><article class="logbook-form">
        <div class="logbook-heading"><h1>{{ $incident->record_number }}</h1><p>{{ ucfirst($incident->type) }} · {{ ucfirst($incident->status) }}</p></div>
        @foreach(\App\Support\IncidentLogbook::sections() as $title=>$fields)
        <fieldset><legend>{{ $title }}</legend><dl class="logbook-details">
            @if($title === 'Respondent / Person Involved')
                @forelse(($incident->logbook['persons'] ?? []) as $person)
                    <dt>Person {{ $loop->iteration }}</dt><dd>{{ $person['name'] ?? '—' }}<br>Age: {{ $person['age'] ?? '—' }}<br>Address: {{ $person['address'] ?? '—' }}</dd>
                @empty<dt>Persons involved</dt><dd>—</dd>@endforelse
            @else
                @foreach($fields as $name=>$field)
                    @php
                        $value = match($name) {
                            'record_number'=>$incident->record_number,
                            'type'=>ucfirst($incident->type),
                            'location'=>$incident->location,
                            'description'=>$incident->description,
                            'recorded_by'=>$incident->logbook['recorded_by'] ?? $incident->user?->name,
                            'sitio'=>$incident->sitio ?? $incident->logbook['sitio'] ?? $incident->logbook['purok'] ?? null,
                            default=>$incident->logbook[$name] ?? null
                        };
                    @endphp
                    <dt>{{ $field[0] }}</dt><dd>{{ $value === null || $value === '' ? '—' : $value }}</dd>
                @endforeach
            @endif
        </dl></fieldset>
        @endforeach
        @if($incident->image_path)<fieldset><legend>Incident Photo</legend><img src="{{ asset('storage/'.$incident->image_path) }}" alt="Incident photo" style="max-width:100%"/></fieldset>@endif
        @if(!$incident->archived)
        <form method="POST" action="{{ route(\App\Support\RecordPortal::routeName('incidents.status'), $incident) }}">
            @csrf @method('PATCH')
            <fieldset><legend>Incident Status</legend><div class="logbook-grid">
                <label>Status<select name="status" required>@foreach(['pending','ongoing','resolved'] as $status)<option value="{{ $status }}" @selected($incident->status===$status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                <div class="logbook-actions"><button class="btn btn-primary" type="submit">Update Status</button></div>
            </div></fieldset>
        </form>
        @endif
        <div class="logbook-actions"><a class="btn btn-primary" href="{{ route(\App\Support\RecordPortal::routeName('incidents.edit'), $incident) }}">Edit Record</a><a class="btn btn-ghost" href="{{ route(\App\Support\RecordPortal::routeName('incidents.index')) }}">Back to Records</a></div>
    </article></div>
</main></div></body></html>
