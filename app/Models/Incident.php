<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    /** @use HasFactory<\Database\Factories\IncidentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'logbook',
        'image_path',
        'type',
        'location',
        'latitude',
        'longitude',
        'status',
        'user_id',
        'description',
        'details',
        'photos',
        'archived',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'logbook' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'photos' => 'array',
            'archived' => 'boolean',
        ];
    }

    /**
     * Get the user that reported the incident.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRecordNumberAttribute(): string
    {
        return 'DR-'.($this->created_at?->format('Y') ?? now()->format('Y')).'-'.str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getComplainantFirstNameAttribute(): ?string
    {
        if (!empty($this->logbook['complainant_first_name'])) {
            return $this->logbook['complainant_first_name'];
        }
        if (!empty($this->logbook['complainant_name'])) {
            $parts = preg_split('/\s+/', trim($this->logbook['complainant_name']));
            return $parts[0] ?? null;
        }
        return null;
    }

    public function getComplainantMiddleNameAttribute(): ?string
    {
        if (!empty($this->logbook['complainant_middle_name'])) {
            return $this->logbook['complainant_middle_name'];
        }
        if (!empty($this->logbook['complainant_name'])) {
            $parts = preg_split('/\s+/', trim($this->logbook['complainant_name']));
            if (count($parts) > 2) {
                return implode(' ', array_slice($parts, 1, -1));
            }
        }
        return null;
    }

    public function getComplainantLastNameAttribute(): ?string
    {
        if (!empty($this->logbook['complainant_last_name'])) {
            return $this->logbook['complainant_last_name'];
        }
        if (!empty($this->logbook['complainant_name'])) {
            $parts = preg_split('/\s+/', trim($this->logbook['complainant_name']));
            return count($parts) > 1 ? end($parts) : null;
        }
        return null;
    }

    public function getSitioAttribute(): ?string
    {
        if (!empty($this->logbook['sitio'])) {
            return $this->logbook['sitio'];
        }
        if (!empty($this->logbook['purok'])) {
            return $this->logbook['purok'];
        }
        return null;
    }
}
