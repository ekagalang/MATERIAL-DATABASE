@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<style>
    html,
    body {
        overflow: hidden !important;
        height: 100% !important;
        position: relative !important;
    }

    .page-content {
        height: calc(100dvh - 55px);
        overflow-y: hidden !important;
        overflow-x: visible !important;
    }

    .um-viewport {
        box-sizing: border-box;
        width: 100%;
        height: 100%;
        padding-top: 14px;
        padding-bottom: 18px;
        overflow-y: hidden;
        overflow-x: visible;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .um-shell {
        --um-accent: #891313;
        --um-accent-soft: #fff1f2;
        --um-ink: #172033;
        --um-muted: #66758f;
        --um-line: #dbe4f0;
        --um-panel: #ffffff;
        --um-panel-alt: #f7f3ef;
        margin: 0 auto;
        width: 100%;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
        overflow: visible;
        padding-bottom: 0;
    }

    .um-panel {
        background: linear-gradient(180deg, #fffdfb 0%, #ffffff 100%);
        border: 1px solid var(--um-line);
        border-radius: 12px;
        padding: 10px;
    }

    .um-toolbar,
    .um-toolbar-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .um-control-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 10px;
        width: 100%;
        min-width: 0;
    }

    .um-control-row .material-search-form {
        width: 100%;
        min-width: 0;
    }

    .um-register {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 0 5px;
        border-radius: 10px;
        background: var(--um-panel-alt);
        border: none;
        font-size: 0.8rem;
        color: var(--um-ink);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .um-register-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .um-register-dot.is-on {
        background: #10b981;
        box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.14);
    }

    .um-register-dot.is-off {
        background: #94a3b8;
    }

    .um-register form {
        display: inline;
    }

    .um-link-button {
        border: none;
        background: transparent;
        padding: 0;
        color: var(--um-accent);
        font-weight: 800;
        cursor: pointer;
    }

    .um-primary-btn,
    .um-soft-btn,
    .um-action-btn {
        border-radius: 14px;
        font-size: 0.82rem;
        font-weight: 800;
        padding: 10px 14px;
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }

    .um-primary-btn {
        border: none;
        background: var(--um-accent);
        color: #fff;
        box-shadow: 0 12px 20px rgba(137, 19, 19, 0.18);
    }

    .um-soft-btn,
    .um-action-btn {
        border: 1px solid var(--um-line);
        background: #fff;
        color: var(--um-ink);
    }

    .um-action-btn.is-primary {
        border-color: rgba(137, 19, 19, 0.18);
        color: var(--um-accent);
        background: var(--um-accent-soft);
    }

    .um-action-btn.is-danger {
        border-color: #fecaca;
        background: #fff5f5;
        color: #b91c1c;
    }

    .um-primary-btn:hover,
    .um-soft-btn:hover,
    .um-action-btn:hover {
        transform: translateY(-1px);
    }

    .material-search-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 170px auto auto auto;
        align-items: center;
        gap: 8px;
        width: 100%;
        min-width: 0;
        margin: 0;
    }

    .material-search-input {
        flex: 1 1 auto;
        width: auto;
        max-width: none;
        min-width: 180px;
        position: relative;
        padding: 0;
    }

    .material-search-input input {
        width: 100%;
        height: 34px;
        padding: 4px 10px 4px 30px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M11.742 10.344l3.387 3.387-1.398 1.398-3.387-3.387a6 6 0 111.398-1.398zM6.5 11A4.5 4.5 0 106.5 2a4.5 4.5 0 000 9z' fill='%2364748b'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 10px 50%;
        background-size: 14px 14px;
        transition: all 0.2s ease;
    }

    .material-search-input i {
        display: none;
    }

    .material-search-form .btn,
    .um-role-filter-control {
        height: 34px;
        padding: 4px 12px;
        font-size: 13px;
        line-height: 1.1;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .um-role-filter-control {
        min-width: 170px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        color: var(--um-ink);
        flex-shrink: 0;
    }

    .um-field {
        display: grid;
        gap: 6px;
    }

    .um-field-inline {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr);
        align-items: center;
        gap: 10px;
    }

    .um-field-inline .um-label {
        margin: 0;
    }

    .um-label {
        font-size: 0.72rem;
        font-weight: 800;
        color: var(--um-muted);
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .um-input,
    .um-select {
        width: 100%;
        border: 1px solid var(--um-line);
        border-radius: 14px;
        padding: 11px 13px;
        font-size: 0.9rem;
        background: #fff;
        color: var(--um-ink);
        transition: border-color .16s ease, box-shadow .16s ease;
    }

    .um-input:focus,
    .um-select:focus {
        outline: none;
        border-color: rgba(137, 19, 19, 0.45);
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.08);
    }

    .um-table-note {
        color: var(--um-muted);
        font-size: 0.76rem;
    }

    .um-table-frame {
        position: relative;
        overflow: visible;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
    }

    .um-table-frame .table-container {
        position: relative;
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        box-shadow: none !important;
    }

    .um-table-wrap {
        height: 100%;
        background: linear-gradient(180deg, #fffdfb 0%, #ffffff 100%);
        border: 1px solid var(--um-line);
        border-radius: 22px;
        box-shadow: none !important;
    }

    .um-table {
        width: 100%;
        min-width: 980px;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: auto !important;
    }

    .um-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #f8fafc;
        color: var(--um-muted);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 8px 12px !important;
        border: 1px solid #cbd5e1 !important;
        vertical-align: top !important;
        white-space: nowrap;
    }

    .um-table tbody td {
        padding: 2px 8px !important;
        vertical-align: middle;
        border: 1px solid #f1f5f9 !important;
        color: var(--um-ink);
        font-size: 12px !important;
        line-height: 1.3 !important;
        height: 35px !important;
        white-space: nowrap;
    }

    .um-table tbody tr:hover > td {
        background: #fffdfa;
    }

    .um-index {
        width: 46px;
        color: var(--um-muted);
        font-weight: 700;
        font-size: 12px;
        text-align: center;
    }

    .um-user-cell {
        min-width: 180px;
    }

    .um-email-cell {
        min-width: 220px;
    }

    .um-user-line {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .um-avatar {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        background: linear-gradient(135deg, #fff1f2 0%, #fde2e4 100%);
        border: 1px solid #fecaca;
        color: var(--um-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 900;
        flex-shrink: 0;
    }

    .um-user-name {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 850;
        font-size: 12px;
        color: var(--um-ink);
        white-space: nowrap;
    }

    .um-user-email {
        color: var(--um-muted);
        font-size: 11px;
        white-space: nowrap;
    }

    .um-self-badge,
    .um-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 3px 7px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        line-height: 1.2;
    }

    .um-self-badge {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .um-status-badge.is-active {
        background: #ecfdf5;
        color: #047857;
    }

    .um-status-badge.is-pending {
        background: #fff7ed;
        color: #c2410c;
    }

    .um-role-stack {
        display: flex;
        flex-wrap: nowrap;
        gap: 4px;
        max-width: none;
        white-space: nowrap;
    }

    .um-role-chip {
        display: inline-flex;
        align-items: center;
        padding: 3px 7px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid var(--um-line);
        color: #334155;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.2;
        white-space: nowrap;
    }

    .um-role-chip-empty {
        color: var(--um-muted);
        font-style: italic;
        font-size: 11px;
        white-space: nowrap;
    }

    .um-actions {
        width: 72px;
    }

    .um-action-row {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 2px;
        width: 100%;
        white-space: nowrap;
    }

    .um-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 22px;
        padding: 0;
        margin: 0;
        border-radius: 0 !important;
        font-size: 12px;
        line-height: 1;
        font-weight: normal !important;
        border: none !important;
        background: transparent !important;
        color: #0f172a !important;
        box-shadow: none !important;
    }

    .um-action-btn.is-primary {
        color: #b45309 !important;
    }

    .um-action-btn.is-danger {
        color: #b91c1c !important;
    }

    .um-action-btn:hover {
        transform: none;
        background: transparent !important;
        box-shadow: none !important;
    }

    .um-editor-row td,
    .um-create-row td {
        padding: 0 !important;
        height: 0 !important;
        min-height: 0 !important;
        line-height: 0 !important;
        border-top: none !important;
        border-bottom: none !important;
    }

    .um-editor-row td {
        background: #fbfcfe;
    }

    .um-create-row td {
        background: #fffdfa;
    }

    .um-editor {
        padding: 14px;
        border-bottom: 1px solid var(--um-line);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(251, 246, 241, 1) 100%);
        line-height: 1.5;
    }

    .um-editor-grid {
        display: grid;
        gap: 14px;
    }

    .um-field.is-full {
        grid-column: 1 / -1;
    }

    .um-role-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 8px;
    }

    .um-role-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 12px;
        border: 1px solid var(--um-line);
        background: #fff;
        color: var(--um-ink);
        font-size: 0.78rem;
    }

    .um-role-option input {
        accent-color: var(--um-accent);
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .um-role-meta {
        color: var(--um-muted);
        font-size: 0.7rem;
    }

    .um-editor-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .um-editor-actions .um-toolbar-group {
        justify-content: flex-end;
    }

    .um-empty {
        padding: 44px 20px;
        text-align: center;
        color: var(--um-muted);
        font-size: 0.9rem;
    }

    .um-pagination {
        padding: 16px 20px;
        border-top: 1px solid var(--um-line);
        background: #fff;
    }

    .um-table thead th:last-child,
    .um-table tbody td:last-child {
        width: 72px !important;
        min-width: 72px !important;
        max-width: 72px !important;
        text-align: center !important;
    }

    @media (max-width: 900px) {
        .um-viewport {
            height: 100%;
        }

        .um-shell {
            min-height: 0;
        }

        .um-table-frame {
            height: 100%;
        }

        .um-table-wrap {
            height: 100%;
        }

        .um-editor-grid,
        .um-field-inline {
            grid-template-columns: 1fr;
        }

        .um-role-filter-control {
            width: 100%;
        }

        .um-control-row,
        .material-search-form {
            display: flex;
            flex-wrap: wrap;
        }

        .um-register {
            width: 100%;
        }

        .um-toolbar-group,
        .um-editor-actions .um-toolbar-group {
            width: 100%;
        }

        .um-toolbar-group > *,
        .um-editor-actions .um-toolbar-group > * {
            flex: 1;
        }
    }
</style>

<div class="um-viewport">
<div class="um-shell">

    @if ($errors->any())
        <div class="alert alert-danger mb-0" style="border-radius: 18px; font-size: .84rem;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="um-panel">
        <div class="um-control-row">
            <div class="um-register">
                <span class="um-register-dot {{ $registrationEnabled ? 'is-on' : 'is-off' }}"></span>
                <span>Register {{ $registrationEnabled ? 'aktif' : 'nonaktif' }}</span>
                <form action="{{ route('settings.users.registration.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="registration_enabled" value="{{ $registrationEnabled ? '0' : '1' }}">
                    <button type="submit" class="um-link-button">{{ $registrationEnabled ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
            </div>

            <form action="{{ route('settings.users.index') }}" method="GET" class="material-search-form manual-search" data-search-manual="true">
                <div class="material-search-input">
                    <i class="bi bi-search"></i>
                    <input
                        id="user-search"
                        type="text"
                        name="search"
                        data-search-manual="true"
                        value="{{ request('search') }}"
                        placeholder="Cari nama atau email..."
                    >
                </div>
                <select id="user-role" name="role" class="um-role-filter-control">
                    <option value="">Semua role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary-glossy">
                    <i class="bi bi-search"></i> Cari
                </button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-secondary-glossy material-search-reset-btn">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
                <button
                    type="button"
                    class="btn btn-secondary-glossy"
                    data-bs-toggle="collapse"
                    data-bs-target="#user-create-row"
                    aria-expanded="{{ old('form_context') === 'create-user' ? 'true' : 'false' }}"
                    aria-controls="user-create-row"
                >
                    <i class="bi bi-plus-lg"></i> Tambah User
                </button>
            </form>
        </div>
    </section>

    <div class="um-table-frame">
        <div class="um-table-wrap table-container">
            <table class="um-table">
                <thead>
                    <tr>
                        <th class="um-index">No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th class="um-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="um-create-row">
                        <td colspan="6">
                            <div id="user-create-row" class="collapse {{ old('form_context') === 'create-user' ? 'show' : '' }}">
                                <div class="um-editor">
                                    <form action="{{ route('settings.users.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="form_context" value="create-user">
                                        <div class="um-editor-grid">
                                            <div class="um-field um-field-inline">
                                                <label class="um-label">Nama</label>
                                                <input type="text" name="name" value="{{ old('form_context') === 'create-user' ? old('name') : '' }}" class="um-input" required>
                                            </div>
                                            <div class="um-field um-field-inline">
                                                <label class="um-label">Email</label>
                                                <input type="email" name="email" value="{{ old('form_context') === 'create-user' ? old('email') : '' }}" class="um-input" required>
                                            </div>
                                            <div class="um-field um-field-inline">
                                                <label class="um-label">Password</label>
                                                <input type="password" name="password" class="um-input" required>
                                            </div>
                                            <div class="um-field um-field-inline">
                                                <label class="um-label">Konfirmasi</label>
                                                <input type="password" name="password_confirmation" class="um-input" required>
                                            </div>
                                            <div class="um-field is-full">
                                                <label class="um-label">Role Awal</label>
                                                <div class="um-role-grid">
                                                    @foreach ($roles as $role)
                                                        <label class="um-role-option">
                                                            <input
                                                                type="checkbox"
                                                                name="roles[]"
                                                                value="{{ $role->name }}"
                                                                @checked(old('form_context') === 'create-user' && collect(old('roles', []))->contains($role->name))
                                                            >
                                                            <span>
                                                                <strong>{{ $role->name }}</strong><br>
                                                                <span class="um-role-meta">{{ $role->users_count }} user memakai role ini</span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <div class="um-editor-actions">
                                            <span class="um-table-note">User baru akan langsung aktif setelah role dipasang dan disimpan.</span>
                                            <div class="um-toolbar-group">
                                                <button
                                                    type="button"
                                                    class="um-soft-btn"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#user-create-row"
                                                    aria-controls="user-create-row"
                                                >
                                                    Batal
                                                </button>
                                                <button type="submit" class="um-primary-btn">Tambah User</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @forelse ($users as $user)
                        <tr>
                            <td class="um-index">{{ ($users->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="um-user-cell">
                                <div class="um-user-line">
                                    <div class="um-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                    <div>
                                        <div class="um-user-name">
                                            <span>{{ $user->name }}</span>
                                            @if (auth()->id() === $user->id)
                                                <span class="um-self-badge">Akun aktif</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="um-email-cell">
                                <span class="um-user-email">{{ $user->email }}</span>
                            </td>
                            <td>
                                <div class="um-role-stack">
                                    @forelse ($user->roles as $role)
                                        <span class="um-role-chip">{{ $role->name }}</span>
                                    @empty
                                        <span class="um-role-chip-empty">Belum ada role</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <span class="um-status-badge {{ $user->roles->isNotEmpty() ? 'is-active' : 'is-pending' }}">
                                    {{ $user->roles->isNotEmpty() ? 'Aktif' : 'Menunggu akses' }}
                                </span>
                            </td>
                            <td class="um-actions">
                                <div class="um-action-row">
                                    <button
                                        type="button"
                                        class="um-action-btn is-primary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#user-editor-{{ $user->id }}"
                                        aria-expanded="{{ old('editing_user_id') == $user->id ? 'true' : 'false' }}"
                                        aria-controls="user-editor-{{ $user->id }}"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @if (auth()->id() !== $user->id)
                                        <form action="{{ route('settings.users.destroy', $user) }}" method="POST" data-confirm="Hapus user ini?" data-confirm-ok="Hapus" data-confirm-cancel="Batal">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="um-action-btn is-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr class="um-editor-row">
                            <td colspan="6">
                                <div id="user-editor-{{ $user->id }}" class="collapse {{ old('editing_user_id') == $user->id ? 'show' : '' }}">
                                    <div class="um-editor">
                                        <form action="{{ route('settings.users.update', $user) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="editing_user_id" value="{{ $user->id }}">
                                            <div class="um-editor-grid">
                                                <div class="um-field um-field-inline">
                                                    <label class="um-label">Nama</label>
                                                    <input type="text" name="name" value="{{ old('editing_user_id') == $user->id ? old('name', $user->name) : $user->name }}" class="um-input" required>
                                                </div>
                                                <div class="um-field um-field-inline">
                                                    <label class="um-label">Email</label>
                                                    <input type="email" name="email" value="{{ old('editing_user_id') == $user->id ? old('email', $user->email) : $user->email }}" class="um-input" required>
                                                </div>
                                                <div class="um-field um-field-inline">
                                                    <label class="um-label">Password Baru</label>
                                                    <input type="password" name="password" class="um-input" placeholder="Kosongkan jika tidak diubah">
                                                </div>
                                                <div class="um-field um-field-inline">
                                                    <label class="um-label">Konfirmasi</label>
                                                    <input type="password" name="password_confirmation" class="um-input">
                                                </div>
                                                <div class="um-field is-full">
                                                    <label class="um-label">Role</label>
                                                    <div class="um-role-grid">
                                                        @foreach ($roles as $role)
                                                            @php
                                                                $selectedRoles = old('editing_user_id') == $user->id
                                                                    ? collect(old('roles', []))
                                                                    : $user->roles->pluck('name');
                                                            @endphp
                                                            <label class="um-role-option">
                                                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked($selectedRoles->contains($role->name))>
                                                                <span>
                                                                    <strong>{{ $role->name }}</strong><br>
                                                                    <span class="um-role-meta">{{ $role->users_count }} user memakai role ini</span>
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="um-editor-actions">
                                                <span class="um-table-note">Perubahan tersimpan langsung ke user ini.</span>
                                                <div class="um-toolbar-group">
                                                    <button
                                                        type="button"
                                                        class="um-soft-btn"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#user-editor-{{ $user->id }}"
                                                        aria-controls="user-editor-{{ $user->id }}"
                                                    >
                                                        Batal
                                                    </button>
                                                    <button type="submit" class="um-primary-btn">Simpan Perubahan</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="um-empty">Tidak ada user yang cocok dengan filter saat ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="um-pagination">{{ $users->links() }}</div>
        @endif
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.um-create-row, .um-editor-row').forEach(function (row) {
            const collapse = row.querySelector('.collapse');

            if (!collapse) {
                return;
            }

            const syncRowVisibility = function () {
                row.style.display = collapse.classList.contains('show') ? 'table-row' : 'none';
            };

            syncRowVisibility();

            collapse.addEventListener('show.bs.collapse', function () {
                row.style.display = 'table-row';
            });

            collapse.addEventListener('hidden.bs.collapse', function () {
                row.style.display = 'none';
            });
        });
    });
</script>
@endpush
