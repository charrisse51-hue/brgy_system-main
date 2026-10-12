<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyPowerOutage extends Model
{
    protected $table = 'emergency_power_outages';
    protected $fillable = ['site', 'type', 'provider', 'interrupted_at', 'restored_at', 'remarks'];
    protected $casts = [

        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

