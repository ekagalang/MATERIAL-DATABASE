<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Calculation\CalculationOrchestrationService;
use App\Services\FormulaRegistry;
use App\Repositories\CalculationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Calculation API Controller
 *
 * REST API endpoints for material calculations
 * Following the same clean architecture pattern as Material APIs
 *
 * @group Calculation Management
 */
class CalculationApiController extends Controller
{
    protected CalculationOrchestrationService $orchestrationService;
    protected CalculationRepository $repository;

    public function __construct(
        CalculationOrchestrationService $orchestrationService,
        CalculationRepository $repository
    ) {
        $this->orchestrationService = $orchestrationService;
        $this->repository = $repository;
    }

    /**
     * Calculate brick materials
     *
     * Generate combinations of cement and sand for given brick(s) and parameters.
     * Supports multiple price filters: best, common, cheapest, medium, expensive, custom, all
     *
     * @bodyParam work_type string required Formula code (e.g., 'brick_half', 'brick_full', 'wall_plastering'). Example: brick_half
     * @bodyParam wall_length numeric required Wall length in meters. Example: 10
     * @bodyParam wall_height numeric required Wall height in meters. Example: 3
     * @bodyParam mortar_thickness numeric required Mortar thickness in cm. Example: 1.5
     * @bodyParam installation_type_id integer required Installation type ID. Example: 1
     * @bodyParam mortar_formula_id integer required Mortar formula ID. Example: 1
     * @bodyParam brick_ids array optional Array of brick IDs to calculate. Example: [1, 2, 3]
     * @bodyParam brick_id integer optional Single brick ID. Example: 1
     * @bodyParam price_filters array optional Price filters to apply. Example: ["best", "cheapest"]
     * @bodyParam cement_id integer optional Specific cement ID for custom filter. Example: 5
     * @bodyParam sand_id integer optional Specific sand ID for custom filter. Example: 3
     * @bodyParam layer_count integer optional Number of layers for Rollag formula. Example: 3
     * @bodyParam plaster_sides integer optional Number of sides for wall plastering. Example: 2
     * @bodyParam skim_sides integer optional Number of sides for skim coating. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "projects": [{
     *       "brick": {...},
     *       "combinations": {
     *         "TerBAIK 1": [{...}],
     *         "TerMURAH 1": [{...}]
     *       }
     *     }],
     *     "formulaName": "Pekerjaan 1/2 Bata",
     *     "requestData": {...}
     *   }
     * }
     */
    public function calculate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'work_type' => 'required|string',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.01|max:10',
                'installation_type_id' => 'required|exists:brick_installation_types,id',
                'mortar_formula_id' => 'required|exists:mortar_formulas,id',
                'brick_ids' => 'nullable|array',
                'brick_ids.*' => 'exists:bricks,id',
                'brick_id' => 'nullable|exists:bricks,id',
                'price_filters' => 'nullable|array',
                'price_filters.*' => 'in:best,common,cheapest,medium,expensive,custom,all',
                'cement_id' => 'nullable|exists:cements,id',
                'sand_id' => 'nullable|exists:sands,id',
                'layer_count' => 'nullable|integer|min:1',
                'plaster_sides' => 'nullable|integer|min:1|max:2',
                'skim_sides' => 'nullable|integer|min:1|max:2',
            ]);

            $result = $this->orchestrationService->generateCombinations($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Calculate Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Preview single calculation
     *
     * Calculate materials for a single brick with specific cement and sand.
     * Returns calculation without saving to database.
     *
     * @bodyParam work_type string required Formula code. Example: brick_half
     * @bodyParam wall_length numeric required Wall length in meters. Example: 10
     * @bodyParam wall_height numeric required Wall height in meters. Example: 3
     * @bodyParam mortar_thickness numeric required Mortar thickness in cm. Example: 1.5
     * @bodyParam installation_type_id integer required Installation type ID. Example: 1
     * @bodyParam mortar_formula_id integer required Mortar formula ID. Example: 1
     * @bodyParam brick_id integer required Brick ID. Example: 1
     * @bodyParam cement_id integer required Cement ID. Example: 5
     * @bodyParam sand_id integer required Sand ID. Example: 3
     * @bodyParam layer_count integer optional Number of layers for Rollag. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "calculation": {...},
     *     "summary": {...}
     *   }
     * }
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'work_type' => 'required|string',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.01|max:10',
                'installation_type_id' => 'required|exists:brick_installation_types,id',
                'mortar_formula_id' => 'required|exists:mortar_formulas,id',
                'brick_id' => 'required|exists:bricks,id',
                'cement_id' => 'required|exists:cements,id',
                'sand_id' => 'required|exists:sands,id',
                'layer_count' => 'nullable|integer|min:1',
                'plaster_sides' => 'nullable|integer|min:1|max:2',
                'skim_sides' => 'nullable|integer|min:1|max:2',
                'project_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            $calculation = $this->orchestrationService->preview($validated);

            return response()->json([
                'success' => true,
                'data' => [
                    'calculation' => $calculation,
                    'summary' => $calculation->getSummary(),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Preview Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store calculation
     *
     * Save calculation result to database.
     *
     * @bodyParam work_type string required Formula code. Example: brick_half
     * @bodyParam wall_length numeric required Wall length in meters. Example: 10
     * @bodyParam wall_height numeric required Wall height in meters. Example: 3
     * @bodyParam mortar_thickness numeric required Mortar thickness in cm. Example: 1.5
     * @bodyParam installation_type_id integer required Installation type ID. Example: 1
     * @bodyParam mortar_formula_id integer required Mortar formula ID. Example: 1
     * @bodyParam brick_id integer required Brick ID. Example: 1
     * @bodyParam cement_id integer required Cement ID. Example: 5
     * @bodyParam sand_id integer required Sand ID. Example: 3
     * @bodyParam project_name string optional Project name. Example: "Rumah Pak Budi"
     * @bodyParam notes string optional Additional notes. Example: "Lantai 1"
     * @bodyParam layer_count integer optional Number of layers for Rollag. Example: 3
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Calculation saved successfully",
     *   "data": {
     *     "id": 123,
     *     "calculation": {...},
     *     "summary": {...}
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'work_type' => 'required|string',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.01|max:10',
                'installation_type_id' => 'required|exists:brick_installation_types,id',
                'mortar_formula_id' => 'required|exists:mortar_formulas,id',
                'brick_id' => 'required|exists:bricks,id',
                'cement_id' => 'required|exists:cements,id',
                'sand_id' => 'required|exists:sands,id',
                'project_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'layer_count' => 'nullable|integer|min:1',
                'plaster_sides' => 'nullable|integer|min:1|max:2',
                'skim_sides' => 'nullable|integer|min:1|max:2',
            ]);

            $calculation = $this->orchestrationService->store($validated);
            $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand']);

            return response()->json([
                'success' => true,
                'message' => 'Calculation saved successfully',
                'data' => [
                    'id' => $calculation->id,
                    'calculation' => $calculation,
                    'summary' => $calculation->getSummary(),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Store Calculation Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Compare multiple bricks
     *
     * Compare material costs for multiple bricks using same mortar materials.
     * Uses cheapest cement and sand for fair comparison.
     *
     * @bodyParam brick_ids array required Array of brick IDs to compare. Example: [1, 2, 3]
     * @bodyParam wall_length numeric required Wall length in meters. Example: 10
     * @bodyParam wall_height numeric required Wall height in meters. Example: 3
     * @bodyParam mortar_thickness numeric required Mortar thickness in cm. Example: 1.5
     * @bodyParam installation_type_id integer required Installation type ID. Example: 1
     * @bodyParam work_type string optional Formula code. Example: brick_half
     * @bodyParam layer_count integer optional Number of layers for Rollag. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "comparisons": [{
     *       "brick": {...},
     *       "result": {...},
     *       "total_cost": 5000000,
     *       "cost_per_m2": 166666.67
     *     }],
     *     "materials": {
     *       "cement": {...},
     *       "sand": {...},
     *       "mortar_formula": {...}
     *     }
     *   }
     * }
     */
    public function compare(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'brick_ids' => 'required|array|min:2',
                'brick_ids.*' => 'exists:bricks,id',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.01|max:10',
                'installation_type_id' => 'required|exists:brick_installation_types,id',
                'work_type' => 'nullable|string',
                'layer_count' => 'nullable|integer|min:1',
            ]);

            $result = $this->orchestrationService->compareBricks($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Compare Bricks Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Trace calculation step-by-step
     *
     * Get detailed step-by-step calculation trace for debugging and transparency.
     * Mode 1 Professional only (most accurate and complete).
     *
     * @bodyParam formula_code string required Formula code. Example: brick_half
     * @bodyParam wall_length numeric required Wall length in meters. Example: 10
     * @bodyParam wall_height numeric required Wall height in meters. Example: 3
     * @bodyParam installation_type_id integer required Installation type ID. Example: 1
     * @bodyParam mortar_thickness numeric required Mortar thickness in cm. Example: 1.5
     * @bodyParam mortar_formula_id integer required Mortar formula ID. Example: 1
     * @bodyParam brick_id integer optional Brick ID. Example: 1
     * @bodyParam cement_id integer optional Cement ID. Example: 5
     * @bodyParam sand_id integer optional Sand ID. Example: 3
     * @bodyParam custom_cement_ratio numeric optional Custom cement ratio. Example: 1
     * @bodyParam custom_sand_ratio numeric optional Custom sand ratio. Example: 4
     * @bodyParam has_additional_layer boolean optional Has additional layer. Example: false
     * @bodyParam layer_count integer optional Number of layers for Rollag. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "steps": [...],
     *     "final_result": {...}
     *   }
     * }
     */
    public function trace(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'formula_code' => 'required|string',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'installation_type_id' => 'required|exists:brick_installation_types,id',
                'mortar_thickness' => 'required|numeric|min:0.01|max:10',
                'mortar_formula_id' => 'required|exists:mortar_formulas,id',
                'brick_id' => 'nullable|exists:bricks,id',
                'cement_id' => 'nullable|exists:cements,id',
                'sand_id' => 'nullable|exists:sands,id',
                'custom_cement_ratio' => 'nullable|numeric|min:1',
                'custom_sand_ratio' => 'nullable|numeric|min:1',
                'has_additional_layer' => 'nullable|boolean',
                'layer_count' => 'nullable|integer|min:1', // For Rollag formula
            ]);

            // Get formula instance from registry
            $formulaCode = $validated['formula_code'];
            $formula = FormulaRegistry::instance($formulaCode);

            if (!$formula) {
                return response()->json([
                    'success' => false,
                    'message' => "Formula dengan code '{$formulaCode}' tidak ditemukan",
                ], 404);
            }

            // Validate parameters using formula's validate method
            if (!$formula->validate($validated)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tidak valid untuk formula ini',
                ], 422);
            }

            // Execute trace calculation
            $trace = $formula->trace($validated);

            return response()->json([
                'success' => true,
                'data' => $trace,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Trace Calculation Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get calculation log
     *
     * Get paginated list of saved calculations with filters.
     *
     * @queryParam search string Search by project name or notes. Example: "Rumah Pak Budi"
     * @queryParam work_type string Filter by work type. Example: brick_half
     * @queryParam date_from date Filter by start date. Example: 2025-01-01
     * @queryParam date_to date Filter by end date. Example: 2025-12-31
     * @queryParam per_page integer Items per page (max 100). Example: 15
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 50
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->integer('per_page', 15), 100);

            $filters = [
                'search' => $request->input('search'),
                'work_type' => $request->input('work_type'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ];

            $calculations = $this->repository->getCalculationLog($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $calculations->items(),
                'meta' => [
                    'current_page' => $calculations->currentPage(),
                    'per_page' => $calculations->perPage(),
                    'total' => $calculations->total(),
                    'last_page' => $calculations->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Calculations Error:', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get single calculation
     *
     * Retrieve a specific calculation by ID.
     *
     * @urlParam id integer required Calculation ID. Example: 123
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "calculation": {...},
     *     "summary": {...}
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Calculation not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        try {
            $calculation = $this->repository->findCalculation($id);

            if (!$calculation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calculation not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'calculation' => $calculation,
                    'summary' => $calculation->getSummary(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Calculation Error:', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update calculation
     *
     * Update an existing calculation with new parameters.
     * Recalculates all values based on new inputs.
     *
     * @urlParam id integer required Calculation ID. Example: 123
     * @bodyParam work_type string required Formula code. Example: brick_half
     * @bodyParam wall_length numeric required Wall length in meters. Example: 10
     * @bodyParam wall_height numeric required Wall height in meters. Example: 3
     * @bodyParam mortar_thickness numeric required Mortar thickness in cm. Example: 1.5
     * @bodyParam installation_type_id integer required Installation type ID. Example: 1
     * @bodyParam mortar_formula_id integer required Mortar formula ID. Example: 1
     * @bodyParam brick_id integer optional Brick ID. Example: 1
     * @bodyParam cement_id integer optional Cement ID. Example: 5
     * @bodyParam sand_id integer optional Sand ID. Example: 3
     * @bodyParam project_name string optional Project name. Example: "Rumah Pak Budi"
     * @bodyParam notes string optional Additional notes. Example: "Lantai 1"
     * @bodyParam layer_count integer optional Number of layers for Rollag. Example: 3
     * @bodyParam plaster_sides integer optional Number of sides for plastering. Example: 2
     * @bodyParam skim_sides integer optional Number of sides for skim coating. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Calculation updated successfully",
     *   "data": {
     *     "calculation": {...},
     *     "summary": {...}
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Calculation not found"
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Find existing calculation
            $existingCalculation = $this->repository->findCalculation($id);

            if (!$existingCalculation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calculation not found',
                ], 404);
            }

            $validated = $request->validate([
                'work_type' => 'nullable|string',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'installation_type_id' => 'required|exists:brick_installation_types,id',
                'mortar_thickness' => 'required|numeric|min:0.01|max:10',
                'mortar_formula_id' => 'required|exists:mortar_formulas,id',
                'brick_id' => 'nullable|exists:bricks,id',
                'cement_id' => 'nullable|exists:cements,id',
                'sand_id' => 'nullable|exists:sands,id',
                'project_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'layer_count' => 'nullable|integer|min:1',
                'plaster_sides' => 'nullable|integer|min:1|max:2',
                'skim_sides' => 'nullable|integer|min:1|max:2',
            ]);

            // Add work_type from existing if not provided
            if (!isset($validated['work_type'])) {
                $params = $existingCalculation->calculation_params;
                $validated['work_type'] = $params['work_type'] ?? 'brick_half';
            }

            // Perform new calculation
            $newCalculation = $this->orchestrationService->calculateSingle($validated, false);

            // Update existing record
            $existingCalculation->fill($newCalculation->toArray());
            $existingCalculation->save();
            $existingCalculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand']);

            return response()->json([
                'success' => true,
                'message' => 'Calculation updated successfully',
                'data' => [
                    'calculation' => $existingCalculation,
                    'summary' => $existingCalculation->getSummary(),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update Calculation Error:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete calculation
     *
     * Remove a calculation from the database.
     *
     * @urlParam id integer required Calculation ID. Example: 123
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Calculation deleted successfully"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Calculation not found"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $calculation = $this->repository->findCalculation($id);

            if (!$calculation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calculation not found',
                ], 404);
            }

            $calculation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Calculation deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Calculation Error:', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete calculation: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Compare installation types
     *
     * Compare different installation types (1/2 bata, 1 bata, rollag, etc.)
     * using the same brick and materials.
     * Useful for deciding which installation type is most cost-effective.
     *
     * @bodyParam wall_length numeric required Wall length in meters. Example: 10
     * @bodyParam wall_height numeric required Wall height in meters. Example: 3
     * @bodyParam mortar_thickness numeric required Mortar thickness in cm. Example: 1.5
     * @bodyParam mortar_formula_id integer required Mortar formula ID. Example: 1
     * @bodyParam brick_id integer optional Brick ID. Example: 1
     * @bodyParam cement_id integer optional Cement ID. Example: 5
     * @bodyParam sand_id integer optional Sand ID. Example: 3
     * @bodyParam layer_count integer optional Number of layers for Rollag. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "data": [{
     *     "installation_type": "1/2 Bata",
     *     "brick_quantity": 2860,
     *     "mortar_volume": 0.625,
     *     "cement_50kg": 4.5,
     *     "sand_m3": 0.469,
     *     "water_liters": 187,
     *     "total_cost": 2028409.23
     *   }]
     * }
     */
    public function compareInstallationTypes(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.01|max:10',
                'mortar_formula_id' => 'required|exists:mortar_formulas,id',
                'brick_id' => 'nullable|exists:bricks,id',
                'cement_id' => 'nullable|exists:cements,id',
                'sand_id' => 'nullable|exists:sands,id',
                'layer_count' => 'nullable|integer|min:1',
            ]);

            $installationTypes = $this->repository->getInstallationTypesAndFormulas()['installationTypes'];
            $comparisons = [];

            foreach ($installationTypes as $type) {
                $params = array_merge($validated, [
                    'installation_type_id' => $type->id,
                    'work_type' => 'brick_half', // Default, will be overridden by formula
                ]);

                try {
                    $calculation = $this->orchestrationService->calculateSingle($params, false);
                    $calculation->load(['installationType', 'mortarFormula']);

                    $comparisons[] = [
                        'installation_type' => $type->name,
                        'installation_type_code' => $type->code,
                        'brick_quantity' => $calculation->brick_quantity,
                        'mortar_volume' => $calculation->mortar_volume,
                        'cement_40kg' => $calculation->cement_quantity_40kg,
                        'cement_50kg' => $calculation->cement_quantity_50kg,
                        'sand_m3' => $calculation->sand_m3,
                        'water_liters' => $calculation->water_liters,
                        'total_cost' => $calculation->total_material_cost,
                    ];
                } catch (\Exception $e) {
                    Log::error('Compare Installation Type Error:', [
                        'type_id' => $type->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Skip this type if calculation fails
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $comparisons,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Compare Installation Types Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
