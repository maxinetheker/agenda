<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (blank($user->fcm_token)) {
            Log::info('FCM omitido: el agente no tiene token de dispositivo', ['user_id' => $user->id]);

            return false;
        }

        return $this->send($user->fcm_token, $title, $body, $data);
    }

    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $credentials = $this->credentials();
        if ($credentials === null) {
            Log::warning('FCM omitido: falta storage/app/firebase/service-account.json');

            return false;
        }

        $accessToken = $this->googleAccessToken($credentials);
        if ($accessToken === null) {
            return false;
        }

        $projectId = $credentials['project_id'];
        $payloadData = [];
        foreach ($data as $key => $value) {
            $payloadData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $payloadData,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'click_action' => 'OPEN_CLIENT_DETAIL',
                            'channel_id' => 'maxcitas_reminders',
                            'sound' => 'default',
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::warning('FCM falló', ['body' => $response->body()]);

            return false;
        }

        return true;
    }

    private function credentials(): ?array
    {
        $path = storage_path('app/firebase/service-account.json');
        if (! is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : null;
    }

    private function googleAccessToken(array $credentials): ?string
    {
        $now = time();
        $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->b64(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header.'.'.$claims;
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if ($privateKey === false) {
            Log::error('No se pudo leer la llave privada de Firebase');

            return null;
        }

        openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.$this->b64($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            Log::warning('No se pudo obtener token FCM', ['body' => $response->body()]);

            return null;
        }

        return $response->json('access_token');
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
