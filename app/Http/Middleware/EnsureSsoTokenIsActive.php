<?php

namespace App\Http\Middleware;

use App\Services\CybeatSsoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoTokenIsActive
{
    public function __construct(private CybeatSsoService $ssoService) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $request->session()->get('sso_access_token');

        if (is_string($accessToken) && $this->hasExpiredJwt($accessToken)) {
            $refreshToken = $request->session()->get('sso_refresh_token');
            $refreshResult = is_string($refreshToken) && $refreshToken !== ''
                ? $this->ssoService->refresh($refreshToken)
                : ['success' => false, 'retryable' => false];

            if ($refreshResult['success']) {
                $request->session()->put([
                    'sso_access_token' => $refreshResult['access_token'],
                    'sso_refresh_token' => $refreshResult['refresh_token'],
                ]);

                return $next($request);
            }

            if ($refreshResult['retryable']) {
                return $next($request);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }

    private function hasExpiredJwt(string $accessToken): bool
    {
        $parts = explode('.', $accessToken);

        if (count($parts) !== 3) {
            return false;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);
        $claims = $payload === false ? null : json_decode($payload, true);

        if (! is_array($claims)) {
            return true;
        }

        return isset($claims['exp']) && is_numeric($claims['exp']) && (int) $claims['exp'] <= time();
    }
}
