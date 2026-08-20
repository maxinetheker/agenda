<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'weekday',
    'start_time',
    'end_time',
    'slot_minutes',
    'is_active',
])]
class AvailabilityRange extends Model
{
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'slot_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'weekday' => $this->weekday,
            'weekday_label' => self::weekdayLabel($this->weekday),
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'slot_minutes' => $this->slot_minutes,
            'is_active' => $this->is_active,
        ];
    }

    public static function weekdayLabel(int $weekday): string
    {
        return match ($weekday) {
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            default => 'Día',
        };
    }
}
