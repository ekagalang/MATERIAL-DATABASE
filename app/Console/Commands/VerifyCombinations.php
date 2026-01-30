<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Brick;
use App\Models\Cement;
use App\Models\Sand;
use App\Services\Calculation\CombinationGenerationService;
use Illuminate\Http\Request;
use App\Services\FormulaRegistry;

class VerifyCombinations extends Command
{
    protected $signature = 'verify:combinations {brick_id} {work_type=brick_half}';
    protected $description = 'Verify calculation combinations logic and counts';

    public function handle(CombinationGenerationService $service)
    {
        $brickId = $this->argument('brick_id');
        $workType = $this->argument('work_type');
        
        $brick = Brick::find($brickId);
        if (!$brick) {
            $this->error("Brick ID $brickId not found.");
            return;
        }

        $this->info("Verifying combinations for Brick: {$brick->brand} (ID: $brickId)");
        $this->info("Work Type: $workType");

        // 1. Count Raw Potential
        $cements = Cement::where('type', '!=', 'Nat')->orWhereNull('type')->where('package_price', '>', 0)->count();
        $sands = Sand::where('package_price', '>', 0)->count();
        
        $this->info("------------------------------------------------");
        $this->info("Raw Data Availability:");
        $this->info("Valid Cements: $cements");
        $this->info("Valid Sands:   $sands");
        $this->info("Potential Combinations (Cement x Sand): " . ($cements * $sands));
        $this->info("------------------------------------------------");

        // 2. Run Service Calculation
        $request = new Request([
            'wall_length' => 10,
            'wall_height' => 3,
            'mortar_thickness' => 2,
            'installation_type_id' => 1, // Assumes ID 1 exists
            'mortar_formula_id' => 1,    // Assumes ID 1 exists
            'work_type' => $workType,
            'price_filters' => ['all'],  // Request ALL filters
            'brick_id' => $brickId
        ]);

        $start = microtime(true);
        $results = $service->calculateCombinationsForBrick($brick, $request);
        $duration = round((microtime(true) - $start) * 1000, 2);

        $this->info("Service Calculation Duration: {$duration}ms");
        $this->info("Total Result Groups: " . count($results));

        $totalCombos = 0;
        foreach ($results as $label => $group) {
            $count = count($group);
            $totalCombos += $count;
            $this->line(" - $label: $count items");
        }
        
        $this->info("Total Unique Combinations Returned: $totalCombos");
        $this->info("------------------------------------------------");
        
        // 3. Explanation
        $this->comment("NOTE: Result count is lower than potential because:");
        $this->comment("1. Service filters 'Best' (limit 1-3), 'Cheapest' (limit 3), 'Expensive' (limit 3), etc.");
        $this->comment("2. 'Common' is limited to top 3 historical.");
        $this->comment("3. Duplicates between categories are merged (e.g., Best might also be Cheapest).");
    }
}
