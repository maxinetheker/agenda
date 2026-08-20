<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'office',
    'license_number',
    'photo_url',
    'timezone',
    'role',
    'bio',
    'fcm_token',
    'google_calendar_linked',
    'google_calendar_id',
    'google_access_token',
    'google_refresh_token',
    'google_token_expires_at',
    'google_email',
])]
#[Hidden(['password', 'remember_token', 'google_access_token', 'google_refresh_token', 'fcm_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_calendar_linked' => 'boolean',
            'google_token_expires_at' => 'datetime',
            'google_access_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTask::class);
    }

    public function availabilityRanges(): HasMany
    {
        return $this->hasMany(AvailabilityRange::class);
    }

    public function toAgentArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'office' => $this->office,
            'license_number' => $this->license_number,
            'photo_url' => $this->photo_url,
            'timezone' => $this->timezone,
            'role' => $this->role,
            'bio' => $this->bio,
            'google_calendar_linked' => (bool) $this->google_calendar_linked,
            'google_calendar_id' => $this->google_calendar_id,
            'google_email' => $this->google_email,
        ];
    }
}
