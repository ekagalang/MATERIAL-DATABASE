<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\NumberHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NumberHelperApiController extends Controller
{
    public function format(Request $request): JsonResponse
    {
        try {
            $values = $request->input('values');

            if ($values === null) {
                return response()->json([
                    'success' => true,
                    'data' => $this->formatItem($request->all()),
                ]);
            }

            if (!is_array($values)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payload format.',
                ], 422);
            }

            $formatted = [];
            foreach ($values as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $key = $item['key'] ?? null;
                if ($key === null || $key === '') {
                    continue;
                }
                $formatted[$key] = $this->formatItem($item);
            }

            return response()->json([
                'success' => true,
                'data' => $formatted,
            ]);
        } catch (\Throwable $e) {
            Log::error('Number format error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to format number.',
            ], 500);
        }
    }

    protected function formatItem(array $item): array
    {
        $value = $item['value'] ?? null;
        $number = $this->parseNumber($value);

        $decimals = $item['decimals'] ?? null;
        if ($decimals === '' || $decimals === null) {
            $decimals = null;
        } else {
            $decimals = (int) $decimals;
        }

        $decimalSeparator = $item['decimal_separator'] ?? ',';
        $thousandsSeparator = $item['thousands_separator'] ?? '.';

        return [
            'formatted' => NumberHelper::format($number, $decimals, $decimalSeparator, $thousandsSeparator),
            'plain' => NumberHelper::format($number, $decimals, '.', ''),
            'normalized' => NumberHelper::normalize($number, $decimals),
            'currency' => NumberHelper::currency($number),
        ];
    }

    protected function parseNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        $string = str_replace(['Rp', 'rp', ' '], '', $string);
        $hasComma = str_contains($string, ',');
        $hasDot = str_contains($string, '.');

        if ($hasComma && $hasDot) {
            $string = str_replace('.', '', $string);
            $string = str_replace(',', '.', $string);
        } elseif ($hasComma) {
            $string = str_replace(',', '.', $string);
        }

        if (!is_numeric($string)) {
            return null;
        }

        return (float) $string;
    }
}
