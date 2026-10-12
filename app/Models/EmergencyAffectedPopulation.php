<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyAffectedPopulation extends Model
{
    protected $table = 'emergency_affected_population';
    protected $fillable = ['site', 'families', 'persons', 'rescued'];
    protected $casts = [
            'families' => 'integer',
            'persons' => 'integer',
            'rescued' => 'integer'
        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

