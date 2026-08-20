<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use RuntimeException;

class GoogleCalendarController extends Controller
{
    public function __construct(private readonly GoogleCalendarService $google) {}

    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'linked' => (bool) $user->google_calendar_linked,
            'google_email' => $user->google_email,
            'calendar_id' => $user->google_calendar_id ?: 'primary',
            'configured' => filled(config('services.google.client_id')),
        ]);
    }

    public function connect(Request $request)
    {
        try {
            return response()->json([
                'url' => $this->google->authUrl($request->user()),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->away('maxcitas://oauth?google=error&message='.urlencode((string) $request->query('error')));
        }

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $this->google->handleCallback($request->string('code')->toString(), $request->string('state')->toString());
        } catch (\Throwable $e) {
            return redirect()->away('maxcitas://oauth?google=error&message='.urlencode($e->getMessage()));
        }

        return redirect()->away('maxcitas://oauth?google=connected');
    }

    public function disconnect(Request $request)
    {
        $this->google->disconnect($request->user());

        return response()->json([
            'linked' => false,
            'message' => 'Google Calendar desvinculado',
        ]);
    }
}
