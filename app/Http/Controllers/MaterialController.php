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
        $allSettings = MaterialSetting::where('material_type', '!=', 'nat')->get()
            ->sortBy(function($setting) {
                return MaterialSetting::getMaterialLabel($setting->material_type);
            })
            ->values();

        $materials = [];
        $grandTotal = 0;

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

            $data = $this->getMaterialData($type, $request);

            if ($data) {
                $materials[] = [
                    'type' => $type,
                    'label' => MaterialSetting::getMaterialLabel($type),
                    'data' => $data,
                    'count' => $data->count(), // Filtered count
                    'db_count' => $dbCount, // Absolute total for this type
                    'active_letters' => $activeLetters,
                ];
            }
        }

        return view('materials.index', compact('materials', 'allSettings', 'grandTotal'));
    }

    public function typeSuggestions(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
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
                $types = $model::query()
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
            $materialLabel = $materialLabels[$materialType] ?? ucfirst($materialType);
            $columns = array_unique(array_merge(['type'], $labelColumns[$materialType] ?? [], $searchColumns[$materialType] ?? []));
            $query = $model::query()->select($columns)->whereNotNull('type')->where('type', '!=', '');

            $like = '%' . $search . '%';
            $columns = $searchColumns[$materialType] ?? [];
            $query->where(function ($builder) use ($like, $columns) {
                $builder->where('type', 'like', $like);
                foreach ($columns as $column) {
                    $builder->orWhere($column, 'like', $like);
                }
            });

            $results = $query->orderBy('type')->limit(20)->get();
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
                if (stripos($materialLabel, $search) !== false || stripos($materialType, $search) !== false) {
                    $items->push([
                        'material_type' => $materialType,
                        'type' => $materialLabel,
                        'label' => $materialLabel,
                    ]);
                }

                $typeMatches = $model::query()
                    ->select('type')
                    ->whereNotNull('type')
                    ->where('type', '!=', '')
                    ->where('type', 'like', $like)
                    ->distinct()
                    ->orderBy('type')
                    ->limit(10)
                    ->pluck('type');

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
        $label = '';
        switch ($materialType) {
            case 'brick':
                $label = $row->material_name ?? $row->brand ?? $row->form ?? $row->type ?? '';
                break;
            case 'cat':
                $label = $row->cat_name ?? $row->brand ?? $row->sub_brand ?? $row->color_name ?? $row->type ?? '';
                break;
            case 'cement':
                $label = $row->cement_name ?? $row->brand ?? $row->sub_brand ?? $row->code ?? $row->color ?? $row->type ?? '';
                break;
            case 'sand':
                $label = $row->sand_name ?? $row->brand ?? $row->type ?? '';
                break;
            case 'ceramic':
                $label = $row->material_name ?? $row->brand ?? $row->sub_brand ?? $row->code ?? $row->color ?? $row->form ?? $row->type ?? '';
                break;
            default:
                $label = $row->type ?? '';
                break;
        }

        return trim((string) $label);
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

        $letterColumn = $type === 'ceramic' ? 'type' : 'brand';

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

        // Apply Search (Overrides Letter filter usually, or combines? Standard is Search OR Letter.
        // If Search is present, ignore letter. If no search, use Letter.)
        if ($search) {
            $query->where(function ($q) use ($search, $type) {
                $q->where('brand', 'like', "%{$search}%")->orWhere('store', 'like', "%{$search}%");

                // Add specific fields based on type
                if ($type == 'brick') {
                    $q->orWhere('type', 'like', "%{$search}%");
                }
                if ($type == 'cat') {
                    $q->orWhere('cat_name', 'like', "%{$search}%");
                }
                if ($type == 'cement') {
                    $q->orWhere('cement_name', 'like', "%{$search}%");
                }
                if ($type == 'sand') {
                    $q->orWhere('sand_name', 'like', "%{$search}%");
                }
                if ($type == 'ceramic') {
                    $q->orWhere('material_name', 'like', "%{$search}%");
                }
            });
        }

        if ($type === 'ceramic') {
            return $query->orderBy('type')->orderBy('brand')->get();
        }

        return $query->orderBy('created_at', 'desc')->orderBy('brand')->get();
    }
}
