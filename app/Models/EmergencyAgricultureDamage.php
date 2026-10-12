<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyAgricultureDamage extends Model
{
    protected $table = 'emergency_agriculture_damage';
    protected $fillable = ['site', 'classification', 'commodity', 'farmers', 'area', 'area_unit', 'totally_damaged', 'partially_damaged', 'cost', 'remarks'];
    protected $casts = [
            'farmers' => 'integer',
            'area' => 'decimal:2',
            'totally_damaged' => 'decimal:2',
            'partially_damaged' => 'decimal:2',
            'cost' => 'decimal:2'
        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

