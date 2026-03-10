@extends('layouts.auth')

@section('title', 'Login Admin')
@section('auth_kicker', 'Admin Access')
@section('auth_brand_title', 'Portal Login Database Material dan Perhitungan Proyek.')
@section('auth_brand_copy', 'Akses aplikasi dibatasi ke user yang sudah diberikan role oleh administrator.')
@section('auth_brand_footer', 'Copyright © ' . now()->year . ' Kanggo')
@section('auth_card_title', 'Masuk')
@section('auth_card_copy', 'Gunakan akun admin atau akun yang sudah diberi role oleh admin.')

@section('auth_form')
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control @error('email') is-invalid @enderror"
                required
                autofocus
                autocomplete="username"
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                required
                autocomplete="current-password"
            >
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="auth-links">
            <div class="form-check mb-0">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    class="form-check-input"
                    {{ old('remember') ? 'checked' : '' }}
                >
                <label for="remember" class="form-check-label">Ingat saya</label>
            </div>

            <a href="{{ route('password.request') }}" class="auth-inline-link">Lupa Password?</a>
        </div>

        <button type="submit" class="btn btn-auth text-white w-100">Masuk</button>
    </form>
@endsection

@section('auth_footer')
    <p class="auth-footer mb-0">
        @if ($registrationEnabled)
            Belum punya akun?
            <a href="{{ route('register') }}">Daftar di sini</a>.
        @else
             
        @endif
    </p>
@endsection
