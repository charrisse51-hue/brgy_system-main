<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyRoadBridge extends Model
{
    protected $table = 'emergency_roads_bridges';
    protected $fillable = ['site', 'type', 'name', 'condition', 'reported_at', 'restored_at', 'remarks'];
    protected $casts = [

        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

