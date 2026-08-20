<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'contact_id',
    'title',
    'description',
    'type',
    'due_at',
    'duration_minutes',
    'completed',
    'synced_to_google',
    'google_event_id',
    'reminder_sent_at',
])]
class AgentTask extends Model
{
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed' => 'boolean',
            'synced_to_google' => 'boolean',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'contact_id' => $this->contact_id,
            'contact' => $this->contact ? $this->contact->toApiArray() : null,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'due_at' => $this->due_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'completed' => $this->completed,
            'synced_to_google' => $this->synced_to_google,
            'google_event_id' => $this->google_event_id,
        ];
    }
}
