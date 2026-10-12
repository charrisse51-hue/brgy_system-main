<?php

namespace App\Http\Requests;

use App\Support\ResidentForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResidentRecordRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        $rules = [];
        foreach (ResidentForm::fields() as $name => $field) {
            $type = $field['type'] ?? 'text';
            $rule = [!empty($field['required']) ? 'required' : 'nullable'];
            if ($type === 'checkboxes') {
                $rule[] = 'array';
                $rule[] = 'max:'.count($field['options']);
                $rules[$name.'.*'] = ['string','distinct', Rule::in($field['options'])];
            } elseif ($type === 'select') {
                // Retain compatibility with existing free-text sitio records.
                if ($name === 'sitio_purok') {
                    $rule[] = 'string';
                    $rule[] = 'max:255';
                } else {
                    $rule[] = Rule::in(array_keys($field['options']));
                }
            } elseif ($type === 'number') {
                $rule[] = $name === 'last_year_voted' ? 'integer' : 'numeric';
                $rule[] = 'min:'.$field['min'];
                $rule[] = 'max:'.$field['max'];
            } elseif ($type === 'date') {
                $rule[] = 'date_format:Y-m-d';
                $rule[] = 'before_or_equal:today';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:'.($field['max'] ?? 255);
                if ($type === 'email') $rule[] = 'email';
            }
            $rules[$name] = $rule;
        }
        $pwd = in_array('PWD', (array)$this->input('target_groups', []), true);
        $rules['pwd_classification'][] = Rule::requiredIf($pwd);
        $rules['pwd_other'][] = Rule::requiredIf($pwd && $this->input('pwd_classification') === 'Others');
        return $rules;
    }

    public function attributes(): array
    {
        return array_map(fn ($field) => $field['label'], ResidentForm::fields());
    }

    public function residentData(): array
    {
        $data = $this->validated();
        // Legacy clients may omit new fields; only the expanded form resets unchecked arrays.
        if ($this->input('_resident_form') === 'individual') {
            $data['income_sources'] = $data['income_sources'] ?? [];
            $data['target_groups'] = $data['target_groups'] ?? [];
            $groups = $data['target_groups'];
            if (!in_array('PWD', $groups, true)) $data['pwd_classification'] = $data['pwd_other'] = null;
            elseif (($data['pwd_classification'] ?? '') !== 'Others') $data['pwd_other'] = null;
            if (!in_array('Senior Citizen', $groups, true)) $data['senior_citizen_number'] = null;
            if (empty($data['registered_voter'])) $data['last_year_voted'] = $data['voter_location'] = null;
        }
        $data['name'] = implode(' ', array_filter(array_map(fn ($field) => trim($data[$field] ?? ''), ['first_name','middle_name','last_name','suffix'])));
        $structured = array_filter(array_map(fn ($field) => trim($data[$field] ?? ''), ['house_number','street','subdivision','sitio_purok']));
        if ($structured) {
            $structured[] = $data['barangay'] ?? 'Doyong';
            $data['address'] = implode(', ', array_filter($structured));
        } elseif (empty($data['address'])) {
            $data['address'] = $this->route('resident')?->address ?? ($data['barangay'] ?? 'Doyong');
        }
        return $data;
    }
}
