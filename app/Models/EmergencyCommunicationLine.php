<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EmergencyCommunicationLine extends Model
{
    protected $table = 'emergency_communication_lines';
    protected $fillable = ['site', 'telecompany', 'interrupted_at', 'restored_at', 'remarks'];
    protected $casts = [

        ];
    public function emergencyRecord(): BelongsTo { return $this->belongsTo(Alert::class, 'emergency_record_id'); }
}

