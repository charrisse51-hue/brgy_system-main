<?php

namespace App\Support;

class PersonDetails
{
    public const NAME_PATTERN = "/^(?=.*[\\p{L}\\p{N}])[\\p{L}\\p{M} '\\x{2019}-]+$/u";
    public const ADDRESS_PATTERN = "/^(?=.*[\\p{L}\\p{N}])[\\p{L}\\p{M}\\p{N} .,\\x{2019}'#()\\/-]+$/u";

    // Legacy names have no reliable middle/surname boundaries. Keep every word.
    public static function names(array $person): array
    {
        if (array_key_exists('first_name', $person) || array_key_exists('last_name', $person)) return $person;
        if (!is_string($person['name'] ?? '')) return $person + ['first_name'=>'', 'middle_name'=>'', 'last_name'=>''];
        $parts = preg_split('/\\s+/u', trim($person['name'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $last = count($parts) > 1 ? array_pop($parts) : '';
        return array_merge($person, ['first_name'=>implode(' ', $parts), 'middle_name'=>'', 'last_name'=>$last]);
    }

    public static function fullName(array $person): string
    {
        return implode(' ', array_filter(array_map(fn ($key) => trim($person[$key] ?? ''), ['first_name','middle_name','last_name']), fn ($value) => $value !== ''));
    }

    public static function rules(string $prefix, bool $required = false): array
    {
        $rules = [];
        foreach (['first_name','middle_name','last_name'] as $key) {
            $presence = $key === 'middle_name' ? 'nullable' : ($required ? 'required' : 'required_with:'.$prefix.'.first_name,'.$prefix.'.middle_name,'.$prefix.'.last_name,'.$prefix.'.age,'.$prefix.'.address');
            $rules[$prefix.'.'.$key] = [$presence, 'nullable', 'string', ...($key === 'middle_name' ? [] : ['min:2']), 'max:50', 'regex:'.self::NAME_PATTERN];
        }
        $rules[$prefix.'.age'] = ['nullable','integer','between:0,120','regex:/^[0-9]+$/'];
        $rules[$prefix.'.address'] = ['nullable','string','max:255','regex:'.self::ADDRESS_PATTERN];
        return $rules;
    }

    public static function messages(string $prefix): array
    {
        $messages = [];
        foreach (['first_name'=>'First name','middle_name'=>'Middle name','last_name'=>'Last name'] as $key=>$label) {
            foreach (['required'=>"$label is required.", 'required_with'=>"$label is required.", 'min'=>"$label must contain at least 2 characters.", 'max'=>"$label cannot exceed 50 characters.", 'regex'=>"$label may contain only letters, spaces, apostrophes, and hyphens."] as $rule=>$message) $messages["$prefix.$key.$rule"] = $message;
        }
        foreach (['integer','between','regex'] as $rule) $messages["$prefix.age.$rule"] = 'Please enter a valid age.';
        foreach (['string','max','regex'] as $rule) $messages["$prefix.address.$rule"] = 'Please enter a valid address (maximum 255 characters).';
        return $messages;
    }
}
