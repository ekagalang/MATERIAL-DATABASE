<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\MaterialSetting;
use App\Models\Nat;
use App\Models\Sand;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        // Load ALL materials data (not filtered)
        // JavaScript will handle showing/hiding tabs based on checkbox
        $allSettings = $this->getDisplayMaterialSettings();

        $materials = [];
        $grandTotal = 0;

        // Determine active tab from request or default to the first one
        $activeTab = $this->normalizeDisplayMaterialType((string) $request->query('tab', ''));

        // If no tab is specified, we'll load the first one by default.
        // However, we don't know the user's preference order on server-side.
        // We'll trust that if 'tab' is missing, we load the first one in the list.
        $firstType = $this->normalizeDisplayMaterialType((string) ($allSettings->first()->material_type ?? 'brick'));
        $targetTab = $activeTab ?: $firstType;

        foreach ($allSettings as $setting) {
            $type = $this->normalizeDisplayMaterialType((string) $setting->material_type);

            $dbCount = $this->countDisplayMaterialRecordsByType($type);
            $grandTotal += $dbCount;

            // Get active letters for this material type
            $activeLetters = $this->getDisplayActiveLetters($type);

            // Lazy Load Logic: Only fetch data if it's the target tab
            $isLoaded = $type === $targetTab;

            if ($isLoaded) {
                $data = $this->getDisplayMaterialData($type, $request);
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
        $displayType = $this->normalizeDisplayMaterialType((string) $type);

        // Validate type
        if (!$this->isSupportedDisplayMaterialType($displayType)) {
            abort(404);
        }

        // Calculate Grand Total (needed for footer) - can be cached or simplified
        // For consistency, we recalculate it or pass a placeholder if not strictly needed in the partial
        // Ideally, grandTotal should be passed from the main view or recalculated.
        // Recalculating is cheap (count queries).
        $grandTotal = 0;
        $allSettings = $this->getDisplayMaterialSettings();
        foreach ($allSettings as $setting) {
            $grandTotal += $this->countDisplayMaterialRecordsByType(
                $this->normalizeDisplayMaterialType((string) $setting->material_type),
            );
        }

        $data = $this->getDisplayMaterialData($displayType, $request);
        $dbCount = $this->countDisplayMaterialRecordsByType($displayType);

        $material = [
            'type' => $displayType,
            'label' => MaterialSetting::getMaterialLabel($displayType),
            'data' => $data,
            'count' => $data->count(),
            'db_count' => $dbCount,
            'active_letters' => $this->getDisplayActiveLetters($displayType),
            'is_loaded' => true,
        ];

        return view('materials.partials.table', compact('material', 'grandTotal'));
    }

    private function normalizeDisplayMaterialType(string $type): string
    {
        return $type === 'nat' ? 'cement' : $type;
    }

    private function getDisplayMaterialSettings(): Collection
    {
        return MaterialSetting::query()
            ->where('material_type', '!=', 'nat')
            ->get()
            ->sortBy(function ($setting) {
                $type = $this->normalizeDisplayMaterialType((string) $setting->material_type);

                return MaterialSetting::getMaterialLabel($type);
            })
            ->values();
    }

    private function supportedDisplayMaterialTypes(): array
    {
        return $this->getDisplayMaterialSettings()
            ->pluck('material_type')
            ->map(fn($type) => $this->normalizeDisplayMaterialType((string) $type))
            ->unique()
            ->values()
            ->all();
    }

    private function isSupportedDisplayMaterialType(string $type): bool
    {
        return in_array($type, $this->supportedDisplayMaterialTypes(), true);
    }

    private function countDisplayMaterialRecordsByType(string $type): int
    {
        if ($type === 'cement') {
            return Cement::count() + Nat::count();
        }

        return $this->countMaterialRecordsByType($type);
    }

    private function getDisplayActiveLetters(string $type): array
    {
        if ($type !== 'cement') {
            return $this->getActiveLetters($type);
        }

        return collect(array_merge($this->getActiveLetters('cement'), $this->getActiveLetters('nat')))
            ->filter(fn($letter) => filled($letter))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function getDisplayMaterialData(string $type, Request $request): Collection
    {
        if ($type === 'cement') {
            return $this->getMergedCementAndNatData($request);
        }

        $data = $this->getMaterialData($type, $request);

        if ($data instanceof Collection) {
            return $data;
        }

        return collect();
    }

    private function getMergedCementAndNatData(Request $request): Collection
    {
        $cementRows = ($this->getMaterialData('cement', $request) ?? collect())->map(function ($row) {
            $row->row_material_type = 'cement';

            return $row;
        });

        $natRows = ($this->getMaterialData('nat', $request) ?? collect())->map(function ($row) {
            $row->row_material_type = 'nat';

            return $row;
        });

        return $this->sortMergedCementAndNatRows($cementRows->concat($natRows), $request)->values();
    }

    private function sortMergedCementAndNatRows(Collection $rows, Request $request): Collection
    {
        $sortBy = (string) $request->get('sort_by', '');
        $sortDirection = strtolower((string) $request->get('sort_direction', 'asc'));
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        $allowedSortColumns = [
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
        ];
        if ($sortBy !== '' && !in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = '';
        }

        $defaultColumns = [
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
        ];

        $sortColumns = $defaultColumns;
        if ($sortBy !== '') {
            $sortColumns = array_merge([$sortBy], array_values(array_diff($defaultColumns, [$sortBy])));
        }

        return $rows->sort(function ($left, $right) use ($sortColumns, $sortBy, $sortDirection) {
            foreach ($sortColumns as $column) {
                $leftValue = $left->{$column} ?? null;
                $rightValue = $right->{$column} ?? null;

                $comparison = $this->compareNullableValues($leftValue, $rightValue);
                if ($comparison === 0) {
                    continue;
                }

                $direction = ($sortBy !== '' && $column === $sortBy) ? $sortDirection : 'asc';

                return $direction === 'desc' ? -$comparison : $comparison;
            }

            $leftMaterialType = (string) ($left->row_material_type ?? $left->material_kind ?? 'cement');
            $rightMaterialType = (string) ($right->row_material_type ?? $right->material_kind ?? 'cement');
            $materialTypeComparison = strcmp($leftMaterialType, $rightMaterialType);
            if ($materialTypeComparison !== 0) {
                return $materialTypeComparison;
            }

            return ((int) ($left->id ?? 0)) <=> ((int) ($right->id ?? 0));
        })->values();
    }

    private function compareNullableValues($left, $right): int
    {
        $leftIsEmpty = is_null($left) || trim((string) $left) === '';
        $rightIsEmpty = is_null($right) || trim((string) $right) === '';

        if ($leftIsEmpty && $rightIsEmpty) {
            return 0;
        }
        if ($leftIsEmpty) {
            return 1;
        }
        if ($rightIsEmpty) {
            return -1;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left <=> (float) $right;
        }

        return strnatcasecmp((string) $left, (string) $right);
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
            'nat' => 'cement',
            'grout' => 'cement',
            'pasir' => 'sand',
            'sand' => 'sand',
            'keramik' => 'ceramic',
            'ceramic' => 'ceramic',
        ];
        $normalizeSuggestionMaterialType = static fn(string $type): string => $type === 'nat' ? 'cement' : $type;
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
            'nat' => Nat::class,
        ];
        $typeColumns = [
            'brick' => 'type',
            'cat' => 'type',
            'cement' => 'type',
            'sand' => 'type',
            'ceramic' => 'type',
            'nat' => 'type',
        ];
        if ($search === '') {
            $items = collect();
            foreach ($models as $materialType => $model) {
                $typeColumn = $typeColumns[$materialType] ?? 'type';
                $types = $model
                    ::query()
                    ->selectRaw($typeColumn . ' as type')
                    ->whereNotNull($typeColumn)
                    ->where($typeColumn, '!=', '')
                    ->distinct()
                    ->orderBy($typeColumn)
                    ->limit(25)
                    ->pluck('type');

                foreach ($types as $type) {
                    $normalizedMaterialType = $normalizeSuggestionMaterialType($materialType);
                    $items->push([
                        'material_type' => $normalizedMaterialType,
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
            'nat' => 'Semen',
        ];
        $labelColumns = [
            'brick' => ['material_name', 'brand', 'form', 'type'],
            'cat' => ['cat_name', 'brand', 'sub_brand', 'color_name', 'type'],
            'cement' => ['cement_name', 'brand', 'sub_brand', 'code', 'color', 'type'],
            'sand' => ['sand_name', 'brand', 'type'],
            'ceramic' => ['material_name', 'brand', 'sub_brand', 'code', 'color', 'form', 'type'],
            'nat' => ['type', 'nat_name', 'brand', 'sub_brand', 'code', 'color'],
        ];
        $searchColumns = [
            'brick' => ['type', 'material_name', 'brand', 'form'],
            'cat' => ['type', 'cat_name', 'brand', 'sub_brand', 'color_name'],
            'cement' => ['type', 'cement_name', 'brand', 'sub_brand', 'code', 'color'],
            'sand' => ['type', 'sand_name', 'brand'],
            'ceramic' => ['type', 'material_name', 'brand', 'sub_brand', 'code', 'color', 'form'],
            'nat' => ['type', 'nat_name', 'brand', 'sub_brand', 'code', 'color'],
        ];

        $items = collect();
        foreach ($models as $materialType => $model) {
            $normalizedMaterialType = $normalizeSuggestionMaterialType($materialType);
            if (!empty($targetMaterialTypes) && !in_array($normalizedMaterialType, $targetMaterialTypes, true)) {
                continue;
            }
            $materialLabel = $materialLabels[$materialType] ?? ucfirst($materialType);
            $typeColumn = $typeColumns[$materialType] ?? 'type';
            $columns = array_unique(
                array_merge([$typeColumn], $labelColumns[$materialType] ?? [], $searchColumns[$materialType] ?? []),
            );
            $query = $model::query()->select($columns)->whereNotNull($typeColumn)->where($typeColumn, '!=', '');

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
                    'material_type' => $normalizedMaterialType,
                    'type' => $row->{$typeColumn},
                    'label' => $label,
                ]);
            }

            if ($search !== '') {
                $labelMatches = stripos($materialLabel, $search) !== false || stripos($materialType, $search) !== false;
                if (!$labelMatches && !empty($tokens)) {
                    foreach ($tokens as $token) {
                        if (
                            $token !== '' &&
                            (stripos($materialLabel, $token) !== false || stripos($materialType, $token) !== false)
                        ) {
                            $labelMatches = true;
                            break;
                        }
                    }
                }
                if ($labelMatches) {
                    $items->push([
                        'material_type' => $normalizedMaterialType,
                        'type' => $materialLabel,
                        'label' => $materialLabel,
                    ]);
                }

                if (empty($searchTokens)) {
                    $typeMatches = $model
                        ::query()
                        ->selectRaw($typeColumn . ' as type')
                        ->whereNotNull($typeColumn)
                        ->where($typeColumn, '!=', '')
                        ->distinct()
                        ->orderBy($typeColumn)
                        ->limit(10)
                        ->pluck('type');
                } else {
                    $typeQuery = $model
                        ::query()
                        ->selectRaw($typeColumn . ' as type')
                        ->whereNotNull($typeColumn)
                        ->where($typeColumn, '!=', '');
                    foreach ($searchTokens as $token) {
                        $like = '%' . $token . '%';
                        $typeQuery->where($typeColumn, 'like', $like);
                    }
                    $typeMatches = $typeQuery->distinct()->orderBy($typeColumn)->limit(10)->pluck('type');
                }

                foreach ($typeMatches as $type) {
                    $items->push([
                        'material_type' => $normalizedMaterialType,
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
                $parts = [$row->type ?? null, $row->brand ?? null, $row->form ?? null];
                break;
            case 'cat':
                $parts = [$row->brand ?? null, $row->sub_brand ?? null, $row->color_name ?? null, $row->type ?? null];
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
                $parts = [$row->type ?? null, $row->brand ?? null];
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
            case 'nat':
                $parts = [
                    $row->type ?? null,
                    $row->nat_name ?? null,
                    $row->brand ?? null,
                    $row->sub_brand ?? null,
                    $row->code ?? null,
                    $row->color ?? null,
                ];
                break;
            default:
                $parts = [$row->type ?? null];
                break;
        }

        $parts = array_values(
            array_filter($parts, function ($value) {
                return !is_null($value) && trim((string) $value) !== '';
            }),
        );

        $parts = array_slice($parts, 0, 3);

        return trim(implode(' - ', $parts));
    }

    private function getActiveLetters($type)
    {
        $model = $this->materialModelClass((string) $type);

        if (!$model) {
            return [];
        }

        // Changed to 'brand' based on user request to paginate by Brand instead of Type
        $letterColumn = 'brand';

        // Get distinct first letters, uppercase
        return $model
            ::selectRaw(sprintf('DISTINCT UPPER(SUBSTRING(%s, 1, 1)) as letter', $letterColumn))
            ->whereNotNull($letterColumn)
            ->where($letterColumn, '!=', '')
            ->orderBy('letter')
            ->pluck('letter')
            ->toArray();
    }

    private function supportedMaterialTypes(): array
    {
        return ['brick', 'cat', 'ceramic', 'sand', 'cement', 'nat'];
    }

    private function isSupportedMaterialType(string $type): bool
    {
        return in_array($type, $this->supportedMaterialTypes(), true);
    }

    private function materialModelClass(string $type): ?string
    {
        return match ($type) {
            'brick' => Brick::class,
            'cat' => Cat::class,
            'ceramic' => Ceramic::class,
            'sand' => Sand::class,
            'cement' => Cement::class,
            'nat' => Nat::class,
            default => null,
        };
    }

    private function countMaterialRecordsByType(string $type): int
    {
        $model = $this->materialModelClass($type);
        if (!$model) {
            return 0;
        }

        return $model::count();
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
            case 'nat':
                $query = Nat::query()->with('packageUnit');
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
                    'package_type',
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
                'nat' => [
                    'type',
                    'nat_name',
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
                'package_type',
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
            'nat' => [
                'type',
                'nat_name',
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
            default => [],
        };

        if ($sortBy && !in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = null;
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
                'package_type',
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
            'nat' => [
                'type',
                'nat_name',
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
            default => ['type', 'created_at', 'id'],
        };

        if ($sortBy) {
            $primarySortColumns = match (true) {
                $type === 'ceramic' && $sortBy === 'dimension_length' => [
                    'dimension_length',
                    'dimension_width',
                    'dimension_thickness',
                ],
                in_array($type, ['brick', 'sand'], true) && $sortBy === 'dimension_length' => [
                    'dimension_length',
                    'dimension_width',
                    'dimension_height',
                ],
                default => [$sortBy],
            };

            foreach ($primarySortColumns as $column) {
                $query->orderBy($column, $sortDirection);
            }

            foreach ($defaultOrderBy as $column) {
                if (in_array($column, $primarySortColumns, true)) {
                    continue;
                }
                $query->orderBy($column, 'asc');
            }

            return $query->limit(1000)->get();
        }

        foreach ($defaultOrderBy as $column) {
            $query->orderBy($column, 'asc');
        }

        // Limit to 1000 rows to prevent memory exhaustion/slow rendering
        return $query->limit(1000)->get();
    }
}
