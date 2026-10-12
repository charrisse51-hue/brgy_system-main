@php
    $editing = $mode === 'edit';
    $fullPage = $fullPage ?? false;
    $prefix = $editing ? 'edit' : 'add';
    $restore = old('_resident_form') === 'individual' && old('_resident_mode') === $mode;
    $values = $restore ? session()->getOldInput() : (isset($editingResident) ? array_merge($editingResident->only(array_keys(\App\Support\ResidentForm::fields())), ['id' => $editingResident->id, 'birthdate' => $editingResident->birthdate?->format('Y-m-d')]) : []);
    $formAction = $editing ? route(\App\Support\RecordPortal::routeName('residents.update'), $editingResident ?? old('_resident_id')) : route(\App\Support\RecordPortal::routeName('residents.store'));
@endphp
<div id="{{ $prefix }}ResidentModal" class="{{ $fullPage ? 'resident-form-page' : 'modal-overlay resident-individual-modal' }}" @unless($fullPage) style="display:none" role="dialog" aria-modal="true" @endunless aria-labelledby="{{ $prefix }}ResidentTitle"
     data-mode="{{ $mode }}" data-restore="{{ $restore ? '1' : '0' }}" data-values="{{ json_encode($values) }}" data-errors="{{ json_encode($restore ? $errors->keys() : []) }}">
    <div class="{{ $fullPage ? 'resident-form-card' : 'modal-box modal-box--lg' }}">
        <div class="modal-header">
            <div><h3 class="modal-title" id="{{ $prefix }}ResidentTitle">{{ $editing ? 'Edit Resident' : 'Add New Resident' }}</h3><p class="modal-sub">Individual record · Fields marked * are required.</p></div>
            @unless($fullPage)<button type="button" class="modal-close-btn" data-resident-close aria-label="Close"><span class="material-icons-round">close</span></button>@endunless
        </div>
        <form id="{{ $prefix }}ResidentForm" method="POST" action="{{ $formAction }}" novalidate>
            @csrf
            @if($editing) @method('PUT') @endif
            <input type="hidden" name="_resident_form" value="individual"/>
            <input type="hidden" name="_resident_mode" value="{{ $mode }}"/>
            <input type="hidden" name="_resident_id" value="{{ $values['id'] ?? $values['_resident_id'] ?? '' }}"/>
            <ol class="resident-steps" aria-label="Form steps">
                @foreach(array_merge(array_keys(\App\Support\ResidentForm::sections()), ['Review']) as $title)
                <li><button type="button" data-step-link="{{ $loop->index }}"><span>{{ $loop->iteration }}</span>{{ $title }}</button></li>
                @endforeach
            </ol>
            <div class="resident-step-body">
                @if($restore && $errors->any())
                <div class="resident-form-errors" role="alert"><strong>Please correct these fields:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @foreach(\App\Support\ResidentForm::sections() as $title => $fields)
                <section class="resident-step" data-step="{{ $loop->index }}" aria-label="{{ $title }}" @if(!$loop->first) hidden @endif>
                    <h4>{{ $title }}</h4><div class="res-form-grid">
                    @foreach($fields as $name => $field)
                        @php $type = $field['type'] ?? 'text'; $id = $prefix.'_'.$name; @endphp
                        <div class="em-form-group {{ $type === 'checkboxes' ? 'res-span-2' : '' }}" data-field="{{ $name }}" @isset($field['condition']) data-condition="{{ $field['condition'] }}" @endisset>
                            @if($type === 'checkboxes')
                            <fieldset><legend class="em-label">{{ $field['label'] }}</legend><div class="resident-checks">
                                @foreach($field['options'] as $option)<label><input type="checkbox" name="{{ $name }}[]" value="{{ $option }}"/> {{ $option }}</label>@endforeach
                            </div></fieldset>
                            @else
                            <label class="em-label" for="{{ $id }}">{{ $field['label'] }} @if($field['required'] ?? false)<span class="em-req">*</span>@endif</label>
                            @if($type === 'select')
                            <select class="em-input" id="{{ $id }}" name="{{ $name }}" data-default="{{ $field['default'] ?? '' }}" @required($field['required'] ?? false)>
                                <option value="">Select...</option>
                                @foreach($field['options'] as $value => $label)<option value="{{ $value }}">{{ ucfirst($label) }}</option>@endforeach
                            </select>
                            @else
                            <input class="em-input" id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" data-default="{{ $field['default'] ?? '' }}" @required($field['required'] ?? false)
                                @if(in_array($type, ['text','email','tel'])) maxlength="{{ $field['max'] ?? 255 }}" @endif
                                @if(in_array($type, ['number','date']) && isset($field['max'])) max="{{ $field['max'] }}" @endif
                                @isset($field['min']) min="{{ $field['min'] }}" @endisset
                                @isset($field['step']) step="{{ $field['step'] }}" @endisset/>
                            @endif
                            @endif
                            @isset($field['help'])<small>{{ $field['help'] }}</small>@endisset
                            @if($restore && $errors->has($name))<small class="resident-field-error">{{ $errors->first($name) }}</small>@endif
                        </div>
                    @endforeach
                    </div>
                </section>
                @endforeach
                <section class="resident-step" data-step="4" aria-label="Review" hidden><h4>Review Resident Information</h4><p>Check the details before saving. Use Back to make corrections.</p><div class="resident-review"></div></section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-resident-back>Back</button>
                @if($fullPage)<a class="btn btn-ghost" href="{{ route(\App\Support\RecordPortal::routeName('residents.index'), isset($editingResident) ? ['view_id' => $editingResident->id] : []) }}">Cancel</a>
                @else<button type="button" class="btn btn-ghost" data-resident-close>Cancel</button>@endif
                <button type="button" class="btn btn-primary" data-resident-next>Next</button>
                <button type="submit" class="btn btn-primary" data-resident-save hidden>{{ $editing ? 'Save Changes' : 'Save Resident' }}</button>
            </div>
        </form>
    </div>
</div>
