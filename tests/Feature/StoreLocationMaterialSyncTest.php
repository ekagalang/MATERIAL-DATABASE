<?php

use App\Models\Brick;
use App\Models\Store;
use App\Models\StoreLocation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropAllTables();

    Schema::create('stores', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('store_locations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('store_id');
        $table->text('address')->nullable();
        $table->string('district')->nullable();
        $table->string('city')->nullable();
        $table->string('province')->nullable();
        $table->string('contact_name')->nullable();
        $table->string('contact_phone')->nullable();
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->string('place_id')->nullable();
        $table->text('formatted_address')->nullable();
        $table->decimal('service_radius_km', 8, 2)->nullable();
        $table->timestamps();
    });

    Schema::create('store_material_availabilities', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('store_location_id');
        $table->unsignedBigInteger('materialable_id');
        $table->string('materialable_type');
        $table->timestamps();
    });

    Schema::create('material_settings', function (Blueprint $table) {
        $table->id();
        $table->string('material_type');
        $table->boolean('is_visible')->default(true);
        $table->integer('display_order')->default(0);
        $table->timestamps();
    });

    Schema::create('bricks', function (Blueprint $table) {
        $table->id();
        $table->string('material_name')->default('Bata');
        $table->string('type')->nullable();
        $table->string('photo')->nullable();
        $table->string('brand')->nullable();
        $table->string('form')->nullable();
        $table->decimal('dimension_length', 10, 2)->nullable();
        $table->decimal('dimension_width', 10, 2)->nullable();
        $table->decimal('dimension_height', 10, 2)->nullable();
        $table->decimal('package_volume', 10, 6)->nullable();
        $table->string('store')->nullable();
        $table->text('address')->nullable();
        $table->unsignedBigInteger('store_location_id')->nullable();
        $table->decimal('price_per_piece', 15, 2)->nullable();
        $table->decimal('comparison_price_per_m3', 15, 2)->nullable();
        $table->timestamps();
    });

    \App\Models\MaterialSetting::create([
        'material_type' => 'brick',
        'is_visible' => true,
        'display_order' => 1,
    ]);
});

test('materials remain searchable in materials index after store location address is updated', function () {
    $store = Store::create([
        'name' => 'TB Uji Sinkron',
    ]);

    $location = StoreLocation::create([
        'store_id' => $store->id,
        'address' => 'Jl. Lama No. 10',
    ]);

    $brick = Brick::create([
        'material_name' => 'Bata',
        'type' => 'Merah',
        'brand' => 'Brand Sinkron',
        'form' => 'Persegi',
        'store' => $store->name,
        'address' => $location->address,
        'store_location_id' => $location->id,
        'price_per_piece' => 1000,
    ]);
    $brick->storeLocations()->attach($location->id);

    $this->get(route('materials.index', ['tab' => 'brick', 'search' => 'Jl. Lama No. 10']))
        ->assertOk()
        ->assertSee('Brand Sinkron');

    $this->put(route('store-locations.update', [$store, $location]), [
        'address' => 'Jl. Baru No. 99',
    ])->assertRedirect(route('stores.show', $store));

    $brick->refresh();

    expect($brick->address)->toBe('Jl. Baru No. 99');

    $this->get(route('materials.index', ['tab' => 'brick', 'search' => 'Jl. Baru No. 99']))
        ->assertOk()
        ->assertSee('Brand Sinkron');
});

test('materials stay visible after store location is reassigned', function () {
    $store = Store::create([
        'name' => 'TB Reassign',
    ]);

    $oldLocation = StoreLocation::create([
        'store_id' => $store->id,
        'address' => 'Jl. Lama Reassign',
    ]);

    $newLocation = StoreLocation::create([
        'store_id' => $store->id,
        'address' => 'Jl. Baru Reassign',
    ]);

    $brick = Brick::create([
        'material_name' => 'Bata',
        'type' => 'Press',
        'brand' => 'Brand Pindah',
        'store' => $store->name,
        'address' => $oldLocation->address,
        'store_location_id' => $oldLocation->id,
    ]);

    $brick->store_location_id = $newLocation->id;
    $brick->save();
    $brick->refresh();

    expect((int) $brick->store_location_id)->toBe((int) $newLocation->id)
        ->and($brick->address)->toBe('Jl. Baru Reassign')
        ->and($brick->store)->toBe('TB Reassign');

    expect($brick->storeLocations()->pluck('store_locations.id')->all())->toBe([$newLocation->id]);

    $this->get(route('materials.index', ['tab' => 'brick', 'search' => 'Jl. Baru Reassign']))
        ->assertOk()
        ->assertSee('Brand Pindah');
});

test('updating material store location through controller syncs snapshot fields', function () {
    $store = Store::create(['name' => 'TB Controller']);
    $oldLocation = StoreLocation::create([
        'store_id' => $store->id,
        'address' => 'Alamat Lama Controller',
    ]);
    $newLocation = StoreLocation::create([
        'store_id' => $store->id,
        'address' => 'Alamat Baru Controller',
    ]);

    $brick = Brick::create([
        'material_name' => 'Bata',
        'type' => 'Ringan',
        'brand' => 'Brand Controller',
        'form' => 'Solid',
        'store' => $store->name,
        'address' => $oldLocation->address,
        'store_location_id' => $oldLocation->id,
        'price_per_piece' => 1200,
    ]);

    $this->put(route('bricks.update', $brick), [
        'type' => 'Ringan',
        'brand' => 'Brand Controller',
        'form' => 'Solid',
        'store' => $store->name,
        'address' => $oldLocation->address,
        'price_per_piece' => 1200,
        'store_location_id' => $newLocation->id,
    ])->assertRedirect(route('bricks.index'));

    $brick->refresh();

    expect((int) $brick->store_location_id)->toBe((int) $newLocation->id)
        ->and($brick->address)->toBe('Alamat Baru Controller');

    $this->get(route('materials.index', ['tab' => 'brick', 'search' => 'Alamat Baru Controller']))
        ->assertOk()
        ->assertSee('Brand Controller');
});
