<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyInfrastructureDamage extends Model
{
    protected $table = 'emergency_infrastructure_damage';
    protected $fillable = ['site', 'type', 'classification', 'name', 'number_damaged', 'unit', 'quantity', 'status', 'cost', 'remarks'];
    protected $casts = [
            'number_damaged' => 'integer',
            'quantity' => 'decimal:2',
            'cost' => 'decimal:2'
        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

