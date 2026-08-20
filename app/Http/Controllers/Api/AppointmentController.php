<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly GoogleCalendarService $google,
    ) {}

    public function index(Request $request)
    {
        $query = $request->user()->appointments()->with('contact')->orderBy('starts_at');

        if ($from = $request->query('from')) {
            $query->where('starts_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->query('to')) {
            $query->where('starts_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return response()->json([
            'appointments' => $query->get()->map->toApiArray()->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = $request->user();
        $starts = Carbon::parse($data['starts_at']);
        $ends = Carbon::parse($data['ends_at']);

        $this->availability->assertFits($user, $starts, $ends);

        if (! empty($data['contact_id'])) {
            abort_unless($user->contacts()->whereKey($data['contact_id'])->exists(), 422, 'Contacto inválido');
        }

        $appointment = $user->appointments()->create($data);

        $this->syncGoogle($user, $appointment);

        return response()->json(['appointment' => $appointment->fresh('contact')->toApiArray()], 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->user_id === $request->user()->id, 404);

        $data = $this->validated($request, false);
        $starts = Carbon::parse($data['starts_at'] ?? $appointment->starts_at);
        $ends = Carbon::parse($data['ends_at'] ?? $appointment->ends_at);
        $this->availability->assertFits($request->user(), $starts, $ends, $appointment->id);

        $appointment->fill($data)->save();
        $this->syncGoogle($request->user(), $appointment, true);

        return response()->json(['appointment' => $appointment->fresh('contact')->toApiArray()]);
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->user_id === $request->user()->id, 404);

        if ($appointment->google_event_id) {
            $this->google->deleteEvent($request->user(), $appointment->google_event_id);
        }

        $appointment->delete();

        return response()->json(['ok' => true]);
    }

    private function syncGoogle($user, Appointment $appointment, bool $replace = false): void
    {
        if (! $user->google_calendar_linked) {
            return;
        }

        try {
            if ($replace && $appointment->google_event_id) {
                $this->google->deleteEvent($user, $appointment->google_event_id);
            }

            $tz = $user->timezone ?: 'America/Santo_Domingo';
            $eventId = $this->google->createEvent($user, $this->google->eventPayload(
                summary: 'RE/MAX · '.$appointment->title,
                startIso: $appointment->starts_at->timezone($tz)->toIso8601String(),
                endIso: $appointment->ends_at->timezone($tz)->toIso8601String(),
                timezone: $tz,
                description: trim(($appointment->indications ?? '')."\nCliente: ".($appointment->contact?->name ?? 'Sin asignar')),
                location: $appointment->location,
            ));

            $appointment->forceFill(['google_event_id' => $eventId])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'contact_id' => ['nullable', 'integer'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'type' => ['nullable', 'string', 'max:40'],
            'starts_at' => [$creating ? 'required' : 'sometimes', 'date'],
            'ends_at' => [$creating ? 'required' : 'sometimes', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'indications' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);
    }
}
