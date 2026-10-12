<div class="logbook-overlay" id="incidentLogbookModal" role="dialog" aria-modal="true" aria-labelledby="logbook-title" aria-describedby="logbook-subtitle">
            <form class="logbook-form logbook-modal" method="POST" action="{{ isset($editingIncident) ? route(\App\Support\RecordPortal::routeName('incidents.update'), $editingIncident) : route(\App\Support\RecordPortal::routeName('incidents.store')) }}">
                @csrf
                @isset($editingIncident) @method('PUT') @endisset
                <div class="logbook-heading"><div><h1 id="logbook-title">{{ isset($editingIncident) ? 'Edit' : 'Add' }} Disaster Record</h1><p id="logbook-subtitle">Digital version of the existing barangay logbook</p></div><a class="logbook-close" href="{{ route(\App\Support\RecordPortal::routeName('incidents.index')) }}" aria-label="Close new record form"><span class="material-icons-round">close</span></a></div>
                <div class="logbook-scroll"><small class="logbook-required-note">Fields marked <b class="logbook-required">*</b> are required.</small>
                @if($errors->any())<div class="logbook-errors" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @foreach(\App\Support\IncidentLogbook::sections() as $section => $fields)
                <fieldset><legend>{{ $section }}</legend>
                    @if($section === 'Respondent / Person Involved')
                    <div id="logbook-persons">
                        @foreach(old('persons', $editingIncident->logbook['persons'] ?? []) ?: [['name'=>'', 'age'=>'', 'address'=>'']] as $person)
                        <div class="logbook-person"><div class="logbook-grid">
                            <label>Full Name<input name="persons[{{ $loop->index }}][name]" value="{{ $person['name'] ?? '' }}" maxlength="255"/></label>
                            <label>Age<input type="number" name="persons[{{ $loop->index }}][age]" value="{{ $person['age'] ?? '' }}" min="0" max="120"/></label>
                            <label class="logbook-wide">Address<input name="persons[{{ $loop->index }}][address]" value="{{ $person['address'] ?? '' }}" maxlength="255"/></label>
                        </div><button type="button" class="btn btn-ghost" data-remove-person>Remove Person</button></div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-ghost" id="add-logbook-person">+ Add Another Person</button>
                    @else
                    <div class="logbook-grid">
                        @foreach($fields as $name => $field)
                        @php
                            [$label, $type] = $field;
                            $required = $field[2] ?? false;
                            $default = ($editingIncident->logbook[$name] ?? $editingIncident->$name ?? null) ?? match($name) { 'record_date'=>now()->toDateString(), 'record_time'=>now()->format('H:i'), 'barangay'=>'Doyong', default=>'' };
                        @endphp
                        <label class="{{ in_array($type, ['textarea','readonly','select']) || in_array($name, ['location','other_address']) ? 'logbook-wide' : '' }}">
                            <span>{{ $label }} @if($required)<b class="logbook-required">*</b>@endif</span>
                            @if($type === 'readonly')
                                <input value="{{ $name === 'record_number' ? $recordPreview : ($editingIncident->logbook['recorded_by'] ?? auth()->user()->name) }}" readonly/>
                                <small>{{ $name === 'record_number' ? (isset($editingIncident) ? 'The record number is retained when editing.' : 'Auto-generated on save. The final number may change if another record is saved first.') : 'Automatically recorded using your signed-in account.' }}</small>
                            @elseif($type === 'textarea')
                                <textarea name="{{ $name }}" rows="{{ $name === 'description' ? 6 : 3 }}" maxlength="20000" @required($required) placeholder="{{ $name === 'description' ? 'Write the complete details of what happened, similar to the narrative currently written in the logbook.' : '' }}">{{ old($name, $default) }}</textarea>
                            @elseif($type === 'select')
                                <select name="{{ $name }}" required><option value="">Select Type</option>@foreach(['fire'=>'Fire', 'accident'=>'Accident', 'theft'=>'Theft / Crime', 'flood'=>'Flood', 'others'=>'Others'] as $value=>$option)<option value="{{ $value }}" @selected(old($name, $default) === $value)>{{ $option }}</option>@endforeach</select>
                            @else
                                <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $default) }}" @required($required) @if($type === 'number') min="0" max="120" @elseif($type === 'text') maxlength="255" @endif/>
                            @endif
                        </label>
                        @endforeach
                    </div>
                    @endif
                </fieldset>
                @endforeach
                </div>
                <div class="logbook-actions"><a class="btn btn-ghost" href="{{ route(\App\Support\RecordPortal::routeName('incidents.index')) }}">Cancel</a><button class="btn btn-primary" type="submit"><span class="material-icons-round">save</span> Save Record</button></div>
            </form>
</div>
