@php
    /** @var \Illuminate\Database\Eloquent\Model&\App\Models\Concerns\HasMaterialChangeHistory $materialEntity */
    $materialEntity->loadMissing('materialChangeLogs.user');
    $historyEntries = $materialEntity->materialChangeLogs;
@endphp

<div style="margin-top: 24px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 12px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">Riwayat Perubahan</h3>
    </div>

    @if ($historyEntries->isEmpty())
        <div style="padding: 16px 18px; border: 1px dashed #cbd5e1; border-radius: 14px; background: #f8fafc; color: #64748b; font-size: 13px;">
            Belum ada riwayat perubahan untuk material ini.
        </div>
    @else
        <div data-history-stage>
            @foreach ($historyEntries as $historyIndex => $historyEntry)
                @php
                    $actionLabel = match ($historyEntry->action) {
                        'created' => 'Dibuat',
                        'deleted' => 'Dihapus',
                        default => 'Diedit',
                    };
                @endphp
                <section
                    data-history-slide
                    @if ($historyIndex !== 0) hidden @endif
                    style="border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; background: linear-gradient(180deg, #fffdfb 0%, #ffffff 100%);">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; border-bottom: 1px solid #eef2f7; background: linear-gradient(135deg, #fff7ed 0%, #fff1f2 100%);">
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; min-width: 0;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #ffffff; border: 1px solid #fed7aa; font-size: 11px; font-weight: 800; color: #9a3412;">
                                {{ $actionLabel }}
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #ffffff; border: 1px solid #dbe4f0; font-size: 11px; font-weight: 700; color: #334155;">
                                <i class="bi bi-person"></i>
                                {{ $historyEntry->user?->name ?? 'Sistem' }}
                                <span style="display: inline-flex; align-items: center; gap: 5px; margin-left: 6px; padding-left: 8px; border-left: 1px solid #e2e8f0; color: #64748b; font-weight: 700;">
                                    <i class="bi bi-clock-history"></i>
                                    {{ optional($historyEntry->edited_at)->format('d M Y, H:i') ?? '-' }}
                                </span>
                            </span>
                        </div>
                        @if ($historyEntries->count() > 1)
                            <div
                                data-history-nav
                                style="display: inline-flex; align-items: center; gap: 8px; padding: 4px; border: 1px solid #e2e8f0; border-radius: 999px; background: #fff; flex-shrink: 0;">
                                <button
                                    type="button"
                                    data-history-prev
                                    aria-label="Riwayat sebelumnya"
                                    style="width: 32px; height: 32px; border: none; border-radius: 999px; background: #fff7ed; color: #9a3412; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button
                                    type="button"
                                    data-history-next
                                    aria-label="Riwayat berikutnya"
                                    style="width: 32px; height: 32px; border: none; border-radius: 999px; background: #fff7ed; color: #9a3412; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div style="padding: 14px 18px; display: grid; gap: 10px;">
                        @foreach ($historyEntry->changes as $field => $change)
                            <div style="display: grid; grid-template-columns: minmax(130px, 180px) minmax(0, 1fr) auto minmax(0, 1fr); gap: 10px; align-items: stretch;">
                                <div style="padding: 10px 12px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; color: #334155;">
                                    {{ \App\Models\MaterialChangeLog::labelForField($field) }}
                                </div>
                                <div style="padding: 10px 12px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa; font-size: 12px; color: #9a3412; overflow-wrap: anywhere;">
                                    {{ \App\Models\MaterialChangeLog::formatValue($change['from'] ?? null) }}
                                </div>
                                <div style="display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; font-weight: 900;">
                                    <i class="bi bi-arrow-right"></i>
                                </div>
                                <div style="padding: 10px 12px; border-radius: 12px; background: #ecfdf5; border: 1px solid #86efac; font-size: 12px; color: #166534; overflow-wrap: anywhere;">
                                    {{ \App\Models\MaterialChangeLog::formatValue($change['to'] ?? null) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>
