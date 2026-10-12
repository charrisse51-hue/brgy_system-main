<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'username', 'last_login_at', 'can_generate_reports', 'position',
        'name',
        'email',
        'password',
        'role',
        'contact_number',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'auth_version' => 'integer',
            'can_generate_reports' => 'boolean',
        ];
    }

    /**
     * One-to-one relationship to a Resident record.
     * Assumes residents.user_id points back to users.id.
     */
    public function resident()
    {
        return $this->hasOne(Resident::class);
    }
}
