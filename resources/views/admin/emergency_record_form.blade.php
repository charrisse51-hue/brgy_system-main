@extends('admin.layouts.emergency-record')
@section('title', $emergency->exists ? 'Edit Emergency Record' : 'Add Emergency Record')
@section('content')
@php
    $sections = \App\Support\EmergencyRecordForm::sections();
    $groups = \App\Support\EmergencyRecordForm::groups();
    $tabs = ['Emergency Information', 'Incident Details', 'Casualties & Effects', 'Response & Actions'];
@endphp
<form id="emergency-record-form" method="POST" action="{{ $emergency->exists ? route(\App\Support\RecordPortal::routeName('emergency.record.update'),$emergency) : route(\App\Support\RecordPortal::routeName('emergency.store')) }}">
    @csrf
    @if($emergency->exists) @method('PUT') @endif
    <input type="hidden" name="entries_json" id="er-entries" value="{{ old('entries_json',json_encode($entries ?: new \stdClass())) }}"/>
    <div class="er-card er-tabbed-card">
        <div class="er-tabs" role="tablist" aria-label="Emergency record sections">
            @foreach($tabs as $tab)
            <button type="button" class="er-tab" role="tab" id="er-tab-{{ $loop->index }}" aria-controls="er-panel-{{ $loop->index }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? 0 : -1 }}"><span class="er-tab-number" aria-hidden="true">{{ $loop->iteration }}</span>{{ $tab }}</button>
            @endforeach
        </div>
        <p class="er-hint">Fields marked <span class="er-required">*</span> are required. Casualties and effects are optional.</p>
        @if($errors->any())<div class="er-error-summary" role="alert" tabindex="-1"><strong>Please correct the highlighted fields.</strong><p>Use Edit on an entry to correct errors shown in its table.</p>@error('entries_json')<p>{{ $message }}</p>@enderror @error('entries')<p>{{ $message }}</p>@enderror</div>@endif
        @foreach($tabs as $tab)
        @php $tabIndex = $loop->index; @endphp
        <section class="er-tab-panel" id="er-panel-{{ $tabIndex }}" role="tabpanel" aria-labelledby="er-tab-{{ $tabIndex }}" tabindex="0" @if(!$loop->first) hidden @endif>
            <h3>{{ $tab }}</h3>
            @if($tabIndex===0)
            <div class="er-grid">
                @foreach($sections['Emergency Information'] as $name=>$field)
                    @continue($name==='message')
                    @include('admin.partials.emergency-record-field')
                @endforeach
            </div>
            @elseif($tabIndex===1)
            <div class="er-grid">
                @include('admin.partials.emergency-record-field', ['name'=>'message', 'field'=>$sections['Emergency Information']['message']])
            </div>
            @elseif($tabIndex===2)
            <div class="er-casualty-grid">
                @foreach(['dead_drowned'=>'Dead / Drowned','injured'=>'Injured','missing'=>'Missing'] as $category=>$label)
                <div class="er-entry-section"><h4>{{ $label }}</h4><div data-entry-table="casualties" data-category="{{ $category }}"></div><button type="button" class="btn btn-ghost" data-add-entry="casualties" data-category="{{ $category }}">+ Add {{ $category==='dead_drowned' ? 'Dead/Drowned' : $label }} Person</button></div>
                @endforeach
            </div>
            <h4 class="er-subheading">Effects of Emergency</h4>
            <p class="er-hint">Open a section to record the relevant effects.</p>
            @else
                @foreach(['Actions Taken','Reporter Information','Record Verification'] as $title)
                <div class="er-form-section">
                    @if($title!=='Actions Taken')<h4 class="er-subheading">{{ $title }}</h4>@endif
                    @if($title==='Reporter Information')
                        {{-- Row 1: First Name, Middle Name, Last Name --}}
                        <div class="er-grid-3">
                            @include('admin.partials.emergency-record-field', ['name'=>'reporter_first_name', 'field'=>$sections['Reporter Information']['reporter_first_name']])
                            @include('admin.partials.emergency-record-field', ['name'=>'reporter_middle_name', 'field'=>$sections['Reporter Information']['reporter_middle_name']])
                            @include('admin.partials.emergency-record-field', ['name'=>'reporter_last_name', 'field'=>$sections['Reporter Information']['reporter_last_name']])
                        </div>
                        {{-- Row 2: Age, Sitio, Other Address / Landmark --}}
                        <div class="er-grid-3" style="margin-top: 12px;">
                            @include('admin.partials.emergency-record-field', ['name'=>'reporter_age', 'field'=>$sections['Reporter Information']['reporter_age']])
                            @include('admin.partials.emergency-record-field', ['name'=>'reporter_sitio', 'field'=>$sections['Reporter Information']['reporter_sitio']])
                            @include('admin.partials.emergency-record-field', ['name'=>'reporter_other_address', 'field'=>$sections['Reporter Information']['reporter_other_address']])
                        </div>
                        {{-- Additional Contact / Released By fields --}}
                        <div class="er-grid-3" style="margin-top: 12px;">
                            @include('admin.partials.emergency-record-field', ['name'=>'reporter_contact', 'field'=>$sections['Reporter Information']['reporter_contact']])
                            @include('admin.partials.emergency-record-field', ['name'=>'reporter_relationship', 'field'=>$sections['Reporter Information']['reporter_relationship']])
                            @include('admin.partials.emergency-record-field', ['name'=>'released_by', 'field'=>$sections['Reporter Information']['released_by']])
                        </div>
                        <input type="hidden" name="reporter_name" id="er-reporter_name" value="{{ old('reporter_name', $emergency->reporter_name ?? '') }}"/>
                        <input type="hidden" name="reporter_address" id="er-reporter_address" value="{{ old('reporter_address', $emergency->reporter_address ?? '') }}"/>
                    @else
                        <div class="er-grid">
                            @foreach($sections[$title] as $name=>$field) @include('admin.partials.emergency-record-field') @endforeach
                        </div>
                    @endif
                </div>
                @endforeach
                <p class="er-hint er-verification-hint">Your signed-in account is recorded as the verifying user when this record is marked Verified. Leave Date Verified blank to use the current date and time.</p>
            @endif
            @if(in_array($tabIndex,[1,2]))
            <div class="er-effects-grid">
                @foreach($groups as $key=>$group)
                    @continue($key==='casualties' || ($tabIndex===1 ? $key!=='relatedIncidents' : $key==='relatedIncidents'))
                    <details class="er-accordion" data-effect="{{ $key }}"><summary>{{ $group['title'] }} <span data-entry-count="{{ $key }}">0 entries</span></summary><div class="er-accordion-body"><div data-entry-table="{{ $key }}"></div><button type="button" class="btn btn-ghost" data-add-entry="{{ $key }}">+ Add Entry</button></div></details>
                @endforeach
            </div>
            @endif
        </section>
        @endforeach
        <section id="er-entry-editor" class="er-inline-editor" aria-labelledby="er-entry-title" hidden>
            <div class="er-inline-header"><h4 id="er-entry-title">Add Entry</h4><button type="button" class="er-close" data-close-entry aria-label="Close entry form"><span class="material-icons-round">close</span></button></div>
            <div id="er-entry-fields" class="er-grid"></div>
            <div class="er-actions"><button type="button" class="btn btn-ghost" data-close-entry>Cancel Entry</button><button type="submit" form="er-entry-form" class="btn btn-primary" id="er-save-entry">Add Entry</button></div>
        </section>
        <div class="er-actions er-step-actions">
            <button type="button" class="btn btn-ghost" id="er-previous" hidden>Previous</button>
            <span id="er-step-label" class="er-hint" aria-live="polite">Section 1 of 4</span>
            <button type="button" class="btn btn-primary" id="er-next">Next <span class="material-icons-round" aria-hidden="true">arrow_forward</span></button>
            <a class="btn btn-ghost" href="{{ route(\App\Support\RecordPortal::routeName('emergency.index')) }}" data-discard hidden>Cancel</a>
            <button class="btn btn-primary" id="save-emergency" type="submit" hidden><span class="material-icons-round" aria-hidden="true">save</span> Save Emergency Record</button>
        </div>
    </div>
</form>
{{-- Inline entry controls stage rows in entries_json; the record is saved only by the main form. --}}
<form id="er-entry-form"></form>
@endsection
@section('scripts')
<script id="er-schema" type="application/json">@json(\App\Support\EmergencyRecordForm::groups())</script>
<script id="er-errors" type="application/json">@json($errors->toArray())</script>
<script src="{{ asset('js/person-validation.js') }}" defer></script>
<script src="{{ asset('js/emergency-record-form.js') }}" defer></script>
@endsection
