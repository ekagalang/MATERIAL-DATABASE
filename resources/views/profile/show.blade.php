@extends('layouts.app')

@section('title', 'Profile Akun')

@section('content')
<style>
    .profile-shell {
        --profile-accent: #891313;
        --profile-accent-soft: #fff3f0;
        --profile-ink: #172033;
        --profile-muted: #6b7280;
        --profile-line: #dde5ef;
        margin: 0 auto;
        display: grid;
        gap: 18px;
        padding-bottom: 0px;
        padding-top: 15px;
    }

    .profile-hero {
        position: relative;
        overflow: hidden;
        padding: 28px;
        border-radius: 28px;
        background:
            radial-gradient(circle at top right, rgba(137, 19, 19, 0.14), transparent 28%),
            linear-gradient(135deg, #fff8f2 0%, #ffffff 48%, #f8fbff 100%);
        border: 1px solid var(--profile-line);
        box-shadow: 0 22px 55px rgba(15, 23, 42, 0.08);
    }

    .profile-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 13px;
        border-radius: 999px;
        background: var(--profile-accent-soft);
        color: var(--profile-accent);
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .profile-title {
        margin: 16px 0 8px;
        font-size: clamp(1.9rem, 3vw, 2.7rem);
        line-height: 0.98;
        font-weight: 900;
        letter-spacing: -0.05em;
        color: var(--profile-ink);
    }

    .profile-copy {
        max-width: 58ch;
        margin: 0;
        color: var(--profile-muted);
        font-size: 0.92rem;
        line-height: 1.75;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: minmax(320px, 0.9fr) minmax(0, 1.1fr);
        gap: 18px;
    }

    .profile-card {
        border-radius: 24px;
        background: #fff;
        border: 1px solid var(--profile-line);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.05);
    }

    .profile-summary {
        padding: 24px;
        display: grid;
        gap: 18px;
        align-content: start;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(250, 243, 236, 0.82) 100%);
    }

    .profile-avatar-wrap {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .profile-avatar {
        width: 74px;
        height: 74px;
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #891313 0%, #d97706 100%);
        color: #fff;
        font-size: 1.65rem;
        font-weight: 900;
        box-shadow: 0 18px 30px rgba(137, 19, 19, 0.22);
    }

    .profile-name {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 850;
        color: var(--profile-ink);
        letter-spacing: -0.03em;
    }

    .profile-email {
        margin: 4px 0 0;
        font-size: 0.88rem;
        color: var(--profile-muted);
    }

    .profile-pill-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .profile-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 800;
        line-height: 1.2;
        background: #f8fafc;
        border: 1px solid var(--profile-line);
        color: #334155;
    }

    .profile-pill.is-role {
        background: var(--profile-accent-soft);
        border-color: #f4c4bf;
        color: var(--profile-accent);
    }

    .profile-meta {
        display: grid;
        gap: 12px;
    }

    .profile-meta-item {
        padding: 14px 16px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid var(--profile-line);
    }

    .profile-meta-label {
        margin: 0 0 4px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--profile-muted);
    }

    .profile-meta-value {
        margin: 0;
        color: var(--profile-ink);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .profile-form-card {
        padding: 24px;
        display: grid;
        gap: 18px;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(248, 251, 255, 0.94) 100%);
    }

    .profile-section-title {
        margin: 0;
        color: var(--profile-ink);
        font-size: 1.02rem;
        font-weight: 850;
        letter-spacing: -0.03em;
    }

    .profile-section-copy {
        margin: 5px 0 0;
        font-size: 0.84rem;
        color: var(--profile-muted);
    }

    .profile-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .profile-field {
        display: grid;
        gap: 6px;
    }

    .profile-field.is-full {
        grid-column: 1 / -1;
    }

    .profile-label {
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--profile-muted);
    }

    .profile-input {
        appearance: none;
        -webkit-appearance: none;
        box-sizing: border-box;
        width: 100%;
        border: 1px solid var(--profile-line);
        border-radius: 16px !important;
        background: #fff;
        padding: 12px 14px;
        color: var(--profile-ink);
        font-size: 0.92rem;
        line-height: 1.4;
        transition: border-color .18s ease, box-shadow .18s ease;
        background-clip: padding-box;
    }

    .profile-input[readonly] {
        background: #f8fafc;
        color: var(--profile-muted);
        cursor: default;
    }

    .profile-input:-webkit-autofill,
    .profile-input:-webkit-autofill:hover,
    .profile-input:-webkit-autofill:focus {
        -webkit-text-fill-color: var(--profile-ink) !important;
        -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
        box-shadow: 0 0 0 1000px #fff inset !important;
        border-radius: 16px !important;
        transition: background-color 9999s ease-out 0s;
    }

    .profile-input:focus {
        outline: none;
        border-color: rgba(137, 19, 19, 0.42);
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.08);
    }

    .profile-note {
        padding: 12px 14px;
        border-radius: 16px;
        background: #fff9ed;
        border: 1px solid #f7dcc0;
        color: #9a3412;
        font-size: 0.8rem;
        line-height: 1.6;
    }

    .profile-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .profile-primary-btn,
    .profile-secondary-btn {
        border-radius: 16px;
        padding: 11px 16px;
        font-size: 0.84rem;
        font-weight: 800;
        transition: transform .16s ease, box-shadow .16s ease;
    }

    .profile-primary-btn {
        border: none;
        background: linear-gradient(135deg, #891313 0%, #b45309 100%);
        color: #fff;
        box-shadow: 0 14px 24px rgba(137, 19, 19, 0.18);
    }

    .profile-secondary-btn {
        border: 1px solid var(--profile-line);
        background: #fff;
        color: var(--profile-ink);
        text-decoration: none;
    }

    .profile-primary-btn:hover,
    .profile-secondary-btn:hover {
        transform: translateY(-1px);
    }

    @media (max-width: 920px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .profile-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="profile-shell">
    <section class="profile-hero">
        <h1 class="profile-title">Profile Akun</h1>
    </section>

    @if (session('success'))
        <div class="alert alert-success mb-0" style="border-radius: 18px; font-size: .85rem;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-0" style="border-radius: 18px; font-size: .84rem;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="profile-grid">
        <aside class="profile-card profile-summary">
            <div class="profile-avatar-wrap">
                <div class="profile-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div>
                    <h2 class="profile-name">{{ $user->name }}</h2>
                    <p class="profile-email">{{ $user->email }}</p>
                </div>
            </div>

            <div class="profile-pill-row">
                @forelse ($user->getRoleNames() as $roleName)
                    <span class="profile-pill is-role">{{ \Illuminate\Support\Str::headline($roleName) }}</span>
                @empty
                    <span class="profile-pill">Tanpa role</span>
                @endforelse
            </div>

            <div class="profile-meta">
                <!-- 
                <div class="profile-meta-item">
                    <p class="profile-meta-label">Arah Masuk Utama</p>
                    <p class="profile-meta-value">{{ $user->preferredLandingRoute() }}</p>
                </div>
                -->
                <div class="profile-meta-item">
                    <p class="profile-meta-label">Status Akses</p>
                    <p class="profile-meta-value">{{ $user->roles->isNotEmpty() ? 'Aktif' : 'Menunggu role dari admin' }}</p>
                </div>
                <div class="profile-meta-item">
                    <p class="profile-meta-label">Terakhir Diperbarui</p>
                    <p class="profile-meta-value">{{ optional($user->updated_at)->format('d M Y, H:i') ?? '-' }}</p>
                </div>
            </div>
        </aside>

        <section class="profile-card profile-form-card">
            <div>
                <h2 class="profile-section-title">Ubah Data Akun</h2>
            </div>

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="profile-form-grid">
                    <div class="profile-field">
                        <label class="profile-label" for="profile-name">Nama</label>
                        <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" class="profile-input" required>
                    </div>

                    <div class="profile-field">
                        <label class="profile-label" for="profile-email">Email</label>
                        <input id="profile-email" type="email" value="{{ $user->email }}" class="profile-input" readonly aria-readonly="true">
                    </div>

                    <div class="profile-field is-full">
                        <div class="profile-note">
                            Jika tidak ingin mengganti password, kosongkan semua field keamanan di bawah ini.
                        </div>
                    </div>

                    <div class="profile-field">
                        <label class="profile-label" for="profile-current-password">Password Saat Ini</label>
                        <input id="profile-current-password" type="password" name="current_password" class="profile-input" autocomplete="current-password">
                    </div>

                    <div class="profile-field">
                        <label class="profile-label" for="profile-password">Password Baru</label>
                        <input id="profile-password" type="password" name="password" class="profile-input" autocomplete="new-password">
                    </div>

                    <div class="profile-field is-full">
                        <label class="profile-label" for="profile-password-confirmation">Konfirmasi Password Baru</label>
                        <input id="profile-password-confirmation" type="password" name="password_confirmation" class="profile-input" autocomplete="new-password">
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="{{ url()->previous() === route('profile.show') ? route('dashboard') : url()->previous() }}" class="profile-secondary-btn">Kembali</a>
                    <button type="submit" class="profile-primary-btn">Simpan Profile</button>
                </div>
            </form>
        </section>
    </section>
</div>
@endsection
