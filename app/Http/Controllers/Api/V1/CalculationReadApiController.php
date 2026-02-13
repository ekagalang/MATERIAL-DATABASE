<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalculationReadApiController extends CalculationApiController
{
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

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $calculation = $this->repository->findCalculation($id);

            if (!$calculation) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Calculation not found',
                    ],
                    404,
                );
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

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }
}
