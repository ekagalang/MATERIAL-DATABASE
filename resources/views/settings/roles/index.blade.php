@extends('layouts.app')

@section('title', 'Roles & Permissions')

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

    .rm-viewport {
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

    .rm-shell {
        --rm-accent: #891313;
        --rm-accent-soft: #fff1f2;
        --rm-ink: #172033;
        --rm-muted: #66758f;
        --rm-line: #dbe4f0;
        --rm-panel: #ffffff;
        --rm-panel-alt: #f7f3ef;
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

    .rm-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(320px, .9fr);
        gap: 16px;
    }

    .rm-hero-card,
    .rm-panel {
        background: linear-gradient(180deg, #fffdfb 0%, #ffffff 100%);
        border: 1px solid var(--rm-line);
        border-radius: 12px;
    }

    .rm-hero-card {
        padding: 22px 24px;
        position: relative;
        overflow: hidden;
    }

    .rm-hero-card::after {
        content: '';
        position: absolute;
        inset: auto auto -90px -60px;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(137, 19, 19, 0.12) 0%, rgba(137, 19, 19, 0) 72%);
        pointer-events: none;
    }

    .rm-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: var(--rm-accent-soft);
        color: var(--rm-accent);
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .rm-title {
        margin: 16px 0 8px;
        font-size: clamp(1.6rem, 2vw, 2.2rem);
        line-height: 1.02;
        font-weight: 900;
        letter-spacing: -0.04em;
        color: var(--rm-ink);
    }

    .rm-subtitle {
        max-width: 62ch;
        margin: 0;
        color: var(--rm-muted);
        font-size: 0.92rem;
        line-height: 1.7;
    }

    .rm-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .rm-stat {
        padding: 18px;
        border-radius: 18px;
        background: var(--rm-panel);
        border: 1px solid var(--rm-line);
        min-height: 118px;
        display: grid;
        gap: 8px;
        align-content: start;
    }

    .rm-stat-label {
        font-size: 0.73rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--rm-muted);
        font-weight: 800;
    }

    .rm-stat-value {
        font-size: 2rem;
        line-height: 1;
        letter-spacing: -0.05em;
        font-weight: 900;
        color: var(--rm-ink);
    }

    .rm-stat-note {
        color: var(--rm-muted);
        font-size: 0.82rem;
        line-height: 1.55;
    }

    .rm-panel {
        padding: 10px;
    }

    .rm-toolbar-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .rm-section-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 850;
        color: var(--rm-ink);
        letter-spacing: -0.03em;
    }

    .rm-section-copy {
        margin: 4px 0 0;
        color: var(--rm-muted);
        font-size: 0.82rem;
    }

    .rm-primary-btn,
    .rm-soft-btn,
    .rm-action-btn {
        border-radius: 14px;
        font-size: 0.82rem;
        font-weight: 800;
        padding: 10px 14px;
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }

    .rm-primary-btn {
        border: none;
        background: var(--rm-accent);
        color: #fff;
        box-shadow: 0 12px 20px rgba(137, 19, 19, 0.18);
    }

    .rm-soft-btn,
    .rm-action-btn {
        border: 1px solid var(--rm-line);
        background: #fff;
        color: var(--rm-ink);
    }

    .rm-action-btn.is-primary {
        border-color: rgba(137, 19, 19, 0.18);
        color: var(--rm-accent);
        background: var(--rm-accent-soft);
    }

    .rm-action-btn.is-danger {
        border-color: #fecaca;
        background: #fff5f5;
        color: #b91c1c;
    }

    .rm-primary-btn:hover,
    .rm-soft-btn:hover,
    .rm-action-btn:hover {
        transform: translateY(-1px);
    }

    .rm-filter-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(240px, .8fr) auto;
        gap: 12px;
        align-items: end;
    }

    .rm-field {
        display: grid;
        gap: 6px;
    }

    .rm-field-inline {
        display: grid;
        grid-template-columns: 110px minmax(0, 1fr);
        align-items: center;
        gap: 10px;
    }

    .rm-field-inline .rm-label {
        margin: 0;
    }

    .rm-label {
        font-size: 0.72rem;
        font-weight: 800;
        color: var(--rm-muted);
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .rm-input,
    .rm-select {
        width: 100%;
        border: 1px solid var(--rm-line);
        border-radius: 14px;
        padding: 11px 13px;
        font-size: 0.9rem;
        background: #fff;
        color: var(--rm-ink);
        transition: border-color .16s ease, box-shadow .16s ease;
    }

    .rm-input:focus,
    .rm-select:focus {
        outline: none;
        border-color: rgba(137, 19, 19, 0.45);
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.08);
    }

    .rm-filter-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        align-items: center;
    }

    .material-search-form {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        flex-wrap: wrap;
        width: 100%;
        min-width: 0;
        margin: 0;
    }

    .material-search-input {
        flex: 1 1 320px;
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

    .material-search-form .btn {
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

    .rm-table-note {
        color: var(--rm-muted);
        font-size: 0.76rem;
    }

    .rm-table-frame {
        position: relative;
        overflow: visible;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
    }

    .rm-table-frame .table-container {
        position: relative;
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        box-shadow: none !important;
    }

    .rm-table-wrap {
        height: 100%;
        background: linear-gradient(180deg, #fffdfb 0%, #ffffff 100%);
        border: 1px solid var(--rm-line);
        border-radius: 22px;
        box-shadow: none !important;
    }

    .rm-table {
        width: 100%;
        min-width: 1100px;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: auto !important;
    }

    .rm-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #f8fafc;
        color: var(--rm-muted);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 8px 12px !important;
        border: 1px solid #cbd5e1 !important;
        vertical-align: top !important;
        white-space: nowrap;
    }

    .rm-table tbody td {
        padding: 2px 8px !important;
        vertical-align: middle;
        border: 1px solid #f1f5f9 !important;
        color: var(--rm-ink);
        font-size: 12px !important;
        line-height: 1.3 !important;
        height: 35px !important;
        white-space: nowrap;
    }

    .rm-table tbody tr:hover > td {
        background: #fffdfa;
    }

    .rm-index {
        width: 46px;
        color: var(--rm-muted);
        font-weight: 700;
        font-size: 12px;
        text-align: center;
    }

    .rm-role-name {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 850;
        font-size: 12px;
        color: var(--rm-ink);
        white-space: nowrap;
    }

    .rm-role-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 3px 7px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .rm-role-badge.is-core {
        background: var(--rm-accent-soft);
        color: var(--rm-accent);
    }

    .rm-role-sub {
        margin-top: 2px;
        color: var(--rm-muted);
        font-size: 11px;
        white-space: nowrap;
    }

    .rm-metric {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 46px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid var(--rm-line);
        font-weight: 800;
        color: var(--rm-ink);
        font-size: 11px;
    }

    .rm-perm-stack {
        display: flex;
        flex-wrap: nowrap;
        gap: 4px;
        max-width: none;
        white-space: nowrap;
    }

    .rm-perm-chip {
        display: inline-flex;
        align-items: center;
        padding: 3px 7px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid var(--rm-line);
        color: #334155;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.2;
        white-space: nowrap;
    }

    .rm-modules {
        display: flex;
        flex-wrap: nowrap;
        gap: 4px;
        max-width: none;
        white-space: nowrap;
    }

    .rm-module-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 3px 7px;
        font-size: 11px;
        font-weight: 800;
        background: #fff7ed;
        color: #c2410c;
    }

    .rm-actions {
        width: 72px;
    }

    .rm-action-row {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 2px;
        width: 100%;
        white-space: nowrap;
    }

    .rm-action-btn {
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

    .rm-action-btn.is-primary {
        color: #b45309 !important;
    }

    .rm-action-btn.is-danger {
        color: #b91c1c !important;
    }

    .rm-action-btn:hover {
        transform: none;
        background: transparent !important;
        box-shadow: none !important;
    }

    .rm-editor-row td {
        padding: 0 !important;
        background: #fbfcfe;
        height: 0 !important;
        min-height: 0 !important;
        line-height: 0 !important;
        border-top: none !important;
        border-bottom: none !important;
    }

    .rm-create-row td {
        padding: 0 !important;
        background: #fffdfa;
        height: 0 !important;
        min-height: 0 !important;
        line-height: 0 !important;
        border-top: none !important;
        border-bottom: none !important;
    }

    .rm-editor {
        padding: 14px;
        border-bottom: 1px solid var(--rm-line);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(251, 246, 241, 1) 100%);
        line-height: 1.5;
    }

    .rm-editor-grid {
        display: grid;
        gap: 14px;
    }

    .rm-permission-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
    }

    .rm-permission-group {
        background: #fff;
        border: 1px solid var(--rm-line);
        border-radius: 14px;
        padding: 12px;
        display: grid;
        gap: 8px;
    }

    .rm-permission-title {
        font-size: 0.68rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--rm-accent);
    }

    .rm-permission-list {
        display: grid;
        gap: 6px;
    }

    .rm-permission-option {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 0.76rem;
        color: var(--rm-ink);
        line-height: 1.35;
    }

    .rm-permission-option input {
        accent-color: var(--rm-accent);
        width: 16px;
        height: 16px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .rm-editor-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .rm-editor-actions .rm-toolbar-group {
        justify-content: flex-end;
    }

    .rm-empty {
        padding: 44px 20px;
        text-align: center;
        color: var(--rm-muted);
        font-size: 0.9rem;
    }

    .rm-pagination {
        flex: 0 0 auto;
        padding: 16px 20px;
        border-top: 1px solid var(--rm-line);
        background: #fff;
    }

    .rm-table thead th:last-child,
    .rm-table tbody td:last-child {
        width: 72px !important;
        min-width: 72px !important;
        max-width: 72px !important;
        text-align: center !important;
    }

    @media (max-width: 1140px) {
        .rm-hero {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 900px) {
        .rm-viewport {
            height: 100%;
        }

        .rm-shell {
            min-height: 0;
        }

        .rm-table-frame {
            height: 100%;
        }

        .rm-table-wrap {
            height: 100%;
        }

        .rm-filter-grid,
        .rm-stat-grid {
            grid-template-columns: 1fr;
        }

        .rm-filter-actions,
        .rm-editor-actions .rm-toolbar-group {
            width: 100%;
        }

        .rm-filter-actions > *,
        .rm-editor-actions .rm-toolbar-group > * {
            flex: 1;
        }

        .rm-inline-create-handle {
            left: 8px;
            top: 8px;
            transform: none;
        }

        .rm-inline-create-handle:hover {
            transform: none;
        }
    }
</style>

<div class="rm-viewport">
<div class="rm-shell">

    @if ($errors->any())
        <div class="alert alert-danger mb-0" style="border-radius: 18px; font-size: .84rem;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="rm-panel">
        <form action="{{ route('settings.roles.index') }}" method="GET" class="material-search-form manual-search" data-search-manual="true">
            <div class="material-search-input">
                <i class="bi bi-search"></i>
                <input
                    id="role-search"
                    type="text"
                    name="search"
                    data-search-manual="true"
                    value="{{ request('search') }}"
                    placeholder="Cari role..."
                >
            </div>
            <button type="submit" class="btn btn-primary-glossy">
                <i class="bi bi-search"></i> Cari
            </button>
            @if (request('search'))
                <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary-glossy material-search-reset-btn">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            @endif
            <button
                type="button"
                class="btn btn-secondary-glossy"
                data-bs-toggle="collapse"
                data-bs-target="#role-create-row"
                aria-expanded="{{ old('form_context') === 'create-role' ? 'true' : 'false' }}"
                aria-controls="role-create-row"
            >
                <i class="bi bi-plus-lg"></i> Tambah Role
            </button>
        </form>
    </section>

        <div class="rm-table-frame">
            <div class="rm-table-wrap table-container">
            <table class="rm-table">
                <thead>
                    <tr>
                        <th class="rm-index">No</th>
                        <th>Role</th>
                        <th>User</th>
                        <th>Permission</th>
                        <th>Modul</th>
                        <th class="rm-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="rm-create-row">
                        <td colspan="6">
                            <div id="role-create-row" class="collapse {{ old('form_context') === 'create-role' ? 'show' : '' }}">
                                <div class="rm-editor">
                                    <form action="{{ route('settings.roles.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="form_context" value="create-role">

                                        <div class="rm-editor-grid">
                                            <div class="rm-field rm-field-inline">
                                                <label class="rm-label">Nama Role</label>
                                                <input
                                                    type="text"
                                                    name="name"
                                                    class="rm-input"
                                                    value="{{ old('form_context') === 'create-role' ? old('name') : '' }}"
                                                    placeholder="Contoh: supervisor"
                                                    required
                                                >
                                            </div>

                                            <div class="rm-field">
                                                <label class="rm-label">Permission</label>
                                                <div class="rm-permission-grid">
                                                    @foreach ($permissionGroups as $groupName => $groupPermissions)
                                                        <div class="rm-permission-group">
                                                            <div class="rm-permission-title">{{ $groupName }}</div>
                                                            <div class="rm-permission-list">
                                                                @foreach ($groupPermissions as $permission)
                                                                    <label class="rm-permission-option">
                                                                        <input
                                                                            type="checkbox"
                                                                            name="permissions[]"
                                                                            value="{{ $permission->name }}"
                                                                            @checked(old('form_context') === 'create-role' && collect(old('permissions', []))->contains($permission->name))
                                                                        >
                                                                        <span>{{ $permission->name }}</span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rm-editor-actions">
                                            <span class="rm-table-note">Role baru akan langsung tersedia untuk dipasang ke user setelah disimpan.</span>
                                            <div class="rm-toolbar-group">
                                                <button
                                                    type="button"
                                                    class="rm-soft-btn"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#role-create-row"
                                                    aria-controls="role-create-row"
                                                >
                                                    Batal
                                                </button>
                                                <button type="submit" class="rm-primary-btn">Tambah Role</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @forelse ($roles as $role)
                    @php
                        $modules = $role->permissions
                            ->map(fn ($permission) => \Illuminate\Support\Str::headline(\Illuminate\Support\Str::before($permission->name, '.') ?: $permission->name))
                            ->unique()
                            ->values();
                    @endphp
                        <tr>
                            <td class="rm-index">{{ ($roles->firstItem() ?? 1) + $loop->index }}</td>
                            <td>
                                <div class="rm-role-name">
                                    <span>{{ $role->name }}</span>
                                    @if ($role->name === 'admin')
                                        <span class="rm-role-badge is-core">Role inti</span>
                                    @endif
                                </div>
                            </td>
                            <td><span class="rm-metric">{{ $role->users_count }}</span></td>
                            <td>
                                <div class="rm-perm-stack">
                                    @foreach ($role->permissions->take(4) as $permission)
                                        <span class="rm-perm-chip">{{ $permission->name }}</span>
                                    @endforeach
                                    @if ($role->permissions->count() > 4)
                                        <span class="rm-perm-chip">+{{ $role->permissions->count() - 4 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="rm-modules">
                                    @forelse ($modules->take(4) as $module)
                                        <span class="rm-module-chip">{{ $module }}</span>
                                    @empty
                                        <span class="rm-table-note">Belum ada modul</span>
                                    @endforelse
                                    @if ($modules->count() > 4)
                                        <span class="rm-module-chip">+{{ $modules->count() - 4 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="rm-actions">
                                <div class="rm-action-row">
                                    <button
                                        type="button"
                                        class="rm-action-btn is-primary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#role-editor-{{ $role->id }}"
                                        aria-expanded="{{ old('editing_role_id') == $role->id ? 'true' : 'false' }}"
                                        aria-controls="role-editor-{{ $role->id }}"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @if ($role->name !== 'admin')
                                        <form action="{{ route('settings.roles.destroy', $role) }}" method="POST" data-confirm="Hapus role ini?" data-confirm-ok="Hapus" data-confirm-cancel="Batal">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rm-action-btn is-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr class="rm-editor-row">
                            <td colspan="6">
                                <div id="role-editor-{{ $role->id }}" class="collapse {{ old('editing_role_id') == $role->id ? 'show' : '' }}">
                                <div class="rm-editor">
                                    <form action="{{ route('settings.roles.update', $role) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="editing_role_id" value="{{ $role->id }}">

                                        <div class="rm-editor-grid">
                                            <div class="rm-field">
                                                <label class="rm-label">Nama Role</label>
                                                <input
                                                    type="text"
                                                    name="name"
                                                    class="rm-input"
                                                    value="{{ old('editing_role_id') == $role->id ? old('name', $role->name) : $role->name }}"
                                                    @disabled($role->name === 'admin')
                                                    required
                                                >
                                                @if ($role->name === 'admin')
                                                    <span class="rm-table-note">Nama role inti dikunci untuk menjaga baseline akses.</span>
                                                @endif
                                            </div>

                                            <div class="rm-field">
                                                <label class="rm-label">Permission</label>
                                                <div class="rm-permission-grid">
                                                    @foreach ($permissionGroups as $groupName => $groupPermissions)
                                                        <div class="rm-permission-group">
                                                            <div class="rm-permission-title">{{ $groupName }}</div>
                                                            <div class="rm-permission-list">
                                                                @foreach ($groupPermissions as $permission)
                                                                    @php
                                                                        $selectedPermissions = old('editing_role_id') == $role->id
                                                                            ? collect(old('permissions', []))
                                                                            : $role->permissions->pluck('name');
                                                                    @endphp
                                                                    <label class="rm-permission-option">
                                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($selectedPermissions->contains($permission->name))>
                                                                        <span>{{ $permission->name }}</span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rm-editor-actions">
                                            <span class="rm-table-note">Perubahan permission akan langsung memengaruhi akses user dengan role ini.</span>
                                            <div class="rm-toolbar-group">
                                                <button
                                                    type="button"
                                                    class="rm-soft-btn"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#role-editor-{{ $role->id }}"
                                                    aria-controls="role-editor-{{ $role->id }}"
                                                >
                                                    Batal
                                                </button>
                                                <button type="submit" class="rm-primary-btn">Simpan Perubahan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                </div>
                            </td>
                        </tr>
                @empty
                    <tr>
                        <td colspan="6" class="rm-empty">Tidak ada role yang cocok dengan filter saat ini.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>

            @if ($roles->hasPages())
                <div class="rm-pagination">{{ $roles->links() }}</div>
            @endif
        </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.rm-create-row, .rm-editor-row').forEach(function (row) {
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
