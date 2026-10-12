<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyCasualty extends Model
{
    protected $table = 'emergency_casualties';
    protected $fillable = ['casualty_type', 'first_name', 'middle_name', 'last_name', 'name', 'age', 'sex', 'address', 'cause', 'remarks'];
    protected $casts = [
            'age' => 'integer'
        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

