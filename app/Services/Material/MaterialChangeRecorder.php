<?php

namespace App\Services\Material;

use App\Models\MaterialChangeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MaterialChangeRecorder
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected static array $originalSnapshots = [];

    public function rememberOriginal(Model $material): void
    {
        $key = $this->snapshotKey($material);
        if ($key === null || isset(self::$originalSnapshots[$key])) {
            return;
        }

        self::$originalSnapshots[$key] = $this->extractAuditableAttributes(
            $material,
            $this->requestedAuditableFields($material, 'updated'),
        );
    }

    public function recordCreate(Model $material): void
    {
        $requestBatch = $this->requestBatchId();
        if ($requestBatch === null) {
            return;
        }

        $afterValues = $this->extractCurrentAuditableAttributes(
            $material,
            $this->requestedAuditableFields($material, 'created'),
        );
        $beforeValues = [];
        $changes = $this->buildChanges($beforeValues, $afterValues);

        if ($changes === []) {
            return;
        }

        $log = MaterialChangeLog::query()->firstOrNew([
            'material_table' => $material->getTable(),
            'material_id' => $material->getKey(),
            'request_batch' => $requestBatch,
            'action' => 'created',
        ]);

        $log->fill([
            'material_kind' => method_exists($material, 'materialHistoryKind')
                ? $material->materialHistoryKind()
                : strtolower(class_basename($material)),
            'user_id' => Auth::id(),
            'changes' => $changes,
            'before_values' => $beforeValues,
            'after_values' => $afterValues,
            'edited_at' => Carbon::now(),
        ]);

        $log->save();
    }

    public function recordUpdate(Model $material): void
    {
        $requestBatch = $this->requestBatchId();
        $key = $this->snapshotKey($material);
        if ($requestBatch === null || $key === null) {
            return;
        }

        $requestedFields = $this->requestedAuditableFields($material, 'updated');

        $beforeValues = self::$originalSnapshots[$key] ?? $this->extractAuditableAttributes(
            $material,
            $requestedFields,
        );
        $afterValues = $this->extractRequestedAuditableAttributes(
            $material,
            $requestedFields,
        );
        $changes = $this->buildChanges($beforeValues, $afterValues);

        if ($changes === []) {
            return;
        }

        $createdLog = MaterialChangeLog::query()->where([
            'material_table' => $material->getTable(),
            'material_id' => $material->getKey(),
            'request_batch' => $requestBatch,
            'action' => 'created',
        ])->first();

        if ($createdLog) {
            $createdBefore = is_array($createdLog->before_values) ? $createdLog->before_values : [];
            $createdChanges = $this->buildChanges($createdBefore, $afterValues);

            if ($createdChanges === []) {
                return;
            }

            $createdLog->fill([
                'changes' => $createdChanges,
                'after_values' => $afterValues,
                'edited_at' => Carbon::now(),
            ]);

            $createdLog->save();

            return;
        }

        $log = MaterialChangeLog::query()->firstOrNew([
            'material_table' => $material->getTable(),
            'material_id' => $material->getKey(),
            'request_batch' => $requestBatch,
            'action' => 'updated',
        ]);

        $persistedBefore = is_array($log->before_values) ? $log->before_values : $beforeValues;
        $persistedAfter = $afterValues;
        $persistedChanges = $this->buildChanges($persistedBefore, $persistedAfter);

        if ($persistedChanges === []) {
            return;
        }

        $log->fill([
            'material_kind' => method_exists($material, 'materialHistoryKind')
                ? $material->materialHistoryKind()
                : strtolower(class_basename($material)),
            'user_id' => Auth::id(),
            'changes' => $persistedChanges,
            'before_values' => $persistedBefore,
            'after_values' => $persistedAfter,
            'edited_at' => Carbon::now(),
        ]);

        $log->save();
    }

    public function recordDelete(Model $material): void
    {
        $requestBatch = $this->requestBatchId();
        $key = $this->snapshotKey($material);
        if ($requestBatch === null || $key === null) {
            return;
        }

        $beforeValues = self::$originalSnapshots[$key] ?? $this->extractAuditableAttributes(
            $material,
            $this->requestedAuditableFields($material, 'deleted'),
        );
        $afterValues = [];
        $changes = $this->buildChanges($beforeValues, $afterValues);

        if ($changes === []) {
            return;
        }

        $log = MaterialChangeLog::query()->firstOrNew([
            'material_table' => $material->getTable(),
            'material_id' => $material->getKey(),
            'request_batch' => $requestBatch,
            'action' => 'deleted',
        ]);

        $log->fill([
            'material_kind' => method_exists($material, 'materialHistoryKind')
                ? $material->materialHistoryKind()
                : strtolower(class_basename($material)),
            'user_id' => Auth::id(),
            'changes' => $changes,
            'before_values' => $beforeValues,
            'after_values' => $afterValues,
            'edited_at' => Carbon::now(),
        ]);

        $log->save();
    }

    protected function snapshotKey(Model $material): ?string
    {
        $requestBatch = $this->requestBatchId();
        if ($requestBatch === null || !$material->getKey()) {
            return null;
        }

        return implode('|', [$requestBatch, $material->getTable(), $material->getKey()]);
    }

    protected function requestBatchId(): ?string
    {
        if (!app()->bound('request')) {
            return null;
        }

        /** @var Request $request */
        $request = request();
        $batchId = $request->attributes->get('_material_change_request_batch');

        if (is_string($batchId) && $batchId !== '') {
            return $batchId;
        }

        $batchId = (string) Str::uuid();
        $request->attributes->set('_material_change_request_batch', $batchId);

        return $batchId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractAuditableAttributes(Model $material, ?array $fields = null): array
    {
        $snapshot = [];
        $fillable = $fields ?? $material->getFillable();
        $excluded = ['created_at', 'updated_at', 'material_kind'];

        foreach ($fillable as $field) {
            if (in_array($field, $excluded, true)) {
                continue;
            }

            $value = $material->getRawOriginal($field);
            $snapshot[$field] = $this->normalizeValue($field, $value);
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractCurrentAuditableAttributes(Model $material, ?array $fields = null): array
    {
        $snapshot = [];
        $fillable = $fields ?? $material->getFillable();
        $excluded = ['created_at', 'updated_at', 'material_kind'];

        foreach ($fillable as $field) {
            if (in_array($field, $excluded, true)) {
                continue;
            }

            $snapshot[$field] = $this->normalizeValue($field, $material->getAttribute($field));
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractRequestedAuditableAttributes(Model $material, array $fields): array
    {
        if (!app()->bound('request')) {
            return $this->extractCurrentAuditableAttributes($material, $fields);
        }

        /** @var Request $request */
        $request = request();
        $snapshot = [];

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $snapshot[$field] = $this->normalizeValue($field, $material->getAttribute($field));
                continue;
            }

            if ($request->exists($field)) {
                $snapshot[$field] = $this->normalizeValue($field, $request->input($field));
                continue;
            }

            $snapshot[$field] = $this->normalizeValue($field, $material->getAttribute($field));
        }

        return $snapshot;
    }

    /**
     * @return array<int, string>
     */
    protected function requestedAuditableFields(Model $material, string $action): array
    {
        $fillable = array_values(array_unique($material->getFillable()));

        if ($action === 'deleted' || !app()->bound('request')) {
            return $fillable;
        }

        /** @var Request $request */
        $request = request();
        $requestedFields = array_keys($request->except([
            '_token',
            '_method',
            '_redirect_url',
            '_redirect_to_materials',
            'form_context',
        ]));

        $filtered = array_values(array_intersect($fillable, $requestedFields));

        return $filtered !== [] ? $filtered : $fillable;
    }

    /**
     * @param array<string, mixed> $beforeValues
     * @param array<string, mixed> $afterValues
     * @return array<string, array{from:mixed,to:mixed}>
     */
    protected function buildChanges(array $beforeValues, array $afterValues): array
    {
        $changes = [];
        $fields = array_unique(array_merge(array_keys($beforeValues), array_keys($afterValues)));

        foreach ($fields as $field) {
            $before = $beforeValues[$field] ?? null;
            $after = $afterValues[$field] ?? null;

            if ($this->valuesAreEquivalent($before, $after)) {
                continue;
            }

            $changes[$field] = [
                'from' => $before,
                'to' => $after,
            ];
        }

        return $changes;
    }

    protected function valuesAreEquivalent(mixed $before, mixed $after): bool
    {
        if ($before === $after) {
            return true;
        }

        if (is_numeric($before) && is_numeric($after)) {
            return (float) $before === (float) $after;
        }

        return false;
    }

    protected function normalizeValue(string $field, mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        if (is_bool($value) || is_null($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            if (str_ends_with($field, '_id')) {
                return (int) $value;
            }

            return (float) $value;
        }

        if (is_array($value)) {
            return array_map(fn($item) => $this->normalizeValue($field, $item), $value);
        }

        return is_string($value) ? trim($value) : $value;
    }
}
