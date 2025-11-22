@extends('layouts.app')

@section('title', 'Pengaturan Material')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
        <h2 style="margin-bottom: 0;">Pengaturan Tampilan Material</h2>
        <a href="{{ route('materials.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <p style="margin-bottom: 24px; color: #64748b;">
        Atur material mana yang ingin ditampilkan di halaman "Semua Material" dan urutannya.
    </p>

    <form action="{{ route('materials.settings.update') }}" method="POST">
        @csrf

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">Urutan</th>
                        <th>Material</th>
                        <th style="width: 150px; text-align: center;">Tampilkan</th>
                        <th style="width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="sortable-settings">
                    @foreach($settings as $index => $setting)
                    <tr data-id="{{ $setting->id }}">
                        <td style="text-align: center;">
                            <input type="hidden" name="settings[{{ $index }}][id]" value="{{ $setting->id }}">
                            <input type="number"
                                   name="settings[{{ $index }}][display_order]"
                                   value="{{ $setting->display_order }}"
                                   min="1"
                                   style="width: 60px; text-align: center; padding: 6px; border: 1.5px solid #e2e8f0; border-radius: 6px;">
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <i class="bi bi-grip-vertical" style="cursor: move; color: #94a3b8; font-size: 18px;"></i>
                                <strong style="font-size: 15px; color: #0f172a;">
                                    {{ \App\Models\MaterialSetting::getMaterialLabel($setting->material_type) }}
                                </strong>
                                <span style="font-size: 12px; color: #94a3b8; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">
                                    {{ $setting->material_type }}
                                </span>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       name="settings[{{ $index }}][is_visible]"
                                       value="1"
                                       {{ $setting->is_visible ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <input type="hidden" name="settings[{{ $index }}][is_visible]" value="0">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn btn-sm btn-secondary move-up" title="Pindah ke atas">
                                <i class="bi bi-arrow-up"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary move-down" title="Pindah ke bawah">
                                <i class="bi bi-arrow-down"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('materials.index') }}" class="btn btn-secondary">
                Batal
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<style>
/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #891313;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.toggle-slider:hover {
    background-color: #94a3b8;
}

.toggle-switch input:checked + .toggle-slider:hover {
    background-color: #a01818;
}

#sortable-settings tr {
    cursor: move;
}

#sortable-settings tr:hover {
    background-color: #f8fafc;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('sortable-settings');
    const rows = tbody.querySelectorAll('tr');

    // Move up button
    tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.move-up');
        if (btn) {
            const row = btn.closest('tr');
            const prevRow = row.previousElementSibling;
            if (prevRow) {
                tbody.insertBefore(row, prevRow);
                updateOrderInputs();
            }
        }
    });

    // Move down button
    tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.move-down');
        if (btn) {
            const row = btn.closest('tr');
            const nextRow = row.nextElementSibling;
            if (nextRow) {
                tbody.insertBefore(nextRow, row);
                updateOrderInputs();
            }
        }
    });

    // Update order inputs based on current position
    function updateOrderInputs() {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            const orderInput = row.querySelector('input[name*="[display_order]"]');
            if (orderInput) {
                orderInput.value = index + 1;
            }
        });
    }

    // Fix checkbox behavior for hidden field
    const checkboxes = document.querySelectorAll('.toggle-switch input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        const hiddenInput = checkbox.parentElement.nextElementSibling;

        checkbox.addEventListener('change', function() {
            if (this.checked) {
                hiddenInput.disabled = true;
            } else {
                hiddenInput.disabled = false;
            }
        });

        // Initialize
        if (checkbox.checked) {
            hiddenInput.disabled = true;
        }
    });
});
</script>
@endsection
