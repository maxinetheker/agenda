<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AvailabilityRange;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    public function assertFits(User $user, Carbon $startsAt, Carbon $endsAt, ?int $ignoreAppointmentId = null): void
    {
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => 'La hora de fin debe ser posterior a la de inicio.',
            ]);
        }

        $ranges = $user->availabilityRanges()
            ->where('is_active', true)
            ->where('weekday', (int) $startsAt->dayOfWeek)
            ->get();

        if ($ranges->isNotEmpty() && ! $this->fitsAnyRange($ranges, $startsAt, $endsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => 'La cita está fuera de tus rangos de disponibilidad para ese día.',
            ]);
        }

        $overlap = Appointment::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelada')
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_at' => 'Ya tienes otra cita en ese horario.',
            ]);
        }
    }

    private function fitsAnyRange($ranges, Carbon $startsAt, Carbon $endsAt): bool
    {
        foreach ($ranges as $range) {
            /** @var AvailabilityRange $range */
            $rangeStart = $startsAt->copy()->setTimeFromTimeString(substr((string) $range->start_time, 0, 8));
            $rangeEnd = $startsAt->copy()->setTimeFromTimeString(substr((string) $range->end_time, 0, 8));

            if ($startsAt->greaterThanOrEqualTo($rangeStart) && $endsAt->lessThanOrEqualTo($rangeEnd)) {
                return true;
            }
        }

        return false;
    }
}
