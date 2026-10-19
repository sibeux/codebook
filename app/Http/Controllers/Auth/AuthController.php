<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CybeatSsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected CybeatSsoService $ssoService;

    public function __construct(CybeatSsoService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('workspace');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->ssoService->login($credentials['email'], $credentials['password']);

        if (!$result['success']) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $result['message']]);
        }

        // Find existing user in shared database or create if not present
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            $user = User::create([
                'email' => $credentials['email'],
                'name' => explode('@', $credentials['email'])[0],
                'password' => bcrypt($credentials['password']),
                'role' => 'user',
            ]);
        }

        Auth::login($user);

        $request->session()->regenerate();

        session([
            'sso_access_token' => $result['access_token'],
            'sso_refresh_token' => $result['refresh_token'],
        ]);

        return redirect()->intended(route('workspace'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil logout.');
    }
}

