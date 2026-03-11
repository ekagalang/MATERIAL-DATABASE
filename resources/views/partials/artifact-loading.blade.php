@php
    $message = $message ?? 'Memuat data...';
    $compact = $compact ?? false;
@endphp

<div class="artifact-loading{{ $compact ? ' artifact-loading--compact' : '' }}" role="status" aria-live="polite">
    <div class="artifact-loading__spinner" aria-hidden="true"></div>
    <span class="visually-hidden">{{ $message }}</span>
</div>
