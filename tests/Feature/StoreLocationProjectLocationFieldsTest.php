<?php

use App\Models\BrickCalculation;
use App\Models\StoreLocation;
use Illuminate\Support\Facades\File;

test('migration exists for store location geolocation and radius fields', function () {
    $migrationFile = collect(File::files(database_path('migrations')))
        ->first(fn($file) => str_contains($file->getFilename(), 'add_geolocation_fields_to_store_locations_table'));

    expect($migrationFile)->not->toBeNull();

    $content = File::get($migrationFile->getPathname());

    expect($content)->toContain('latitude');
    expect($content)->toContain('longitude');
    expect($content)->toContain('place_id');
    expect($content)->toContain('formatted_address');
    expect($content)->toContain('service_radius_km');
});

test('migration exists for project location fields in brick calculations', function () {
    $migrationFile = collect(File::files(database_path('migrations')))
        ->first(fn($file) => str_contains($file->getFilename(), 'add_project_location_fields_to_brick_calculations_table'));

    expect($migrationFile)->not->toBeNull();

    $content = File::get($migrationFile->getPathname());

    expect($content)->toContain('project_address');
    expect($content)->toContain('project_latitude');
    expect($content)->toContain('project_longitude');
    expect($content)->toContain('project_place_id');
});

test('store location model supports geolocation and flexible radius input', function () {
    $storeLocation = new StoreLocation();

    expect($storeLocation->getFillable())->toContain(
        'latitude',
        'longitude',
        'place_id',
        'formatted_address',
        'service_radius_km',
    );
});

test('brick calculation model supports project location attributes', function () {
    $calculation = new BrickCalculation();

    expect($calculation->getFillable())->toContain(
        'project_address',
        'project_latitude',
        'project_longitude',
        'project_place_id',
    );
});
