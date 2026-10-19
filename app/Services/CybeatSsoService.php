<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CybeatSsoService
{
    protected string $loginUrl = 'https://cybeat.sibeux.my.id/api/auth/login';

    /**
     * Authenticate user credentials against Cybeat SSO API.
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login(string $email, string $password): array
    {
        try {
            $httpClient = Http::timeout(10)
                ->acceptJson()
                ->asJson();

            // Disable SSL verification on local environment or when configured to avoid Windows cURL 77 cacert.pem errors
            if (app()->environment('local') || !config('services.cybeat.verify_ssl', true)) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->post($this->loginUrl, [
                'email' => $email,
                'password' => $password,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 'success') {
                return [
                    'success' => true,
                    'access_token' => $data['access_token'] ?? null,
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'message' => $data['message'] ?? 'Login berhasil',
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Email atau Password salah.',
                'error' => $data['error'] ?? 'credentials_mismatch',
            ];
        } catch (\Exception $e) {
            Log::error('Cybeat SSO Connection Error: ' . $e->getMessage());

            // If running locally and network is unreachable, fallback to local database verification
            if (app()->environment('local')) {
                $localUser = \App\Models\User::where('email', $email)->first();
                if ($localUser && \Illuminate\Support\Facades\Hash::check($password, $localUser->password)) {
                    return [
                        'success' => true,
                        'access_token' => 'offline-dev-access-token',
                        'refresh_token' => 'offline-dev-refresh-token',
                        'message' => 'Login berhasil (Offline Dev Mode)',
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Gagal terhubung ke server autentikasi Cybeat: ' . $e->getMessage(),
                'error' => 'network_error',
            ];
        }
    }
}

