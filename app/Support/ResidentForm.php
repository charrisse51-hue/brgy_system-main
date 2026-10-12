<?php

namespace App\Support;

class ResidentForm
{
    public const CIVIL_STATUSES = ['single', 'married', 'annulled', 'separated', 'divorced', 'widowed', 'live-in'];

    public static function sections(): array
    {
        $yesNo = ['1' => 'Yes', '0' => 'No'];
        $select = fn ($label, $options) => ['label'=>$label, 'type'=>'select', 'options'=>array_combine($options, $options)];
        return [
            'Address' => [
                'barangay'=>['label'=>'Barangay', 'default'=>'Doyong'],
                'house_number'=>['label'=>'House Number'],
                'street'=>['label'=>'Street'],
                'subdivision'=>['label'=>'Subdivision / Village'],
                'sitio_purok'=>['label'=>'Sitio', 'type'=>'select', 'options'=>[
                    'Sitio Cabuaan'=>'Sitio Cabuaan',
                    'Sitio Camanggaan'=>'Sitio Camanggaan',
                    'Sitio Gonzales'=>'Sitio Gonzales',
                    'Sitio Basasong'=>'Sitio Basasong',
                    'Sitio Doyong Centro'=>'Sitio Doyong Centro',
                    'Sitio Baba'=>'Sito Baba',
                    'Sitio Central'=>'Sitio Central',
                ]],
                'address'=>['label'=>'Existing / alternative address', 'help'=>'For older records, keep the original address or replace it by completing the structured address above.'],
            ],
            'Personal Information' => [
                'philsys_card_number'=>['label'=>'PhilSys Card Number', 'max'=>50],
                'first_name'=>['label'=>'First Name', 'required'=>true, 'max'=>100],
                'middle_name'=>['label'=>'Middle Name', 'max'=>100],
                'last_name'=>['label'=>'Last Name', 'required'=>true, 'max'=>100],
                'suffix'=>['label'=>'Suffix', 'max'=>50],
                'birthdate'=>['label'=>'Birthdate', 'type'=>'date', 'max'=>date('Y-m-d')],
                'birthplace'=>['label'=>'Birthplace'],
                'sex'=>$select('Sex', ['Male','Female']),
                'religion'=>['label'=>'Religion'],
                'citizenship'=>['label'=>'Citizenship', 'default'=>'Filipino'],
                'ethnicity'=>['label'=>'Ethnicity'],
                'civil_status'=>$select('Civil Status', self::CIVIL_STATUSES),
                'educational_attainment'=>$select('Highest Educational Attainment', ['Daycare','No formal education','Elementary undergraduate','Elementary graduate','High school undergraduate','High school graduate','Senior high school undergraduate','Senior high school graduate','Vocational / Technical','College undergraduate','College graduate','Postgraduate']),
                'school_location'=>['label'=>'School Location'],
                'labor_force_status'=>$select('Labor Force Status', ['Employed','Unemployed','Not in the labor force']),
                'occupation'=>['label'=>'Occupation'],
                'monthly_income'=>['label'=>'Monthly Income (PHP)', 'type'=>'number', 'min'=>0, 'max'=>9999999999.99, 'step'=>'0.01'],
                'income_sources'=>['label'=>'Source of Income', 'type'=>'checkboxes', 'options'=>['Salary/Wage','Pension','Business','Rentals','Remittance','Shared Income']],
            ],
            'Vulnerable / Target Groups' => [
                'target_groups'=>['label'=>'Vulnerable / Target Groups', 'type'=>'checkboxes', 'options'=>['OFW','PWD','Migrant','Solo Parent','IP','Senior Citizen','4Ps']],
                'pwd_classification'=>array_merge($select('PWD Classification', ['Psychosocial','Mental / Intellectual','Learning','Visual','Orthopedic / Physical','Communication','Chronic Illness','Rare Disease','Others']), ['condition'=>'pwd']),
                'pwd_other'=>['label'=>'Specify Other PWD Classification', 'condition'=>'pwd_other'],
                'out_of_school_youth'=>['label'=>'Out-of-School Youth', 'type'=>'select', 'options'=>$yesNo],
                'can_read_write'=>['label'=>'Can Read and Write', 'type'=>'select', 'options'=>$yesNo],
                'immunization_status'=>$select('Immunization Status', ['Not Immunized','Full-Private','Full-Public','Full-Mixed','Partial-Private','Partial-Public','Partial-Mixed']),
                'senior_citizen_number'=>['label'=>'Senior Citizen Number', 'condition'=>'senior', 'max'=>100],
            ],
            'Contact & Identity' => [
                'email'=>['label'=>'Email Address', 'type'=>'email'],
                'contact_number'=>['label'=>'Contact Number', 'type'=>'tel', 'max'=>20],
                'blood_type'=>$select('Blood Type', ['O+','O-','A+','A-','B+','B-','AB+','AB-']),
                'height_cm'=>['label'=>'Height (cm)', 'type'=>'number', 'min'=>1, 'max'=>300, 'step'=>'0.1'],
                'weight_kg'=>['label'=>'Weight (kg)', 'type'=>'number', 'min'=>0.1, 'max'=>1000, 'step'=>'0.1'],
                'registered_voter'=>['label'=>'Registered Voter', 'type'=>'select', 'options'=>$yesNo],
                'last_year_voted'=>['label'=>'Last Year Voted', 'type'=>'number', 'min'=>1900, 'max'=>(int)date('Y'), 'step'=>1, 'condition'=>'voter'],
                'voter_location'=>['label'=>'Location Registered', 'condition'=>'voter'],
                'mother_last_name'=>['label'=>"Mother’s Maiden Last Name", 'max'=>100],
                'mother_first_name'=>['label'=>"Mother’s First Name", 'max'=>100],
                'mother_middle_name'=>['label'=>"Mother’s Middle Name", 'max'=>100],
            ],
        ];
    }

    public static function fields(): array
    {
        return array_merge(...array_values(self::sections()));
    }
}
