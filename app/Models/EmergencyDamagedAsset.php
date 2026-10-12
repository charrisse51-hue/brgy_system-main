<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyDamagedAsset extends Model
{
    protected $table = 'emergency_damaged_assets';
    protected $fillable = ['site', 'classification', 'name', 'unit', 'quantity', 'cost', 'remarks'];
    protected $casts = [
            'quantity' => 'decimal:2',
            'cost' => 'decimal:2'
        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

