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
            <div style="background:rgba(124,140,248,0.15);border:1px solid var(--other);color:var(--other);padding:10px 14px;border-radius:8px;font-size:13px;">
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
                <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--muted);margin-top:2px;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" name="remember" value="1" checked style="accent-color:var(--me);">
                    Ingat saya
                </label>
            </div>

            <button type="submit" id="send-btn" style="margin-top:6px;">Masuk via SSO</button>
        </form>

        <div style="text-align:center;font-size:11px;color:var(--muted);font-family:'JetBrains Mono',monospace;">
            Terautentikasi langsung dengan server Cybeat
        </div>
    </div>
</div>
@endsection

