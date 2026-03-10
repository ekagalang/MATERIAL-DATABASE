@extends('layouts.auth')

@section('title', 'Atur Password Baru')
@section('auth_kicker', 'Set New Password')
@section('auth_brand_title', 'Password baru, akses yang sama.')
@section('auth_brand_copy', 'Atur password baru untuk akunmu. Setelah ini kamu bisa login lagi memakai kredensial yang baru.')
@section('auth_brand_footer', 'Copyright © ' . now()->year . ' Kanggo')
@section('auth_card_title', 'Atur Password Baru')
@section('auth_card_copy', 'Lengkapi email dan password baru untuk menyelesaikan reset.')

@section('auth_form')
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
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
            <label for="password" class="form-label">Password Baru</label>
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
            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="form-control"
                required
                autocomplete="new-password"
            >
        </div>

        <button type="submit" class="btn btn-auth text-white w-100">Simpan Password Baru</button>
    </form>
@endsection

@section('auth_footer')
    <p class="auth-footer mb-0">
        Sudah siap login?
        <a href="{{ route('login') }}">Masuk ke sistem</a>
    </p>
@endsection
