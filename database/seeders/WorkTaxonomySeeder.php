<?php

namespace Database\Seeders;

use App\Models\WorkArea;
use App\Models\WorkField;
use App\Models\WorkFloor;
use App\Models\WorkItemGrouping;
use App\Services\FormulaRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedNames(WorkFloor::class, [
                'Basement 1',
                'Basement 2',
                'Lantai Dasar',
                'Lantai 2',
                'Lantai 3',
                'Lantai 4',
                'Lantai 5',
            ]);

            $this->seedNames(WorkArea::class, [
                'Dapur',
                'Kamar Mandi',
                'Ruang Tamu',
                'Kamar Tidur',
                'Ruang Keluarga',
                'Ruang Makan',
                'Ruang Kerja',
                'Garasi',
                'Teras',
                'Balkon',
            ]);

            $this->seedNames(WorkField::class, [
                'Dinding',
                'Lantai',
                'Plafon',
                'Atap',                
            ]);

            $formulaCodes = collect(FormulaRegistry::codes())
                ->filter(fn ($code) => is_string($code) && trim($code) !== '')
                ->map(fn ($code) => trim($code))
                ->unique()
                ->values();

            foreach ($formulaCodes as $formulaCode) {
                WorkItemGrouping::firstOrCreate([
                    'formula_code' => $formulaCode,
                    'work_floor_id' => null,
                    'work_area_id' => null,
                    'work_field_id' => null,
                ]);
            }
        });

        $this->command?->info('Work taxonomy and default groupings seeded successfully.');
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  array<int, string>  $names
     */
    protected function seedNames(string $modelClass, array $names): void
    {
        foreach ($names as $name) {
            $modelClass::firstOrCreate(['name' => $name]);
        }
    }
}
