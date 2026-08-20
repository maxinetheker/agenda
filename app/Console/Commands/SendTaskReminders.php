<?php

namespace App\Console\Commands;

use App\Models\AgentTask;
use App\Models\Appointment;
use App\Services\FirebaseService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('maxcitas:send-reminders')]
#[Description('Envía recordatorios FCM un día antes de citas y tareas')]
class SendTaskReminders extends Command
{
    public function handle(FirebaseService $firebase): int
    {
        $windowStart = now()->addDay()->startOfDay();
        $windowEnd = now()->addDay()->endOfDay();
        $sent = 0;

        $appointments = Appointment::query()
            ->with(['user', 'contact'])
            ->whereNull('reminder_sent_at')
            ->where('status', '!=', 'cancelada')
            ->whereBetween('starts_at', [$windowStart, $windowEnd])
            ->get();

        foreach ($appointments as $appointment) {
            $contact = $appointment->contact;
            $when = $appointment->starts_at->timezone($appointment->user->timezone ?: 'America/Santo_Domingo')->format('H:i');
            $ok = $firebase->sendToUser(
                $appointment->user,
                'Cita RE/MAX mañana',
                ($contact?->name ?? 'Cliente').' · '.$when.' · '.$appointment->title,
                [
                    'type' => 'appointment',
                    'action' => 'open_client',
                    'contact_id' => (string) ($appointment->contact_id ?? ''),
                    'appointment_id' => (string) $appointment->id,
                    'phone' => (string) ($contact?->phone ?? ''),
                ],
            );

            if ($ok) {
                $appointment->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            }
        }

        $tasks = AgentTask::query()
            ->with(['user', 'contact'])
            ->whereNull('reminder_sent_at')
            ->where('completed', false)
            ->whereBetween('due_at', [$windowStart, $windowEnd])
            ->get();

        foreach ($tasks as $task) {
            $contact = $task->contact;
            $ok = $firebase->sendToUser(
                $task->user,
                'Tarea para mañana',
                ($contact?->name ?? 'Seguimiento').' · '.$task->title,
                [
                    'type' => 'task',
                    'action' => 'open_client',
                    'contact_id' => (string) ($task->contact_id ?? ''),
                    'task_id' => (string) $task->id,
                    'phone' => (string) ($contact?->phone ?? ''),
                ],
            );

            if ($ok) {
                $task->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            }
        }

        $this->info("Recordatorios enviados: {$sent}");

        return self::SUCCESS;
    }
}
