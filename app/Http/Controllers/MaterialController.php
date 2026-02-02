<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialSetting;
use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Sand;
use App\Models\Ceramic;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        // Load ALL materials data (not filtered)
        // JavaScript will handle showing/hiding tabs based on checkbox
        $allSettings = MaterialSetting::where('material_type', '!=', 'nat')
            ->get()
            ->sortBy(function ($setting) {
                return MaterialSetting::getMaterialLabel($setting->material_type);
            })
            ->values();

        $materials = [];
        $grandTotal = 0;

        // Determine active tab from request or default to the first one
        $activeTab = $request->query('tab');
        
        // If no tab is specified, we'll load the first one by default.
        // However, we don't know the user's preference order on server-side.
        // We'll trust that if 'tab' is missing, we load the first one in the list.
        $firstType = $allSettings->first()->material_type ?? 'brick';
        $targetTab = $activeTab ?: $firstType;

        foreach ($allSettings as $setting) {
            $type = $setting->material_type;

            // Get model for count
            $model = null;
            switch ($type) {
                case 'brick':
                    $model = Brick::class;
                    break;
                case 'cat':
                    $model = Cat::class;
                    break;
                case 'ceramic':
                    $model = Ceramic::class;
                    break;
                case 'sand':
                    $model = Sand::class;
                    break;
                case 'cement':
                    $model = Cement::class;
                    break;
            }

            $dbCount = $model ? $model::count() : 0;
            $grandTotal += $dbCount;

            // Get active letters for this material type
            $activeLetters = $this->getActiveLetters($type);

            // Lazy Load Logic: Only fetch data if it's the target tab
            $isLoaded = ($type === $targetTab);
            
            if ($isLoaded) {
                $data = $this->getMaterialData($type, $request);
            } else {
                // Return empty collection for non-active tabs to avoid heavy queries
                $data = collect();
            }

            $materials[] = [
                'type' => $type,
                'label' => MaterialSetting::getMaterialLabel($type),
                'data' => $data,
                'count' => $isLoaded ? $data->count() : 0, // Filtered count only if loaded
                'db_count' => $dbCount, // Absolute total for this type
                'active_letters' => $activeLetters,
                'is_loaded' => $isLoaded,
            ];
        }

        return view('materials.index', compact('materials', 'allSettings', 'grandTotal'));
    }

    public function fetchTab(Request $request, $type)
    {
        // Validate type
        if (!in_array($type, ['brick', 'cat', 'cement', 'sand', 'ceramic'])) {
            abort(404);
        }

        // Calculate Grand Total (needed for footer) - can be cached or simplified
        // For consistency, we recalculate it or pass a placeholder if not strictly needed in the partial
        // Ideally, grandTotal should be passed from the main view or recalculated.
        // Recalculating is cheap (count queries).
        $grandTotal = 0;
        $allSettings = MaterialSetting::where('material_type', '!=', 'nat')->get();
        foreach ($allSettings as $setting) {
             $modelClass = match($setting->material_type) {
                 'brick' => Brick::class,
                 'cat' => Cat::class,
                 'ceramic' => Ceramic::class,
                 'sand' => Sand::class,
                 'cement' => Cement::class,
                 default => null
             };
             if ($modelClass) {
                 $grandTotal += $modelClass::count();
             }
        }

        $data = $this->getMaterialData($type, $request);
        $model = match($type) {
             'brick' => Brick::class,
             'cat' => Cat::class,
             'ceramic' => Ceramic::class,
             'sand' => Sand::class,
             'cement' => Cement::class,
             default => null
        };
        $dbCount = $model ? $model::count() : 0;

        $material = [
            'type' => $type,
            'label' => MaterialSetting::getMaterialLabel($type),
            'data' => $data,
            'count' => $data->count(),
            'db_count' => $dbCount,
            'active_letters' => $this->getActiveLetters($type),
            'is_loaded' => true,
        ];

        return view('materials.partials.table', compact('material', 'grandTotal'));
    }

    public function typeSuggestions(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $normalizedSearch = strtolower(trim(preg_replace('/[^\pL\pN]+/u', ' ', $search)));
        $tokens = array_values(array_filter(explode(' ', $normalizedSearch)));
        $materialTokenMap = [
            'bata' => 'brick',
            'brick' => 'brick',
            'cat' => 'cat',
            'semen' => 'cement',
            'cement' => 'cement',
            'pasir' => 'sand',
            'sand' => 'sand',
            'keramik' => 'ceramic',
            'ceramic' => 'ceramic',
        ];
        $materialTokens = [];
        $searchTokens = [];
        foreach ($tokens as $token) {
            if (isset($materialTokenMap[$token])) {
                $materialTokens[] = $materialTokenMap[$token];
            } else {
                $searchTokens[] = $token;
            }
        }
        $targetMaterialTypes = array_values(array_unique($materialTokens));
        $models = [
            'brick' => Brick::class,
            'cat' => Cat::class,
            'cement' => Cement::class,
            'sand' => Sand::class,
            'ceramic' => Ceramic::class,
        ];
        if ($search === '') {
            $items = collect();
            foreach ($models as $materialType => $model) {
                $types = $model
                    ::query()
                    ->select('type')
                    ->whereNotNull('type')
                    ->where('type', '!=', '')
                    ->distinct()
                    ->orderBy('type')
                    ->limit(25)
                    ->pluck('type');

                foreach ($types as $type) {
                    $items->push([
                        'material_type' => $materialType,
                        'type' => $type,
                        'label' => $type,
                    ]);
                }
            }

            return response()->json(['items' => $items->sortBy('label')->values()]);
        }
        $materialLabels = [
            'brick' => 'Bata',
            'cat' => 'Cat',
            'cement' => 'Semen',
            'sand' => 'Pasir',
            'ceramic' => 'Keramik',
        ];
        $labelColumns = [
            'brick' => ['material_name', 'brand', 'form', 'type'],
            'cat' => ['cat_name', 'brand', 'sub_brand', 'color_name', 'type'],
            'cement' => ['cement_name', 'brand', 'sub_brand', 'code', 'color', 'type'],
            'sand' => ['sand_name', 'brand', 'type'],
            'ceramic' => ['material_name', 'brand', 'sub_brand', 'code', 'color', 'form', 'type'],
        ];
        $searchColumns = [
            'brick' => ['type', 'material_name', 'brand', 'form'],
            'cat' => ['type', 'cat_name', 'brand', 'sub_brand', 'color_name'],
            'cement' => ['type', 'cement_name', 'brand', 'sub_brand', 'code', 'color'],
            'sand' => ['type', 'sand_name', 'brand'],
            'ceramic' => ['type', 'material_name', 'brand', 'sub_brand', 'code', 'color', 'form'],
        ];

        $items = collect();
        foreach ($models as $materialType => $model) {
            if (!empty($targetMaterialTypes) && !in_array($materialType, $targetMaterialTypes, true)) {
                continue;
            }
            $materialLabel = $materialLabels[$materialType] ?? ucfirst($materialType);
            $columns = array_unique(
                array_merge(['type'], $labelColumns[$materialType] ?? [], $searchColumns[$materialType] ?? []),
            );
            $query = $model::query()->select($columns)->whereNotNull('type')->where('type', '!=', '');

            $columns = $searchColumns[$materialType] ?? [];
            if (!empty($searchTokens)) {
                foreach ($searchTokens as $token) {
                    $like = '%' . $token . '%';
                    $query->where(function ($builder) use ($like, $columns) {
                        foreach ($columns as $column) {
                            $builder->orWhere($column, 'like', $like);
                        }
                    });
                }
            }

            $results = collect();
            if (!empty($searchTokens)) {
                $results = $query->orderBy('type')->limit(20)->get();
            }
            foreach ($results as $row) {
                $label = $this->buildSuggestionLabel($materialType, $row);
                if ($label === '') {
                    continue;
                }
                $items->push([
                    'material_type' => $materialType,
                    'type' => $row->type,
                    'label' => $label,
                ]);
            }

            if ($search !== '') {
                $labelMatches = stripos($materialLabel, $search) !== false || stripos($materialType, $search) !== false;
                if (!$labelMatches && !empty($tokens)) {
                    foreach ($tokens as $token) {
                        if ($token !== '' && (stripos($materialLabel, $token) !== false || stripos($materialType, $token) !== false)) {
                            $labelMatches = true;
                            break;
                        }
                    }
                }
                if ($labelMatches) {
                    $items->push([
                        'material_type' => $materialType,
                        'type' => $materialLabel,
                        'label' => $materialLabel,
                    ]);
                }

                if (empty($searchTokens)) {
                    $typeMatches = $model
                        ::query()
                        ->select('type')
                        ->whereNotNull('type')
                        ->where('type', '!=', '')
                        ->distinct()
                        ->orderBy('type')
                        ->limit(10)
                        ->pluck('type');
                } else {
                    $typeQuery = $model::query()->select('type')->whereNotNull('type')->where('type', '!=', '');
                    foreach ($searchTokens as $token) {
                        $like = '%' . $token . '%';
                        $typeQuery->where('type', 'like', $like);
                    }
                    $typeMatches = $typeQuery->distinct()->orderBy('type')->limit(10)->pluck('type');
                }

                foreach ($typeMatches as $type) {
                    $items->push([
                        'material_type' => $materialType,
                        'type' => $type,
                        'label' => $type,
                    ]);
                }
            }
        }

        $items = $items
            ->unique(function ($item) {
                return $item['material_type'] . '|' . $item['label'];
            })
            ->sortBy('label')
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    private function buildSuggestionLabel(string $materialType, $row): string
    {
        $parts = [];
        switch ($materialType) {
            case 'brick':
                $parts = [
                    $row->type ?? null,
                    $row->brand ?? null,
                    $row->form ?? null,
                ];
                break;
            case 'cat':
                $parts = [
                    $row->brand ?? null,
                    $row->sub_brand ?? null,
                    $row->color_name ?? null,
                    $row->type ?? null,
                ];
                break;
            case 'cement':
                $parts = [
                    $row->type ?? null,
                    $row->brand ?? null,
                    $row->sub_brand ?? null,
                    $row->code ?? null,
                    $row->color ?? null,
                ];
                break;
            case 'sand':
                $parts = [
                    $row->type ?? null,
                    $row->brand ?? null,
                ];
                break;
            case 'ceramic':
                $parts = [
                    $row->type ?? null,
                    $row->brand ?? null,
                    $row->sub_brand ?? null,
                    $row->code ?? null,
                    $row->color ?? null,
                    $row->form ?? null,
                ];
                break;
            default:
                $parts = [$row->type ?? null];
                break;
        }

        $parts = array_values(array_filter($parts, function ($value) {
            return !is_null($value) && trim((string) $value) !== '';
        }));

        $parts = array_slice($parts, 0, 3);
        return trim(implode(' - ', $parts));
    }

    private function getActiveLetters($type)
    {
        $model = null;
        switch ($type) {
            case 'brick':
                $model = Brick::class;
                break;
            case 'cat':
                $model = Cat::class;
                break;
            case 'cement':
                $model = Cement::class;
                break;
            case 'sand':
                $model = Sand::class;
                break;
            case 'ceramic':
                $model = Ceramic::class;
                break;
        }

        if (!$model) {
            return [];
        }

        // Changed to 'type' based on user request to paginate by Type
        $letterColumn = 'type';

        // Get distinct first letters, uppercase
        return $model
            ::selectRaw(sprintf('DISTINCT UPPER(SUBSTRING(%s, 1, 1)) as letter', $letterColumn))
            ->whereNotNull($letterColumn)
            ->where($letterColumn, '!=', '')
            ->orderBy('letter')
            ->pluck('letter')
            ->toArray();
    }

    private function getMaterialData($type, $request)
    {
        $search = $request->get('search');
        $sortBy = $request->get('sort_by');
        $sortDirection = strtolower((string) $request->get('sort_direction', 'asc'));
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        $query = null;

        switch ($type) {
            case 'brick':
                $query = Brick::query();
                break;
            case 'cat':
                $query = Cat::query()->with('packageUnit');
                break;
            case 'ceramic':
                $query = Ceramic::query();
                break;
            case 'cement':
                $query = Cement::query()->with('packageUnit');
                break;
            case 'sand':
                $query = Sand::query()->with('packageUnit');
                break;
        }

        if (!$query) {
            return null;
        }

        // Apply Search across all table fields
        if ($search) {
            $searchColumns = match ($type) {
                'brick' => [
                    'type',
                    'brand',
                    'form',
                    'dimension_length',
                    'dimension_width',
                    'dimension_height',
                    'package_volume',
                    'store',
                    'address',
                    'price_per_piece',
                    'comparison_price_per_m3',
                ],
                'sand' => [
                    'type',
                    'brand',
                    'package_unit',
                    'dimension_length',
                    'dimension_width',
                    'dimension_height',
                    'package_volume',
                    'store',
                    'address',
                    'package_price',
                    'comparison_price_per_m3',
                    'sand_name',
                ],
                'cat' => [
                    'type',
                    'brand',
                    'sub_brand',
                    'color_code',
                    'color_name',
                    'package_unit',
                    'volume',
                    'package_weight_gross',
                    'package_weight_net',
                    'store',
                    'address',
                    'purchase_price',
                    'comparison_price_per_kg',
                    'cat_name',
                ],
                'cement' => [
                    'type',
                    'brand',
                    'sub_brand',
                    'code',
                    'color',
                    'package_unit',
                    'package_weight_net',
                    'store',
                    'address',
                    'package_price',
                    'comparison_price_per_kg',
                    'cement_name',
                ],
                'ceramic' => [
                    'type',
                    'brand',
                    'sub_brand',
                    'code',
                    'color',
                    'form',
                    'surface',
                    'packaging',
                    'pieces_per_package',
                    'coverage_per_package',
                    'dimension_length',
                    'dimension_width',
                    'dimension_thickness',
                    'store',
                    'address',
                    'price_per_package',
                    'comparison_price_per_m2',
                    'material_name',
                ],
                default => ['brand', 'store'],
            };

            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $allowedSortBy = match ($type) {
            'brick' => [
                'type',
                'brand',
                'form',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_volume',
                'store',
                'address',
                'price_per_piece',
                'comparison_price_per_m3',
            ],
            'sand' => [
                'type',
                'brand',
                'package_unit',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_volume',
                'store',
                'address',
                'package_price',
                'comparison_price_per_m3',
            ],
            'cat' => [
                'type',
                'brand',
                'sub_brand',
                'color_code',
                'color_name',
                'package_unit',
                'volume',
                'package_weight_net',
                'store',
                'address',
                'purchase_price',
                'comparison_price_per_kg',
            ],
            'cement' => [
                'type',
                'brand',
                'sub_brand',
                'code',
                'color',
                'package_unit',
                'package_weight_net',
                'store',
                'address',
                'package_price',
                'comparison_price_per_kg',
            ],
            'ceramic' => [
                'type',
                'brand',
                'sub_brand',
                'code',
                'color',
                'form',
                'surface',
                'packaging',
                'pieces_per_package',
                'coverage_per_package',
                'dimension_length',
                'dimension_width',
                'dimension_thickness',
                'store',
                'address',
                'price_per_package',
                'comparison_price_per_m2',
            ],
            default => [],
        };

        if ($sortBy && !in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = null;
        }

        if ($sortBy) {
            // Mapping for special columns if needed
            $sortColumn = $sortBy;
            if ($type == 'ceramic' && $sortBy == 'dimension_length') {
                $query
                    ->orderBy('dimension_length', $sortDirection)
                    ->orderBy('dimension_width', $sortDirection)
                    ->orderBy('dimension_thickness', $sortDirection);
            } elseif (in_array($type, ['brick', 'sand']) && $sortBy == 'dimension_length') {
                $query
                    ->orderBy('dimension_length', $sortDirection)
                    ->orderBy('dimension_width', $sortDirection)
                    ->orderBy('dimension_height', $sortDirection);
            } else {
                $query->orderBy($sortColumn, $sortDirection);
            }

            return $query->limit(1000)->get();
        }

        $defaultOrderBy = match ($type) {
            'brick' => [
                'type',
                'brand',
                'form',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_volume',
                'store',
                'address',
                'price_per_piece',
                'comparison_price_per_m3',
                'id',
            ],
            'sand' => [
                'type',
                'brand',
                'package_unit',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_volume',
                'store',
                'address',
                'package_price',
                'comparison_price_per_m3',
                'id',
            ],
            'cat' => [
                'type',
                'brand',
                'sub_brand',
                'color_code',
                'color_name',
                'package_unit',
                'package_weight_gross',
                'volume',
                'package_weight_net',
                'store',
                'address',
                'purchase_price',
                'comparison_price_per_kg',
                'id',
            ],
            'cement' => [
                'type',
                'brand',
                'sub_brand',
                'code',
                'color',
                'package_unit',
                'package_weight_net',
                'store',
                'address',
                'package_price',
                'comparison_price_per_kg',
                'id',
            ],
            'ceramic' => [
                'type',
                'brand',
                'dimension_length',
                'dimension_width',
                'dimension_thickness',
                'sub_brand',
                'surface',
                'code',
                'color',
                'form',
                'packaging',
                'pieces_per_package',
                'coverage_per_package',
                'store',
                'address',
                'price_per_package',
                'comparison_price_per_m2',
                'id',
            ],
            default => [
                'type',
                'created_at',
                'id',
            ],
        };

        foreach ($defaultOrderBy as $column) {
            $query->orderBy($column, 'asc');
        }

        // Limit to 1000 rows to prevent memory exhaustion/slow rendering
        return $query->limit(1000)->get();
    }
}
