<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyRelatedIncident extends Model
{
    protected $table = 'emergency_related_incidents';
    protected $fillable = ['site', 'type', 'occurred_at', 'description', 'actions_taken', 'remarks', 'status'];
    protected $casts = [

        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

