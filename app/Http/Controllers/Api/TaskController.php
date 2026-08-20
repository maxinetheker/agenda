<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTask;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly GoogleCalendarService $google) {}

    public function index(Request $request)
    {
        $query = $request->user()->tasks()->with('contact')->orderBy('due_at');

        if ($request->boolean('pending')) {
            $query->where('completed', false);
        }

        return response()->json([
            'tasks' => $query->get()->map->toApiArray()->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = $request->user();

        if (! empty($data['contact_id'])) {
            abort_unless($user->contacts()->whereKey($data['contact_id'])->exists(), 422, 'Contacto inválido');
        }

        $syncGoogle = $request->boolean('sync_google', $user->google_calendar_linked);
        $task = $user->tasks()->create($data + [
            'synced_to_google' => false,
        ]);

        if ($syncGoogle && $user->google_calendar_linked) {
            $this->pushToGoogle($user, $task);
        }

        return response()->json(['task' => $task->fresh('contact')->toApiArray()], 201);
    }

    public function update(Request $request, AgentTask $task)
    {
        abort_unless($task->user_id === $request->user()->id, 404);
        $task->fill($this->validated($request, false))->save();

        return response()->json(['task' => $task->fresh('contact')->toApiArray()]);
    }

    public function destroy(Request $request, AgentTask $task)
    {
        abort_unless($task->user_id === $request->user()->id, 404);

        if ($task->google_event_id) {
            $this->google->deleteEvent($request->user(), $task->google_event_id);
        }

        $task->delete();

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request, AgentTask $task)
    {
        abort_unless($task->user_id === $request->user()->id, 404);
        $task->forceFill(['completed' => $request->boolean('completed', true)])->save();

        return response()->json(['task' => $task->fresh('contact')->toApiArray()]);
    }

    public function syncGoogle(Request $request, AgentTask $task)
    {
        abort_unless($task->user_id === $request->user()->id, 404);
        $user = $request->user();

        if (! $user->google_calendar_linked) {
            return response()->json(['message' => 'Vincula Google Calendar primero.'], 422);
        }

        $this->pushToGoogle($user, $task);

        return response()->json(['task' => $task->fresh('contact')->toApiArray()]);
    }

    public function unsyncGoogle(Request $request, AgentTask $task)
    {
        abort_unless($task->user_id === $request->user()->id, 404);

        if ($task->google_event_id) {
            $this->google->deleteEvent($request->user(), $task->google_event_id);
        }

        $task->forceFill([
            'google_event_id' => null,
            'synced_to_google' => false,
        ])->save();

        return response()->json(['task' => $task->fresh('contact')->toApiArray()]);
    }

    private function pushToGoogle($user, AgentTask $task): void
    {
        $tz = $user->timezone ?: 'America/Santo_Domingo';
        $start = $task->due_at->copy()->timezone($tz);
        $end = $start->copy()->addMinutes($task->duration_minutes ?: 30);

        if ($task->google_event_id) {
            $this->google->deleteEvent($user, $task->google_event_id);
        }

        $eventId = $this->google->createEvent($user, $this->google->eventPayload(
            summary: 'Tarea MAXCitas · '.$task->title,
            startIso: $start->toIso8601String(),
            endIso: $end->toIso8601String(),
            timezone: $tz,
            description: trim(($task->description ?? '')."\nCliente: ".($task->contact?->name ?? 'Sin asignar')),
        ));

        $task->forceFill([
            'google_event_id' => $eventId,
            'synced_to_google' => filled($eventId),
        ])->save();
    }

    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'contact_id' => ['nullable', 'integer'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'max:40'],
            'due_at' => [$creating ? 'required' : 'sometimes', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'completed' => ['nullable', 'boolean'],
        ]);
    }
}
