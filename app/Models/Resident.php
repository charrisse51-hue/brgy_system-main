<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    use HasFactory;

    public function scopeFiltered($query, array $filters)
    {
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            if (preg_match('/^#?RES-0*(\d+)$/i', $search, $match)) {
                $query->where('id', (int) $match[1]);
            } else {
                foreach (preg_split('/\s+/', $search) as $word) {
                    $query->where(function ($query) use ($word) {
                        foreach (['name', 'first_name', 'middle_name', 'last_name', 'suffix', 'address', 'contact_number'] as $column) {
                            $query->orWhere($column, 'like', '%'.$word.'%');
                        }
                        if (ctype_digit($word)) $query->orWhere('id', (int) $word);
                    });
                }
            }
        }
        foreach (['zone', 'civil_status'] as $field) {
            if (!empty($filters[$field])) $query->where($field, $filters[$field]);
        }
        if (!empty($filters['sitio'])) {
            // Include older records saved without the Sitio prefix.
            $name = preg_replace('/^Sitio\s+/i', '', $filters['sitio']);
            $values = array_map('mb_strtolower', [$filters['sitio'], $name, 'Sito '.$name]);
            $query->whereRaw('LOWER(TRIM(sitio_purok)) IN (?, ?, ?)', $values);
        }
        if (!empty($filters['registered_from'])) $query->whereDate('created_at', '>=', $filters['registered_from']);
        if (!empty($filters['registered_to'])) $query->whereDate('created_at', '<=', $filters['registered_to']);
        if (($filters['contact'] ?? '') === 'with') {
            $query->whereNotNull('contact_number')->where('contact_number', '<>', '');
        } elseif (($filters['contact'] ?? '') === 'without') {
            $query->where(fn ($query) => $query->whereNull('contact_number')->orWhere('contact_number', ''));
        }
        switch ($filters['sort'] ?? 'newest') {
            case 'oldest': $query->orderBy('created_at'); break;
            case 'name_asc': $query->orderByRaw("COALESCE(NULLIF(first_name, ''), name) ASC")->orderBy('last_name'); break;
            case 'name_desc': $query->orderByRaw("COALESCE(NULLIF(first_name, ''), name) DESC")->orderByDesc('last_name'); break;
            default: $query->orderByDesc('created_at');
        }
        return $query->orderBy('id');
    }

    protected $fillable = [
        'barangay', 'sitio_purok', 'house_number', 'street', 'subdivision',
        'philsys_card_number', 'birthplace', 'sex', 'religion', 'citizenship', 'ethnicity',
        'educational_attainment', 'school_location', 'labor_force_status', 'occupation',
        'monthly_income', 'income_sources', 'target_groups', 'pwd_classification', 'pwd_other',
        'out_of_school_youth', 'can_read_write', 'immunization_status', 'senior_citizen_number',
        'email', 'blood_type', 'height_cm', 'weight_kg', 'registered_voter',
        'last_year_voted', 'voter_location', 'mother_last_name', 'mother_first_name', 'mother_middle_name',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'address',
        'contact_number',
        'birthdate',
        'civil_status',
        'zone',
        'status',
    ];

    protected $casts = [
        'income_sources' => 'array',
        'target_groups' => 'array',
        'out_of_school_youth' => 'boolean',
        'can_read_write' => 'boolean',
        'registered_voter' => 'boolean',
        'monthly_income' => 'decimal:2',
        'height_cm' => 'decimal:1',
        'weight_kg' => 'decimal:1',
        'last_year_voted' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'birthdate'  => 'datetime',
    ];

    /**
     * Convenient accessor to return the full name constructed from parts.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ]);

        return !empty($parts) ? implode(' ', $parts) : ($this->name ?? '');
    }

    /**
     * Optional belongs-to relation for an associated user account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parse a purok string out of the address if present.
     */
    public function getPurokAttribute(): ?string
    {
        if (!$this->address) {
            return null;
        }

        // Match patterns like "Purok 1", "PUROK 2", "purok 10", etc.
        if (preg_match('/\b(?:purok|puk)\s*\d+\b/i', $this->address, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Get the age of the resident based on birthdate.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birthdate?->age;
    }
}
