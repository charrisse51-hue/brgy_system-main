@extends('admin.layouts.emergency-record')
@section('title', 'Emergency Record Details')
@section('content')
<div class="er-record-heading"><p><strong>#EMG-{{ str_pad($emergency->id,4,'0',STR_PAD_LEFT) }}</strong> · Recorded by {{ $emergency->creator?->name ?? 'Unavailable account' }}</p><a class="btn btn-primary" href="{{ route(\App\Support\RecordPortal::routeName('emergency.edit'),$emergency) }}">Edit Record</a></div>
@foreach(\App\Support\EmergencyRecordForm::sections() as $title=>$fields)
<section class="er-card"><h3>{{ $title }}</h3><dl class="{{ $title==='Reporter Information' ? 'er-grid-3' : 'er-grid' }} er-readonly">
    @foreach($fields as $name=>$field)
    @continue($name==='progress_report_no' && $emergency->report_type!=='progress')
    @php
        $value=match($name) { 'day'=>$emergency->incident_date?->format('l'), 'validated_by'=>$emergency->validator?->name, 'checked_by'=>$emergency->checker?->name, 'emergency_type'=>\App\Support\EmergencyRecordForm::types()[$emergency->emergency_type] ?? $emergency->emergency_type, default=>$emergency->$name };
        if ($value instanceof \DateTimeInterface) $value=$value->format($field['type']==='date' ? 'M d, Y' : 'M d, Y g:i A');
        elseif ($value!==null) $value=$field['options'][$value] ?? $value;
    @endphp
    <div class="{{ $field['type']==='textarea' ? 'er-wide' : '' }}"><dt>{{ $field['label'] }}</dt><dd>{{ $value===null || $value==='' ? '—' : $value }}</dd></div>
    @endforeach
</dl>@if($title==='Record Verification' && $emergency->verified_by)<p class="er-hint">Verification recorded by {{ $emergency->verifier?->name }}.</p>@endif</section>
@if($loop->first)
@foreach(\App\Support\EmergencyRecordForm::groups() as $key=>$group)
<section class="er-card"><h3>{{ $group['title'] }}</h3>
    @if($emergency->$key->isEmpty())<p class="er-hint">No entries reported.</p>
    @else<div class="er-table-wrap"><table class="er-table"><thead><tr>@foreach($group['fields'] as $name=>$field) @continue($key==='casualties' && in_array($name,['middle_name','last_name']))<th scope="col">{{ $key==='casualties' && $name==='first_name' ? 'Full Name' : $field['label'] }}</th>@endforeach</tr></thead><tbody>
        @foreach($emergency->$key as $row)
        @php $legacyNames = $key==='casualties' && !$row->first_name && !$row->last_name ? \App\Support\PersonDetails::names(['name'=>$row->name]) : []; @endphp
        <tr>@foreach($group['fields'] as $name=>$field) @continue($key==='casualties' && in_array($name,['middle_name','last_name']))<td>{{ ($key==='casualties' && $name==='first_name' ? $row->name : null) ?? $legacyNames[$name] ?? $field['options'][$row->$name] ?? $row->$name ?? '—' }}</td>@endforeach</tr>@endforeach
    </tbody></table></div>@endif
</section>
@endforeach
@endif
@endforeach
<div class="er-actions"><a class="btn btn-ghost" href="{{ route(\App\Support\RecordPortal::routeName('emergency.index')) }}">Back to Emergency Records</a></div>
@endsection
