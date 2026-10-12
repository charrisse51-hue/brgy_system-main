<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    public function casualties(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyCasualty::class, 'emergency_record_id');
    }

    public function relatedIncidents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyRelatedIncident::class, 'emergency_record_id');
    }

    public function affectedPopulation(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyAffectedPopulation::class, 'emergency_record_id');
    }

    public function roadsBridges(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyRoadBridge::class, 'emergency_record_id');
    }

    public function powerOutages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyPowerOutage::class, 'emergency_record_id');
    }

    public function waterSupply(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyWaterSupply::class, 'emergency_record_id');
    }

    public function communicationLines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyCommunicationLine::class, 'emergency_record_id');
    }

    public function damagedHouses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyDamagedHouse::class, 'emergency_record_id');
    }

    public function agricultureDamage(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyAgricultureDamage::class, 'emergency_record_id');
    }

    public function infrastructureDamage(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyInfrastructureDamage::class, 'emergency_record_id');
    }

    public function damagedAssets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmergencyDamagedAsset::class, 'emergency_record_id');
    }

    public function validator() { return $this->belongsTo(User::class, 'validated_by'); }
    public function checker() { return $this->belongsTo(User::class, 'checked_by'); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected $fillable = [
        'report_source', 'report_date_time', 'report_type', 'progress_report_no', 'emergency_type', 'location', 'incident_date', 'incident_time', 'actions_taken', 'reporter_name', 'reporter_first_name', 'reporter_middle_name', 'reporter_last_name', 'reporter_age', 'reporter_sitio', 'reporter_other_address', 'reporter_contact', 'reporter_address', 'reporter_relationship', 'released_by', 'date_verified', 'verification_status', 'is_record', 'validated_by', 'checked_by', 'verified_by',
        'title',
        'message',
        'level',
        'is_active',
        'created_by',
    ];

    public function getReporterFirstNameAttribute($value)
    {
        if ($value !== null && $value !== '') return $value;
        if (!empty($this->reporter_name)) {
            $parts = preg_split('/\s+/', trim($this->reporter_name));
            return $parts[0] ?? null;
        }
        return null;
    }

    public function getReporterLastNameAttribute($value)
    {
        if ($value !== null && $value !== '') return $value;
        if (!empty($this->reporter_name)) {
            $parts = preg_split('/\s+/', trim($this->reporter_name));
            return count($parts) > 1 ? end($parts) : null;
        }
        return null;
    }

    public function getReporterSitioAttribute($value)
    {
        if ($value !== null && $value !== '') return $value;
        if (!empty($this->reporter_address)) {
            foreach (array_keys(\App\Support\EmergencyRecordForm::sitios()) as $sitio) {
                if (stripos($this->reporter_address, $sitio) !== false) {
                    return $sitio;
                }
            }
        }
        return null;
    }

    protected $casts = [
        'is_record' => 'boolean',
        'report_date_time' => 'datetime',
        'incident_date' => 'date',
        'date_verified' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
