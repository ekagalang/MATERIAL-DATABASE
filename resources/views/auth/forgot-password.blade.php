@extends('layouts.auth')

@section('title', 'Lupa Password')
@section('auth_kicker', 'Password Recovery')
@section('auth_brand_title', 'Akses akun bisa dipulihkan tanpa bikin akun baru')
@section('auth_brand_copy', 'Masukkan email akunmu. Jika terdaftar, kami kirimkan link reset password untuk mengatur ulang akses login.')
@section('auth_brand_footer', 'Copyright © ' . now()->year . ' Kanggo')
@section('auth_card_title', 'Lupa Password')
@section('auth_card_copy', 'Kirim link reset ke email akun yang terdaftar di sistem.')

@section('auth_form')
    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
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

        <button type="submit" class="btn btn-auth text-white w-100">Kirim Link Reset</button>
    </form>
@endsection

@section('auth_footer')
    <p class="auth-footer mb-0">
        Sudah ingat password?
        <a href="{{ route('login') }}">Kembali ke login</a>
        @if ($registrationEnabled)
            . Belum punya akun?
            <a href="{{ route('register') }}">Daftar</a>
        @endif
    </p>
@endsection
