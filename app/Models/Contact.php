<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'phone',
    'email',
    'type',
    'stage',
    'notes',
    'address',
    'property_interest',
    'budget',
    'source',
    'device_contact_id',
    'last_contacted_at',
])]
class Contact extends Model
{
    protected function casts(): array
    {
        return [
            'last_contacted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTask::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'type' => $this->type,
            'stage' => $this->stage,
            'notes' => $this->notes,
            'address' => $this->address,
            'property_interest' => $this->property_interest,
            'budget' => $this->budget,
            'source' => $this->source,
            'device_contact_id' => $this->device_contact_id,
            'last_contacted_at' => $this->last_contacted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
