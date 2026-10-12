<?php
namespace App\Support;
class EmergencyRecordForm
{
    public static function sections(): array { return [
            'Emergency Information' => [
            'report_source' => [
            'label' => 'Report Source',
            'type' => 'text',
            'required' => true
        ],
            'report_date_time' => [
            'label' => 'Report Date & Time',
            'type' => 'datetime-local',
            'required' => true
        ],
            'validated_by' => [
            'label' => 'Validated By',
            'type' => 'staff',
            'required' => false
        ],
            'report_type' => [
            'label' => 'Report Type',
            'type' => 'select',
            'required' => true,
            'options' => [
            'initial' => 'Initial Report',
            'progress' => 'Progress Report',
            'final' => 'Final Report'
        ]
        ],
            'progress_report_no' => [
            'label' => 'Progress Report No.',
            'type' => 'text',
            'required' => false
        ],
            'emergency_type' => [
            'label' => 'Emergency Type',
            'type' => 'select',
            'required' => true
        ],
            'location' => [
            'label' => 'Location',
            'type' => 'text',
            'required' => true
        ],
            'incident_date' => [
            'label' => 'Incident Date',
            'type' => 'date',
            'required' => true
        ],
            'day' => [
            'label' => 'Day',
            'type' => 'readonly',
            'required' => false
        ],
            'incident_time' => [
            'label' => 'Incident Time',
            'type' => 'time',
            'required' => true
        ],
            'message' => [
            'label' => 'Details / Narrative / Underlying Circumstances / Causes',
            'type' => 'textarea',
            'required' => true
        ]
        ],
            'Actions Taken' => [
            'actions_taken' => [
            'label' => 'Actions Taken',
            'type' => 'textarea',
            'required' => false
        ]
        ],
            'Reporter Information' => [
            'reporter_first_name' => [
            'label' => 'First Name',
            'type' => 'text',
            'required' => false
        ],
            'reporter_middle_name' => [
            'label' => 'Middle Name',
            'type' => 'text',
            'required' => false
        ],
            'reporter_last_name' => [
            'label' => 'Last Name',
            'type' => 'text',
            'required' => false
        ],
            'reporter_age' => [
            'label' => 'Age',
            'type' => 'age',
            'required' => false
        ],
            'reporter_sitio' => [
            'label' => 'Sitio',
            'type' => 'select',
            'required' => false,
            'options' => self::sitios()
        ],
            'reporter_other_address' => [
            'label' => 'Other Address / Landmark',
            'type' => 'text',
            'required' => false
        ],
            'reporter_contact' => [
            'label' => 'Contact Number',
            'type' => 'tel',
            'required' => false
        ],
            'reporter_relationship' => [
            'label' => 'Relationship to Incident',
            'type' => 'text',
            'required' => false
        ],
            'released_by' => [
            'label' => 'Report Released By',
            'type' => 'text',
            'required' => false
        ]
        ],
            'Record Verification' => [
            'checked_by' => [
            'label' => 'Checked / Verified By',
            'type' => 'staff',
            'required' => false
        ],
            'date_verified' => [
            'label' => 'Date Verified',
            'type' => 'datetime-local',
            'required' => false
        ],
            'verification_status' => [
            'label' => 'Verification Status',
            'type' => 'select',
            'required' => true,
            'options' => [
            'pending' => 'Pending',
            'verified' => 'Verified'
        ]
        ]
        ]
        ]; }
    public static function groups(): array { return [
            'casualties' => [
            'title' => 'Casualties',
            'table' => 'emergency_casualties',
            'model' => 'EmergencyCasualty',
            'fields' => [
            'casualty_type' => [
            'label' => 'Category',
            'type' => 'select',
            'required' => true,
            'options' => [
            'dead_drowned' => 'Dead / Drowned',
            'injured' => 'Injured',
            'missing' => 'Missing'
        ]
        ],
            'first_name' => ['label'=>'First Name', 'type'=>'text', 'required'=>true],
            'middle_name' => ['label'=>'Middle Name', 'type'=>'text', 'required'=>false],
            'last_name' => ['label'=>'Last Name', 'type'=>'text', 'required'=>true],
            'age' => [
            'label' => 'Age',
            'type' => 'age',
            'required' => false
        ],
            'address' => [
            'label' => 'Address',
            'type' => 'text',
            'required' => false
        ],
            'sex' => [
            'label' => 'Sex',
            'type' => 'select',
            'required' => false,
            'options' => [
            'Male' => 'Male',
            'Female' => 'Female'
        ]
        ],
            'cause' => [
            'label' => 'Cause',
            'type' => 'text',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks / Disposition',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ],
            'relatedIncidents' => [
            'title' => 'Related Incidents',
            'table' => 'emergency_related_incidents',
            'model' => 'EmergencyRelatedIncident',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'type' => [
            'label' => 'Type of Incident',
            'type' => 'text',
            'required' => false
        ],
            'occurred_at' => [
            'label' => 'Date and Time of Occurrence',
            'type' => 'datetime-local',
            'required' => false
        ],
            'description' => [
            'label' => 'Description',
            'type' => 'textarea',
            'required' => false
        ],
            'actions_taken' => [
            'label' => 'Actions Taken',
            'type' => 'textarea',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ],
            'status' => [
            'label' => 'Status',
            'type' => 'text',
            'required' => false
        ]
        ]
        ],
            'affectedPopulation' => [
            'title' => 'Affected Population',
            'table' => 'emergency_affected_population',
            'model' => 'EmergencyAffectedPopulation',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'families' => [
            'label' => 'Number of Affected Families',
            'type' => 'integer',
            'required' => false
        ],
            'persons' => [
            'label' => 'Number of Affected Persons',
            'type' => 'integer',
            'required' => false
        ],
            'rescued' => [
            'label' => 'Rescued Persons',
            'type' => 'integer',
            'required' => false
        ]
        ]
        ],
            'roadsBridges' => [
            'title' => 'Status of Roads and Bridges',
            'table' => 'emergency_roads_bridges',
            'model' => 'EmergencyRoadBridge',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'type' => [
            'label' => 'Type',
            'type' => 'select',
            'required' => false,
            'options' => [
            'Road' => 'Road',
            'Bridge' => 'Bridge'
        ]
        ],
            'name' => [
            'label' => 'Name / Location',
            'type' => 'text',
            'required' => false
        ],
            'condition' => [
            'label' => 'Road / Bridge Condition',
            'type' => 'text',
            'required' => false
        ],
            'reported_at' => [
            'label' => 'Date and Time Reported',
            'type' => 'datetime-local',
            'required' => false
        ],
            'restored_at' => [
            'label' => 'Date and Time Restored / Passable',
            'type' => 'datetime-local',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ],
            'powerOutages' => [
            'title' => 'Power Interruption / Outage',
            'table' => 'emergency_power_outages',
            'model' => 'EmergencyPowerOutage',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'type' => [
            'label' => 'Type of Interruption / Damage',
            'type' => 'text',
            'required' => false
        ],
            'provider' => [
            'label' => 'Service Provider',
            'type' => 'text',
            'required' => false
        ],
            'interrupted_at' => [
            'label' => 'Date and Time of Interruption / Outage',
            'type' => 'datetime-local',
            'required' => false
        ],
            'restored_at' => [
            'label' => 'Date and Time Restored',
            'type' => 'datetime-local',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ],
            'waterSupply' => [
            'title' => 'Water Supply',
            'table' => 'emergency_water_supply',
            'model' => 'EmergencyWaterSupply',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'type' => [
            'label' => 'Type / Status of Water Supply',
            'type' => 'text',
            'required' => false
        ],
            'provider' => [
            'label' => 'Service Provider',
            'type' => 'text',
            'required' => false
        ],
            'interrupted_at' => [
            'label' => 'Date and Time of Interruption / Outage',
            'type' => 'datetime-local',
            'required' => false
        ],
            'restored_at' => [
            'label' => 'Date and Time Restored',
            'type' => 'datetime-local',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ],
            'communicationLines' => [
            'title' => 'Communication Lines',
            'table' => 'emergency_communication_lines',
            'model' => 'EmergencyCommunicationLine',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'telecompany' => [
            'label' => 'Telecommunication Company',
            'type' => 'text',
            'required' => false
        ],
            'interrupted_at' => [
            'label' => 'Date and Time of Interruption / Outage',
            'type' => 'datetime-local',
            'required' => false
        ],
            'restored_at' => [
            'label' => 'Date and Time Restored',
            'type' => 'datetime-local',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ],
            'damagedHouses' => [
            'title' => 'Damaged Houses',
            'table' => 'emergency_damaged_houses',
            'model' => 'EmergencyDamagedHouse',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'totally_damaged' => [
            'label' => 'Totally Damaged',
            'type' => 'integer',
            'required' => false
        ],
            'partially_damaged' => [
            'label' => 'Partially Damaged',
            'type' => 'integer',
            'required' => false
        ],
            'total' => [
            'label' => 'Total',
            'type' => 'computed',
            'required' => false
        ],
            'cost' => [
            'label' => 'Estimated Amount / Cost (PHP)',
            'type' => 'decimal',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ],
            'agricultureDamage' => [
            'title' => 'Damage and Losses to Agriculture',
            'table' => 'emergency_agriculture_damage',
            'model' => 'EmergencyAgricultureDamage',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'classification' => [
            'label' => 'Classification / Type of Agriculture',
            'type' => 'text',
            'required' => false
        ],
            'commodity' => [
            'label' => 'Type of Crop / Commodity',
            'type' => 'text',
            'required' => false
        ],
            'farmers' => [
            'label' => 'Number of Farmers Affected',
            'type' => 'integer',
            'required' => false
        ],
            'area' => [
            'label' => 'Area Affected / Damaged',
            'type' => 'decimal',
            'required' => false
        ],
            'area_unit' => [
            'label' => 'Area Unit (e.g. hectares)',
            'type' => 'text',
            'required' => false
        ],
            'totally_damaged' => [
            'label' => 'Totally Damaged',
            'type' => 'decimal',
            'required' => false
        ],
            'partially_damaged' => [
            'label' => 'Partially Damaged',
            'type' => 'decimal',
            'required' => false
        ],
            'cost' => [
            'label' => 'Estimated Loss / Cost (PHP)',
            'type' => 'decimal',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ],
            'infrastructureDamage' => [
            'title' => 'Damage to Infrastructure',
            'table' => 'emergency_infrastructure_damage',
            'model' => 'EmergencyInfrastructureDamage',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'type' => [
            'label' => 'Type of Infrastructure',
            'type' => 'text',
            'required' => false
        ],
            'classification' => [
            'label' => 'Classification',
            'type' => 'text',
            'required' => false
        ],
            'name' => [
            'label' => 'Infrastructure Name / Description',
            'type' => 'text',
            'required' => false
        ],
            'number_damaged' => [
            'label' => 'Number Damaged',
            'type' => 'integer',
            'required' => false
        ],
            'unit' => [
            'label' => 'Unit',
            'type' => 'text',
            'required' => false
        ],
            'quantity' => [
            'label' => 'Quantity',
            'type' => 'decimal',
            'required' => false
        ],
            'status' => [
            'label' => 'Status',
            'type' => 'text',
            'required' => false
        ],
            'cost' => [
            'label' => 'Cost of Damage (PHP)',
            'type' => 'decimal',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ],
            'damagedAssets' => [
            'title' => 'Other Damaged Assets',
            'table' => 'emergency_damaged_assets',
            'model' => 'EmergencyDamagedAsset',
            'fields' => [
            'site' => [
            'label' => 'Site',
            'type' => 'text',
            'required' => true
        ],
            'classification' => [
            'label' => 'Classification',
            'type' => 'text',
            'required' => false
        ],
            'name' => [
            'label' => 'Name of Asset',
            'type' => 'text',
            'required' => false
        ],
            'unit' => [
            'label' => 'Unit',
            'type' => 'text',
            'required' => false
        ],
            'quantity' => [
            'label' => 'Quantity',
            'type' => 'decimal',
            'required' => false
        ],
            'cost' => [
            'label' => 'Cost of Damage (PHP)',
            'type' => 'decimal',
            'required' => false
        ],
            'remarks' => [
            'label' => 'Remarks',
            'type' => 'textarea',
            'required' => false
        ]
        ]
        ]
        ]; }
    public static function types(): array
    {
        $types = ['fire'=>'Fire', 'accident'=>'Accident', 'flood'=>'Flood', 'medical'=>'Medical Emergency', 'others'=>'Others'];
        foreach (\App\Models\Alert::query()->whereNotNull('emergency_type')->distinct()->pluck('emergency_type') as $type) {
            if ($type !== '') $types[$type] ??= ucfirst($type);
        }
        return $types;
    }

    public static function sitios(): array
    {
        return [
            'Sitio Cabuaan' => 'Sitio Cabuaan',
            'Sitio Camanggaan' => 'Sitio Camanggaan',
            'Sitio Gonzales' => 'Sitio Gonzales',
            'Sitio Basasong' => 'Sitio Basasong',
            'Sitio Doyong Centro' => 'Sitio Doyong Centro',
            'Sitio Baba' => 'Sitio Baba',
            'Sitio Central' => 'Sitio Central',
        ];
    }
}

