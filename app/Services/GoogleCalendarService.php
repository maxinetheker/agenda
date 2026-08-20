<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleCalendarService
{
    public const SCOPES = [
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/calendar.events',
        'openid',
        'email',
        'profile',
    ];

    public function authUrl(User $user): string
    {
        $this->assertConfigured();

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => encrypt((string) $user->id),
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
    }

    public function handleCallback(string $code, string $state): User
    {
        $this->assertConfigured();

        $userId = (int) decrypt($state);
        $user = User::query()->findOrFail($userId);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            Log::error('Google OAuth token exchange failed', ['body' => $response->body()]);
            throw new RuntimeException('No se pudo vincular Google Calendar. Revisa las credenciales OAuth.');
        }

        $payload = $response->json();
        $email = $this->fetchGoogleEmail($payload['access_token'] ?? '');

        $user->forceFill([
            'google_access_token' => $payload['access_token'] ?? null,
            'google_refresh_token' => $payload['refresh_token'] ?? $user->google_refresh_token,
            'google_token_expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 3600) - 60),
            'google_calendar_linked' => true,
            'google_calendar_id' => $user->google_calendar_id ?: 'primary',
            'google_email' => $email,
        ])->save();

        return $user->fresh();
    }

    public function disconnect(User $user): void
    {
        if ($user->google_access_token) {
            Http::asForm()->post('https://oauth2.googleapis.com/revoke', [
                'token' => $user->google_access_token,
            ]);
        }

        $user->forceFill([
            'google_calendar_linked' => false,
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_email' => null,
        ])->save();
    }

    public function createEvent(User $user, array $event): ?string
    {
        if (! $user->google_calendar_linked) {
            return null;
        }

        $token = $this->accessToken($user);
        $calendarId = $user->google_calendar_id ?: 'primary';

        $response = Http::withToken($token)
            ->post("https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events", $event);

        if ($response->failed()) {
            Log::warning('Google Calendar create event failed', ['body' => $response->body()]);
            throw new RuntimeException('No se pudo crear el evento en Google Calendar.');
        }

        return $response->json('id');
    }

    public function deleteEvent(User $user, ?string $eventId): void
    {
        if (! $user->google_calendar_linked || blank($eventId)) {
            return;
        }

        $token = $this->accessToken($user);
        $calendarId = $user->google_calendar_id ?: 'primary';

        $response = Http::withToken($token)
            ->delete("https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events/{$eventId}");

        if ($response->failed() && $response->status() !== 404 && $response->status() !== 410) {
            Log::warning('Google Calendar delete event failed', ['body' => $response->body()]);
            throw new RuntimeException('No se pudo eliminar el evento de Google Calendar.');
        }
    }

    public function eventPayload(
        string $summary,
        string $startIso,
        string $endIso,
        string $timezone,
        ?string $description = null,
        ?string $location = null,
    ): array {
        return [
            'summary' => $summary,
            'description' => $description,
            'location' => $location,
            'start' => [
                'dateTime' => $startIso,
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $endIso,
                'timeZone' => $timezone,
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => 1440],
                    ['method' => 'popup', 'minutes' => 30],
                ],
            ],
        ];
    }

    private function accessToken(User $user): string
    {
        if ($user->google_token_expires_at && $user->google_token_expires_at->isFuture() && $user->google_access_token) {
            return $user->google_access_token;
        }

        if (! $user->google_refresh_token) {
            throw new RuntimeException('Google Calendar expiró. Vuelve a vincularlo en Configuración.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $user->google_refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            $user->forceFill(['google_calendar_linked' => false])->save();
            throw new RuntimeException('No se pudo renovar Google Calendar. Vuelve a vincular la cuenta.');
        }

        $payload = $response->json();
        $user->forceFill([
            'google_access_token' => $payload['access_token'],
            'google_token_expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 3600) - 60),
            'google_calendar_linked' => true,
        ])->save();

        return $user->google_access_token;
    }

    private function fetchGoogleEmail(string $accessToken): ?string
    {
        if ($accessToken === '') {
            return null;
        }

        $response = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');

        return $response->successful() ? $response->json('email') : null;
    }

    private function assertConfigured(): void
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            throw new RuntimeException('Google Calendar no está configurado en el backend (.env).');
        }
    }
}
