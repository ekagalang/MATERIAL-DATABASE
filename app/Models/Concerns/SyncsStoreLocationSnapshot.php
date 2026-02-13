<?php

namespace App\Models\Concerns;

use App\Models\StoreLocation;
use Illuminate\Support\Facades\Schema;

trait SyncsStoreLocationSnapshot
{
    public static function bootSyncsStoreLocationSnapshot(): void
    {
        static::saving(function ($model): void {
            if (!Schema::hasTable('store_locations')) {
                return;
            }

            if (!$model->isDirty('store_location_id')) {
                return;
            }

            $storeLocationId = $model->store_location_id;
            if (!$storeLocationId) {
                return;
            }

            $location = StoreLocation::with('store')->find($storeLocationId);
            if (!$location) {
                return;
            }

            if (property_exists($model, 'fillable') && in_array('store', $model->getFillable(), true)) {
                $model->store = $location->store->name ?? $model->store;
            }

            if (property_exists($model, 'fillable') && in_array('address', $model->getFillable(), true)) {
                $model->address = $location->address;
            }
        });

        static::saved(function ($model): void {
            if (!Schema::hasTable('store_material_availabilities')) {
                return;
            }

            if (!method_exists($model, 'storeLocations')) {
                return;
            }

            $storeLocationChanged = $model->wasChanged('store_location_id');
            if (!$storeLocationChanged && !$model->wasRecentlyCreated) {
                return;
            }

            if ($model->store_location_id) {
                $model->storeLocations()->sync([$model->store_location_id]);
                return;
            }

            $model->storeLocations()->detach();
        });
    }
}
