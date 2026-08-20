<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $todayStart = now($user->timezone ?: 'America/Santo_Domingo')->startOfDay();
        $todayEnd = $todayStart->copy()->endOfDay();
        $tomorrowStart = $todayStart->copy()->addDay();
        $tomorrowEnd = $tomorrowStart->copy()->endOfDay();

        $todayAppointments = $user->appointments()
            ->with('contact')
            ->whereBetween('starts_at', [$todayStart, $todayEnd])
            ->where('status', '!=', 'cancelada')
            ->orderBy('starts_at')
            ->get();

        $tomorrowAppointments = $user->appointments()
            ->with('contact')
            ->whereBetween('starts_at', [$tomorrowStart, $tomorrowEnd])
            ->where('status', '!=', 'cancelada')
            ->orderBy('starts_at')
            ->get();

        $callTasks = $user->tasks()
            ->with('contact')
            ->where('completed', false)
            ->where('due_at', '<=', $tomorrowEnd)
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        $pipeline = $user->contacts()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return response()->json([
            'agent' => $user->toAgentArray(),
            'kpis' => [
                'contacts' => $user->contacts()->count(),
                'appointments_today' => $todayAppointments->count(),
                'tasks_pending' => $user->tasks()->where('completed', false)->count(),
                'google_calendar_linked' => (bool) $user->google_calendar_linked,
            ],
            'pipeline' => $pipeline,
            'today' => $todayAppointments->map->toApiArray()->values(),
            'tomorrow' => $tomorrowAppointments->map->toApiArray()->values(),
            'calls' => $callTasks->map->toApiArray()->values(),
        ]);
    }
}
