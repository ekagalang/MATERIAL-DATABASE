@extends('layouts.auth')

@section('title', 'Register Akun')
@section('auth_kicker', 'Open Registration')
@section('auth_brand_title', 'Pendaftaran Akun')
@section('auth_brand_copy', 'Setelah mendaftar, akun akan aktif. Hak akses ke modul tetap ditentukan oleh admin melalui role.')
@section('auth_brand_footer', 'Copyright © ' . now()->year . ' Kanggo.')
@section('auth_card_title', 'Daftar Akun')
@section('auth_card_copy', 'Buat akun baru agar admin dapat memberikan role sesuai kebutuhan kerja.')

@section('auth_form')
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nama</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                class="form-control @error('name') is-invalid @enderror"
                required
                autofocus
                autocomplete="name"
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control @error('email') is-invalid @enderror"
                required
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
                autocomplete="new-password"
            >
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="form-control"
                required
                autocomplete="new-password"
            >
        </div>

        <button type="submit" class="btn btn-auth text-white w-100">Daftar</button>
    </form>
@endsection

@section('auth_footer')
    <p class="auth-footer mb-0">
        Sudah punya akun?
        <a href="{{ route('login') }}">Masuk di sini</a>
    </p>
@endsection
