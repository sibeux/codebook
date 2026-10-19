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
            $response = Http::timeout(10)
                ->acceptJson()
                ->asJson()
                ->post($this->loginUrl, [
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
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke server autentikasi Cybeat: ' . $e->getMessage(),
                'error' => 'network_error',
            ];
        }
    }
}

