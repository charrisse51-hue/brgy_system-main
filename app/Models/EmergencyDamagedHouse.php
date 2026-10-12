<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyDamagedHouse extends Model
{
    protected $table = 'emergency_damaged_houses';
    protected $fillable = ['site', 'totally_damaged', 'partially_damaged', 'total', 'cost', 'remarks'];
    protected $casts = [
            'totally_damaged' => 'integer',
            'partially_damaged' => 'integer',
            'total' => 'integer',
            'cost' => 'decimal:2'
        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

