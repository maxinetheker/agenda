<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'contact_id',
    'title',
    'type',
    'starts_at',
    'ends_at',
    'location',
    'indications',
    'status',
    'google_event_id',
    'reminder_sent_at',
])]
class Appointment extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
            'type' => $this->type,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'location' => $this->location,
            'indications' => $this->indications,
            'status' => $this->status,
            'google_event_id' => $this->google_event_id,
            'synced_to_google' => filled($this->google_event_id),
        ];
    }
}
