@php
    $type=$field['type']; $required=$field['required'];
    $default=$emergency->exists ? $emergency->$name : match($name) { 'report_date_time'=>now()->format('Y-m-d\TH:i'), 'report_type'=>'initial', 'verification_status'=>'pending', default=>'' };
    if ($default instanceof \DateTimeInterface) $default=$default->format($type==='date' ? 'Y-m-d' : 'Y-m-d\TH:i');
    if ($type==='time' && $default) $default=substr($default,0,5);
    $options=$name==='emergency_type' ? $types : ($type==='staff' ? $staff->pluck('name','id')->all() : ($field['options'] ?? []));
@endphp
<div class="er-field {{ $type==='textarea' || in_array($name,['location','reporter_address']) ? 'er-wide' : '' }}" data-main-field="{{ $name }}" @if($name==='progress_report_no' && old('report_type',$emergency->report_type ?? 'initial')!=='progress') hidden @endif>
    <label for="er-{{ $name }}">{{ $field['label'] }} @if($required || $name==='progress_report_no' || in_array($name, ['reporter_first_name', 'reporter_last_name']))<span class="er-required">*</span>@endif</label>
    @if(in_array($type,['select','staff']))
    <select id="er-{{ $name }}" name="{{ $name }}" @required($required) @error($name) aria-invalid="true" aria-describedby="error-{{ $name }}" @enderror>
        <option value="">Select {{ $name==='reporter_sitio' ? 'Sitio' : strtolower($field['label']) }}</option>
        @foreach($options as $value=>$label)<option value="{{ $value }}" @selected((string)old($name,$default)===(string)$value)>{{ $label }}</option>@endforeach
    </select>
    @elseif($type==='textarea')
    <textarea id="er-{{ $name }}" name="{{ $name }}" rows="3" maxlength="20000" @required($required) @error($name) aria-invalid="true" aria-describedby="error-{{ $name }}" @enderror>{{ old($name,$default) }}</textarea>
    @else
    <input id="er-{{ $name }}" @if($type==='readonly') readonly @else name="{{ $name }}" @endif type="{{ $type==='readonly' ? 'text' : ($type==='age' ? 'number' : $type) }}" value="{{ old($name,$default) }}" @required($required) @if(in_array($name, ['reporter_first_name', 'reporter_middle_name', 'reporter_last_name', 'progress_report_no'])) maxlength="50" @elseif(in_array($type,['text','tel'])) maxlength="255" @endif @if($type==='age') min="0" max="120" step="1" @endif @error($name) aria-invalid="true" aria-describedby="error-{{ $name }}" @enderror/>
    @endif
    @error($name)<small class="er-error" id="error-{{ $name }}">{{ $message }}</small>@enderror
</div>

