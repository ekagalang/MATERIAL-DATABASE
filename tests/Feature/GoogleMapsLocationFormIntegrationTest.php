<?php

use Illuminate\Support\Facades\File;

test('store forms include google maps picker fields', function () {
    $storeCreate = File::get(resource_path('views/stores/create.blade.php'));
    $storeLocationCreate = File::get(resource_path('views/store-locations/create.blade.php'));
    $storeLocationEdit = File::get(resource_path('views/store-locations/edit.blade.php'));

    expect($storeCreate)->toContain('storeLocationSearch');
    expect($storeCreate)->toContain('storeLocationMap');
    expect($storeCreate)->toContain('name="latitude"');
    expect($storeCreate)->toContain('name="longitude"');
    expect($storeCreate)->toContain('name="service_radius_km"');

    expect($storeLocationCreate)->toContain('storeLocationSearch');
    expect($storeLocationCreate)->toContain('storeLocationMap');
    expect($storeLocationCreate)->toContain('name="service_radius_km"');

    expect($storeLocationEdit)->toContain('storeLocationSearch');
    expect($storeLocationEdit)->toContain('storeLocationMap');
    expect($storeLocationEdit)->toContain('name="service_radius_km"');
});

test('material calculation create form includes project map location fields', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->toContain('projectLocationSearch');
    expect($content)->toContain('projectLocationMap');
    expect($content)->toContain('name="project_address"');
    expect($content)->toContain('name="project_latitude"');
    expect($content)->toContain('name="project_longitude"');
    expect($content)->toContain('name="project_place_id"');
    expect($content)->toContain('name="use_store_filter"');
    expect($content)->toContain('name="allow_mixed_store"');
});

test('store controllers validate geolocation and flexible radius inputs', function () {
    $storeController = File::get(app_path('Http/Controllers/StoreController.php'));
    $storeLocationController = File::get(app_path('Http/Controllers/StoreLocationController.php'));

    expect($storeController)->toContain("'latitude' => 'nullable|numeric|between:-90,90'");
    expect($storeController)->toContain("'longitude' => 'nullable|numeric|between:-180,180'");
    expect($storeController)->toContain("'service_radius_km' => 'nullable|numeric|min:0'");

    expect($storeLocationController)->toContain("'latitude' => 'nullable|numeric|between:-90,90'");
    expect($storeLocationController)->toContain("'longitude' => 'nullable|numeric|between:-180,180'");
    expect($storeLocationController)->toContain("'service_radius_km' => 'nullable|numeric|min:0'");
});

test('material calculation controller accepts project location fields', function () {
    $controller = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));

    expect($controller)->toContain("'project_address' => 'nullable|string'");
    expect($controller)->toContain("'project_latitude' => 'nullable|numeric|between:-90,90'");
    expect($controller)->toContain("'project_longitude' => 'nullable|numeric|between:-180,180'");
    expect($controller)->toContain("'project_place_id' => 'nullable|string|max:255'");
});

test('frontend scripts initialize google maps picker integration', function () {
    $storeScript = File::get(public_path('js/store-form.js'));
    $storeLocationScript = File::get(public_path('js/store-location-form.js'));
    $calculationScript = File::get(public_path('js/material-calculation-form.js'));
    $mapsPickerScript = File::get(public_path('js/google-maps-picker.js'));

    expect($storeScript)->toContain('GoogleMapsPicker');
    expect($storeLocationScript)->toContain('GoogleMapsPicker');
    expect($calculationScript)->toContain('GoogleMapsPicker');
    expect($mapsPickerScript)->toContain("geocoder.geocode({ address:");
    expect($mapsPickerScript)->toContain("searchInput.addEventListener('keydown'");
});
