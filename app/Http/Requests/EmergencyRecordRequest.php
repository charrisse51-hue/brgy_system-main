<?php
namespace App\Http\Requests;

use App\Support\EmergencyRecordForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmergencyRecordRequest extends FormRequest
{
    public function authorize(): bool { return in_array($this->user()?->role, ['admin','staff'], true); }

    protected function prepareForValidation(): void
    {
        if ($this->has('entries_json')) {
            $decoded = is_string($this->entries_json) ? json_decode($this->entries_json, true) : null;
            if (is_array($decoded)) array_walk_recursive($decoded, function (&$value) { if (is_string($value)) $value = trim($value) === '' ? null : trim($value); });
            if (is_array($decoded['casualties'] ?? null)) foreach ($decoded['casualties'] as &$person) if (is_array($person)) $person = \App\Support\PersonDetails::names($person);
            unset($person);
            $this->merge(['entries'=>is_array($decoded) ? $decoded : null]);
        }
    }

    public function rules(): array
    {
        $rules = ['entries_json'=>'nullable|json|max:2000000', 'entries'=>['nullable','array:'.implode(',', array_keys(EmergencyRecordForm::groups()))]];
        foreach (EmergencyRecordForm::sections() as $fields) foreach ($fields as $name=>$field) {
            if ($name !== 'day') $rules[$name] = $this->fieldRules($field);
        }
        $rules['emergency_type'] = ['required',Rule::in(array_keys(EmergencyRecordForm::types()))];
        foreach (['validated_by','checked_by'] as $key) $rules[$key] = ['nullable','integer',Rule::exists('users','id')->where(fn ($query) => $query->whereIn('role',['admin','staff']))];
        $rules['progress_report_no'] = ['exclude_unless:report_type,progress','required','string','max:50'];
        $rules['date_verified'] = ['exclude_unless:verification_status,verified','nullable','date_format:Y-m-d\TH:i','before_or_equal:now'];

        // Standardized reporter validation rules
        $rules['reporter_first_name'] = ['nullable', 'required_with:reporter_last_name,reporter_middle_name,reporter_age,reporter_sitio,reporter_other_address', 'string', 'min:2', 'max:50', 'regex:/^[\p{L}\s\-\']+$/u'];
        $rules['reporter_middle_name'] = ['nullable', 'string', 'max:50', 'regex:/^[\p{L}\s\-\']+$/u'];
        $rules['reporter_last_name'] = ['nullable', 'required_with:reporter_first_name,reporter_middle_name,reporter_age,reporter_sitio,reporter_other_address', 'string', 'min:2', 'max:50', 'regex:/^[\p{L}\s\-\']+$/u'];
        $rules['reporter_age'] = ['nullable', 'integer', 'min:0', 'max:120'];
        $rules['reporter_sitio'] = ['nullable', Rule::in(array_keys(EmergencyRecordForm::sitios()))];
        $rules['reporter_contact'] = ['nullable', 'regex:/^09\d{9}$/'];

        foreach (EmergencyRecordForm::groups() as $key=>$group) {
            $rules["entries.$key"] = 'nullable|array|max:100';
            $rules["entries.$key.*"] = 'array:'.implode(',',array_keys($group['fields'])).($key==='casualties' ? ',name' : '');
            foreach ($group['fields'] as $name=>$field) $rules["entries.$key.*.$name"] = $this->fieldRules($field);
        }
        $rules = array_replace($rules, \App\Support\PersonDetails::rules('entries.casualties.*', true));
        $rules['entries.casualties.*.name'] = 'nullable|string|max:255';
        foreach (['first','middle','last'] as $part) $rules['reporter_'.$part.'_name'][array_key_last($rules['reporter_'.$part.'_name'])] = 'regex:'.\App\Support\PersonDetails::NAME_PATTERN;
        $rules['reporter_other_address'] = ['nullable','string','max:255','regex:'.\App\Support\PersonDetails::ADDRESS_PATTERN];
        $rules['reporter_age'][] = 'regex:/^[0-9]+$/';
        return $rules;
    }

    private function fieldRules(array $field): array
    {
        $rules = [$field['required'] ? 'required' : 'nullable'];
        $rules = array_merge($rules, match($field['type']) {
            'integer','computed'=>['integer','min:0','max:1000000000'],
            'age'=>['integer','min:0','max:120'],
            'decimal'=>['numeric','min:0','max:999999999999.99'],
            'date'=>['date_format:Y-m-d'], 'time'=>['date_format:H:i'],
            'datetime-local'=>['date_format:Y-m-d\TH:i'],
            'textarea'=>['string','max:20000'], default=>['string','max:255'],
        });
        if (!empty($field['options'])) $rules[] = Rule::in(array_keys($field['options']));
        return $rules;
    }

    public function attributes(): array
    {
        $labels=[];
        foreach (EmergencyRecordForm::sections() as $fields) foreach ($fields as $key=>$field) $labels[$key]=strtolower($field['label']);
        foreach (EmergencyRecordForm::groups() as $key=>$group) foreach ($group['fields'] as $name=>$field) $labels["entries.$key.*.$name"]=$group['title'].' — '.$field['label'];
        return $labels;
    }

    public function messages(): array
    {
        return [
            ...\App\Support\PersonDetails::messages('entries.casualties.*'),
            'reporter_other_address.regex' => 'Please enter a valid address.',
            'reporter_age.regex' => 'Please enter a valid age.',
            'reporter_first_name.required' => 'First name is required.',
            'reporter_first_name.required_with' => 'First name is required.',
            'reporter_first_name.min' => 'First name must contain at least 2 characters.',
            'reporter_first_name.max' => 'First name cannot exceed 50 characters.',
            'reporter_first_name.regex' => 'Numbers and inappropriate special characters are not allowed in the first name.',
            'reporter_middle_name.max' => 'Middle name cannot exceed 50 characters.',
            'reporter_middle_name.regex' => 'Numbers and inappropriate special characters are not allowed in the middle name.',
            'reporter_last_name.required' => 'Last name is required.',
            'reporter_last_name.required_with' => 'Last name is required.',
            'reporter_last_name.min' => 'Last name must contain at least 2 characters.',
            'reporter_last_name.max' => 'Last name cannot exceed 50 characters.',
            'reporter_last_name.regex' => 'Numbers and inappropriate special characters are not allowed in the last name.',
            'reporter_sitio.in' => 'Please select a valid sitio.',
            'reporter_age.integer' => 'Please enter a valid age.',
            'reporter_age.min' => 'Please enter a valid age.',
            'reporter_age.max' => 'Please enter a valid age.',
            'reporter_contact.regex' => 'Please enter a valid Philippine mobile number (09XXXXXXXXX).',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('incident_date') && $this->filled('incident_time')) {
                try {
                    $dt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $this->incident_date.' '.$this->incident_time);
                    if ($dt && $dt->isFuture()) {
                        $validator->errors()->add('incident_time', 'Incident date and time cannot be in the future.');
                    }
                } catch (\Exception $e) {}
            }

            foreach (['roadsBridges'=>'reported_at','powerOutages'=>'interrupted_at','waterSupply'=>'interrupted_at','communicationLines'=>'interrupted_at'] as $group=>$start) {
                $rows=$this->input("entries.$group", []);
                if (!is_array($rows)) continue;
                foreach ($rows as $i=>$row) {
                    if (!is_array($row)) continue;
                    if (!empty($row['restored_at']) && !empty($row[$start]) && is_string($row['restored_at']) && is_string($row[$start]) && $row['restored_at'] < $row[$start]) $validator->errors()->add("entries.$group.$i.restored_at", 'Restoration must be on or after the interruption or report time.');
                }
            }
        });
    }
}
