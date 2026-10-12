<?php

namespace App\Support;

class IncidentLogbook
{
    public static function sections(): array
    {
        return [
            'Record Information' => [
                'record_number'=>['Record No.', 'readonly'], 'record_date'=>['Date', 'date', true], 'record_time'=>['Time', 'time', true],
            ],
            'Complainant / Reporting Person' => [
                'complainant_name'=>['Full Name', 'text', true], 'complainant_age'=>['Age', 'number'],
                'sitio'=>['Sitio', 'text'], 'other_address'=>['Other Address / Landmark', 'text'],
            ],
            'Respondent / Person Involved' => [],
            'Incident / Disaster Details' => [
                'type'=>['Type of Record', 'select', true], 'incident_date'=>['Date of Incident', 'date', true],
                'incident_time'=>['Time of Incident', 'time', true], 'location'=>['Place of Incident', 'text', true],
                'description'=>['Narrative / Details', 'textarea', true],
            ],
            'Barangay Action / Settlement' => [
                'action_taken'=>['Action Taken', 'textarea'], 'meeting_date'=>['Scheduled Confrontation / Meeting — Date', 'date'],
                'meeting_time'=>['Scheduled Confrontation / Meeting — Time', 'time'], 'remarks'=>['Remarks / Result', 'textarea'],
            ],
            'Recording Official' => [
                'recorded_by'=>['Recorded By', 'readonly'], 'officer'=>['Official / Officer', 'text'], 'position'=>['Position', 'text'],
            ],
        ];
    }
}
