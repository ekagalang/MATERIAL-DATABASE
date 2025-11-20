<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            CatSeeder::class,
            BrickSeeder::class,
            CementSeeder::class,
            SandSeeder::class,
        ]);
    }
}