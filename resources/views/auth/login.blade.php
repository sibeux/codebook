@extends('layouts.app')

@section('title', 'Login SSO')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand" style="justify-content:center;margin-bottom:8px;">
                <h1>Codebook</h1>
                <span>SSO Cybeat</span>
            </div>
            <p>Masuk menggunakan akun Cybeat Anda untuk mengakses catatan.</p>
        </div>

        @if (session('status'))
            <div style="background:var(--other-bg);border:1px solid var(--other-border);color:var(--other);padding:10px 14px;border-radius:8px;font-size:13px;">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="nama@email.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
                    <button type="button" id="toggle-password" class="password-toggle-btn" aria-label="Toggle password visibility" title="Tampilkan / Sembunyikan Password">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                            <path d="M17.479 17.499A10.75 10.75 0 0 1 2.062 12.349a1 1 0 0 1 0-.697 10.703 10.703 0 0 1 3.544-4.524"/>
                            <line x1="2" x2="22" y1="2" y2="22"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" id="send-btn" style="margin-top:6px;">Masuk via SSO</button>
        </form>

        <div style="text-align:center;font-size:11px;color:var(--muted);font-family:'JetBrains Mono',monospace;">
            Terautentikasi langsung dengan server Cybeat
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('toggle-password')?.addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeOffIcon = document.getElementById('eye-off-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.style.display = 'none';
            eyeOffIcon.style.display = 'block';
        } else {
            passwordInput.type = 'password';
            eyeIcon.style.display = 'block';
            eyeOffIcon.style.display = 'none';
        }
    });
</script>
@endsection

