# CLEAN ARCHITECTURE REFACTORING PLAN
## Material Database - Service Layer & Repository Pattern

**Version:** 2.0 (Updated)
**Date:** December 30, 2025
**Focus:** REST API + Clean Architecture + NO AUTH (untuk sekarang)

---

## 🎯 STRATEGI BERDASARKAN KEBUTUHAN ANDA

### Situasi Anda:
1. ✅ **Internal use** (tidak perlu auth sekarang)
2. ✅ **Multi-user nanti** (perlu persiapan untuk permissions)
3. ✅ **Tidak terburu-buru** (ada waktu untuk develop dengan baik)
4. ✅ **Code reusable** (tidak mau logic bertumpuk di controller)

### Solusi:
1. **Skip authentication dulu** → Hemat 1+ minggu
2. **Implement Service Layer Pattern** → Code reusable & testable
3. **Implement Repository Pattern** → Data access terpisah
4. **Prepare untuk multi-user** → Easy add auth later

---

## 📚 APA ITU SERVICE LAYER PATTERN?

### Masalah: Fat Controllers (Saat Ini)

**BAD PRACTICE:** ❌ Semua logic di Controller
```php
class BrickController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validation (20 lines)
        $request->validate([...]);

        // 2. Business Logic (30 lines)
        $data = $request->all();
        if (empty($data['brick_name'])) {
            $parts = array_filter([...]);
            $data['brick_name'] = implode(' ', $parts);
        }

        // 3. Database Operation (10 lines)
        $brick = Brick::create($data);

        // 4. File Upload (20 lines)
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time() . '_' . $photo->getClientOriginalName();
            $path = $photo->storeAs('bricks', $filename, 'public');
            $brick->photo = $path;
        }

        // 5. Calculations (15 lines)
        if (!$brick->package_volume) {
            $brick->calculateVolume();
        }
        if (!$brick->comparison_price_per_m3) {
            $brick->calculateComparisonPrice();
        }
        $brick->save();

        // 6. Response (5 lines)
        if ($request->input('_redirect_to_materials')) {
            return redirect()->route('materials.index');
        }
        return redirect()->route('bricks.index');
    }
}
```

**Problems:**
- 100+ lines di satu method
- Tidak reusable (logic locked di controller)
- Sulit testing (harus mock Request, Storage, dll)
- Sulit maintain (semua tercampur)
- Tidak bisa dipanggil dari Command, Job, atau API

---

### Solusi: Service Layer Pattern

**GOOD PRACTICE:** ✅ Separation of Concerns

```php
// 1. CONTROLLER - Hanya koordinasi
class BrickController extends Controller
{
    public function __construct(
        private BrickService $brickService
    ) {}

    public function store(StoreBrickRequest $request)
    {
        $brick = $this->brickService->create(
            $request->validated(),
            $request->file('photo')
        );

        return $this->createdResponse(
            new BrickResource($brick),
            'Brick created successfully'
        );
    }
}

// 2. SERVICE - Business Logic
class BrickService
{
    public function __construct(
        private BrickRepository $brickRepository,
        private FileUploadService $fileUploadService
    ) {}

    public function create(array $data, ?UploadedFile $photo = null): Brick
    {
        // Auto-generate name if empty
        if (empty($data['brick_name'])) {
            $data['brick_name'] = $this->generateBrickName($data);
        }

        // Handle photo upload
        if ($photo) {
            $data['photo'] = $this->fileUploadService->upload($photo, 'bricks');
        }

        // Create brick
        $brick = $this->brickRepository->create($data);

        // Auto-calculations
        $this->calculateDerivedFields($brick);

        return $brick;
    }

    private function generateBrickName(array $data): string
    {
        $parts = array_filter([
            $data['type'] ?? '',
            $data['brand'] ?? '',
            $data['form'] ?? '',
        ]);
        return implode(' ', $parts) ?: 'Brick';
    }

    private function calculateDerivedFields(Brick $brick): void
    {
        if (!$brick->package_volume) {
            $brick->calculateVolume();
        }
        if (!$brick->comparison_price_per_m3) {
            $brick->calculateComparisonPrice();
        }
        $brick->save();
    }
}

// 3. REPOSITORY - Data Access
class BrickRepository
{
    public function create(array $data): Brick
    {
        return Brick::create($data);
    }

    public function findById(int $id): ?Brick
    {
        return Brick::find($id);
    }

    public function paginate(int $perPage = 15)
    {
        return Brick::orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function search(string $query, int $perPage = 15)
    {
        return Brick::where('brick_name', 'like', "%{$query}%")
            ->orWhere('brand', 'like', "%{$query}%")
            ->paginate($perPage);
    }
}

// 4. FILE UPLOAD SERVICE - Reusable untuk semua materials
class FileUploadService
{
    public function upload(UploadedFile $file, string $folder): string
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        return $file->storeAs($folder, $filename, 'public');
    }

    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}
```

**Benefits:**
- ✅ Controller cuma 5 lines (clean!)
- ✅ Service reusable (bisa dipanggil dari mana saja)
- ✅ Easy testing (mock repository, test service independently)
- ✅ Single Responsibility (setiap class punya 1 tugas)
- ✅ DRY (Don't Repeat Yourself)

---

## 🏗️ STRUKTUR FOLDER BARU

### Before (Sekarang):
```
app/
├── Http/
│   └── Controllers/
│       ├── BrickController.php (200+ lines)
│       ├── CementController.php (200+ lines)
│       └── ...
├── Models/
│   ├── Brick.php
│   └── ...
└── Services/
    └── Formula/
        └── BrickHalfFormula.php
```

### After (Clean Architecture):
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php (nanti)
│   │   │   ├── BrickController.php (30 lines!)
│   │   │   ├── CementController.php (30 lines!)
│   │   │   └── MaterialCalculationController.php (50 lines!)
│   │   └── ...
│   ├── Requests/
│   │   ├── Brick/
│   │   │   ├── StoreBrickRequest.php
│   │   │   └── UpdateBrickRequest.php
│   │   └── ...
│   └── Resources/
│       ├── BrickResource.php
│       └── ...
│
├── Services/
│   ├── Material/
│   │   ├── BrickService.php
│   │   ├── CementService.php
│   │   ├── SandService.php
│   │   └── CatService.php
│   ├── Calculation/
│   │   ├── MaterialCalculationService.php
│   │   ├── CombinationGeneratorService.php
│   │   └── MaterialSelectorService.php
│   ├── Formula/
│   │   ├── FormulaInterface.php
│   │   ├── FormulaRegistry.php
│   │   ├── BrickHalfFormula.php
│   │   └── ...
│   └── Common/
│       ├── FileUploadService.php
│       └── AutocompleteService.php
│
├── Repositories/
│   ├── BrickRepository.php
│   ├── CementRepository.php
│   ├── SandRepository.php
│   ├── CatRepository.php
│   ├── MaterialCalculationRepository.php
│   └── Contracts/
│       ├── BrickRepositoryInterface.php
│       └── ...
│
├── Models/
│   ├── Brick.php (keep as is - hanya Eloquent stuff)
│   ├── Cement.php
│   └── ...
│
├── Traits/
│   └── ApiResponse.php
│
└── Providers/
    └── RepositoryServiceProvider.php (untuk binding interfaces)
```

---

## 🔄 REFACTORING ROADMAP DETAIL

### PHASE 2: ARCHITECTURE REFACTORING (5 hari)

#### Day 1: Setup Architecture Foundation
**Tasks:**
1. **Create Base Repository** (2 jam)
   ```php
   // app/Repositories/BaseRepository.php
   abstract class BaseRepository
   {
       protected $model;

       public function all()
       {
           return $this->model->all();
       }

       public function find($id)
       {
           return $this->model->find($id);
       }

       public function create(array $data)
       {
           return $this->model->create($data);
       }

       public function update($id, array $data)
       {
           $record = $this->find($id);
           $record->update($data);
           return $record;
       }

       public function delete($id)
       {
           return $this->model->destroy($id);
       }

       public function paginate($perPage = 15)
       {
           return $this->model->paginate($perPage);
       }
   }
   ```

2. **Create Base Service** (2 jam)
   ```php
   // app/Services/BaseService.php
   abstract class BaseService
   {
       protected $repository;

       public function all()
       {
           return $this->repository->all();
       }

       public function find($id)
       {
           return $this->repository->find($id);
       }

       public function create(array $data)
       {
           return $this->repository->create($data);
       }

       public function update($id, array $data)
       {
           return $this->repository->update($id, $data);
       }

       public function delete($id)
       {
           return $this->repository->delete($id);
       }
   }
   ```

3. **Create Repository Service Provider** (2 jam)
   ```php
   // app/Providers/RepositoryServiceProvider.php
   class RepositoryServiceProvider extends ServiceProvider
   {
       public function register()
       {
           // Bind interfaces to implementations
           $this->app->bind(
               BrickRepositoryInterface::class,
               BrickRepository::class
           );
           // ... repeat for other repositories
       }
   }
   ```

4. **Create Common Services** (2 jam)
   ```php
   // app/Services/Common/FileUploadService.php
   class FileUploadService
   {
       public function upload(UploadedFile $file, string $folder): string;
       public function delete(string $path): bool;
       public function update(UploadedFile $file, string $folder, ?string $oldPath): string;
   }

   // app/Services/Common/AutocompleteService.php
   class AutocompleteService
   {
       public function getFieldValues(string $model, string $field, array $filters = []): Collection;
       public function getAllStores(): Collection;
       public function getAddressesByStore(string $store): Collection;
   }
   ```

---

#### Day 2: Refactor Brick (Template untuk materials lainnya)

**1. Create BrickRepository** (1 jam)
```php
// app/Repositories/BrickRepository.php
class BrickRepository extends BaseRepository
{
    public function __construct(Brick $model)
    {
        $this->model = $model;
    }

    public function search(string $query, int $perPage = 15)
    {
        return $this->model
            ->where('brick_name', 'like', "%{$query}%")
            ->orWhere('brand', 'like', "%{$query}%")
            ->orWhere('type', 'like', "%{$query}%")
            ->paginate($perPage);
    }

    public function getFieldValues(string $field, array $filters = [])
    {
        $query = $this->model->query()
            ->whereNotNull($field)
            ->where($field, '!=', '');

        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }

        return $query->select($field)
            ->groupBy($field)
            ->orderBy($field)
            ->limit(20)
            ->pluck($field);
    }
}
```

**2. Create BrickService** (3 jam)
```php
// app/Services/Material/BrickService.php
class BrickService extends BaseService
{
    public function __construct(
        private BrickRepository $repository,
        private FileUploadService $fileUploadService
    ) {
        $this->repository = $repository;
    }

    public function create(array $data, ?UploadedFile $photo = null): Brick
    {
        // Auto-generate name
        if (empty($data['brick_name'])) {
            $data['brick_name'] = $this->generateName($data);
        }

        // Upload photo
        if ($photo) {
            $data['photo'] = $this->fileUploadService->upload($photo, 'bricks');
        }

        // Create
        $brick = $this->repository->create($data);

        // Auto-calculate
        $this->calculateDerivedFields($brick);

        return $brick;
    }

    public function update(int $id, array $data, ?UploadedFile $photo = null): Brick
    {
        $brick = $this->repository->find($id);

        // Auto-generate name
        if (empty($data['brick_name'])) {
            $data['brick_name'] = $this->generateName($data);
        }

        // Update photo
        if ($photo) {
            // Delete old photo
            if ($brick->photo) {
                $this->fileUploadService->delete($brick->photo);
            }
            $data['photo'] = $this->fileUploadService->upload($photo, 'bricks');
        }

        // Update
        $brick->update($data);

        // Recalculate
        $this->calculateDerivedFields($brick);

        return $brick;
    }

    public function delete(int $id): bool
    {
        $brick = $this->repository->find($id);

        // Delete photo
        if ($brick->photo) {
            $this->fileUploadService->delete($brick->photo);
        }

        return $this->repository->delete($id);
    }

    private function generateName(array $data): string
    {
        $parts = array_filter([
            $data['type'] ?? '',
            $data['brand'] ?? '',
            $data['form'] ?? '',
        ]);
        return implode(' ', $parts) ?: 'Brick';
    }

    private function calculateDerivedFields(Brick $brick): void
    {
        if (!$brick->package_volume) {
            $brick->calculateVolume();
        }
        if (!$brick->comparison_price_per_m3) {
            $brick->calculateComparisonPrice();
        }
        $brick->save();
    }

    // Autocomplete methods
    public function getFieldValues(string $field, array $filters = [])
    {
        return $this->repository->getFieldValues($field, $filters);
    }
}
```

**3. Refactor BrickController** (2 jam)
```php
// app/Http/Controllers/Api/BrickController.php
class BrickController extends Controller
{
    use ApiResponse;

    public function __construct(
        private BrickService $brickService
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search');

        $bricks = $search
            ? $this->brickService->search($search)
            : $this->brickService->paginate();

        return BrickResource::collection($bricks);
    }

    public function store(StoreBrickRequest $request)
    {
        $brick = $this->brickService->create(
            $request->validated(),
            $request->file('photo')
        );

        return $this->createdResponse(
            new BrickResource($brick),
            'Brick created successfully'
        );
    }

    public function show(int $id)
    {
        $brick = $this->brickService->find($id);

        if (!$brick) {
            return $this->notFoundResponse('Brick not found');
        }

        return new BrickResource($brick);
    }

    public function update(UpdateBrickRequest $request, int $id)
    {
        $brick = $this->brickService->update(
            $id,
            $request->validated(),
            $request->file('photo')
        );

        return $this->successResponse(
            new BrickResource($brick),
            'Brick updated successfully'
        );
    }

    public function destroy(int $id)
    {
        $this->brickService->delete($id);

        return $this->noContentResponse();
    }

    // Autocomplete
    public function getFieldValues(string $field, Request $request)
    {
        $values = $this->brickService->getFieldValues(
            $field,
            $request->only(['brand', 'store', 'package_unit'])
        );

        return response()->json($values);
    }
}
```

**Result: Controller cuma 50 lines!** (dari 200+ lines)

---

#### Day 3-4: Repeat untuk Cement, Sand, Cat (6 jam each)
- Copy pattern dari Brick
- Adjust untuk material specifics
- Total: 3 materials × 6 jam = 18 jam = 2+ hari

---

#### Day 5: Refactor MaterialCalculation (Complex)

**1. Break down MaterialCalculationController** (4 jam)

Create 3 Services:
```php
// app/Services/Calculation/MaterialCalculationService.php
class MaterialCalculationService
{
    public function __construct(
        private MaterialCalculationRepository $repository,
        private CombinationGeneratorService $combinationGenerator,
        private MaterialSelectorService $materialSelector
    ) {}

    public function calculate(array $params): BrickCalculation
    {
        // Delegate ke BrickCalculation::performCalculation
        return BrickCalculation::performCalculation($params);
    }

    public function store(array $params): BrickCalculation
    {
        $calculation = $this->calculate($params);
        $calculation->save();
        return $calculation;
    }

    public function generateCombinations(array $params): array
    {
        return $this->combinationGenerator->generate($params);
    }
}

// app/Services/Calculation/CombinationGeneratorService.php
class CombinationGeneratorService
{
    public function generate(array $params): array
    {
        // Extract generateCombinations logic dari controller
        // Reusable!
    }
}

// app/Services/Calculation/MaterialSelectorService.php
class MaterialSelectorService
{
    public function selectByPrice(string $filter, string $materialType): Collection
    {
        return match($filter) {
            'best' => $this->getBest($materialType),
            'cheapest' => $this->getCheapest($materialType),
            'medium' => $this->getMedium($materialType),
            'expensive' => $this->getExpensive($materialType),
            'common' => $this->getCommon($materialType),
            default => collect(),
        };
    }

    private function getBest(string $materialType): Collection
    {
        // Logic dari getBestCombinations()
    }

    // ... other methods
}
```

**2. Refactor Controller** (2 jam)
```php
class MaterialCalculationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private MaterialCalculationService $calculationService
    ) {}

    public function calculate(CalculateRequest $request)
    {
        $calculation = $this->calculationService->calculate(
            $request->validated()
        );

        return $this->successResponse([
            'data' => new MaterialCalculationResource($calculation),
            'summary' => $calculation->getSummary(),
        ]);
    }

    public function store(StoreCalculationRequest $request)
    {
        $calculation = $this->calculationService->store(
            $request->validated()
        );

        return $this->createdResponse(
            new MaterialCalculationResource($calculation),
            'Calculation saved successfully'
        );
    }

    // ... other methods (50 lines total vs 1200+ lines!)
}
```

---

## 📊 COMPARISON: BEFORE vs AFTER

### Before (Fat Controllers):
```
BrickController.php: 467 lines
├── CRUD logic: 200 lines
├── Business logic: 150 lines
├── Validation: 50 lines
├── File upload: 40 lines
└── Helpers: 27 lines

CementController.php: 450 lines (similar structure)
SandController.php: 440 lines
CatController.php: 460 lines
MaterialCalculationController.php: 1282 lines

Total Controller Code: ~3100 lines
Reusability: 0% (locked in controllers)
Testability: Hard (need to mock HTTP requests)
```

### After (Clean Architecture):
```
Controllers/ (Thin controllers)
├── BrickController.php: 50 lines ✅
├── CementController.php: 50 lines ✅
├── SandController.php: 50 lines ✅
├── CatController.php: 50 lines ✅
└── MaterialCalculationController.php: 80 lines ✅

Services/ (Business logic)
├── Material/
│   ├── BrickService.php: 150 lines
│   ├── CementService.php: 150 lines
│   ├── SandService.php: 150 lines
│   └── CatService.php: 150 lines
├── Calculation/
│   ├── MaterialCalculationService.php: 200 lines
│   ├── CombinationGeneratorService.php: 150 lines
│   └── MaterialSelectorService.php: 200 lines
└── Common/
    ├── FileUploadService.php: 50 lines
    └── AutocompleteService.php: 100 lines

Repositories/ (Data access)
├── BrickRepository.php: 80 lines
├── CementRepository.php: 80 lines
├── SandRepository.php: 80 lines
├── CatRepository.php: 80 lines
└── MaterialCalculationRepository.php: 100 lines

Total Code: ~2200 lines
Reusability: 100% ✅
Testability: Easy ✅
Maintainability: High ✅
```

**Code Reduction: ~30%**
**Reusability: 0% → 100%**
**Testability: Hard → Easy**

---

## 🧪 TESTING BENEFITS

### Before (Fat Controllers):
```php
// Hard to test - need to mock everything
public function test_can_create_brick()
{
    Storage::fake('public');

    $response = $this->post('/bricks', [
        'brand' => 'Test',
        'photo' => UploadedFile::fake()->image('brick.jpg'),
        // ... many fields
    ]);

    // Can only test via HTTP
    // Can't test business logic separately
}
```

### After (Service Layer):
```php
// Easy to test - unit test service directly
public function test_brick_service_creates_brick_with_auto_name()
{
    $service = new BrickService(
        new BrickRepository(new Brick),
        new FileUploadService
    );

    $brick = $service->create([
        'type' => 'Merah',
        'brand' => 'Delanggu',
        'form' => 'Press',
    ]);

    $this->assertEquals('Merah Delanggu Press', $brick->brick_name);
}

public function test_brick_service_auto_calculates_volume()
{
    $brick = $service->create([
        'dimension_length' => 23,
        'dimension_width' => 11,
        'dimension_height' => 5,
    ]);

    $this->assertNotNull($brick->package_volume);
}
```

**Unit Tests: 100+ tests**
**Coverage: 80%+**

---

## 🚀 UPDATED COMPLETE ROADMAP

### Week 1: Infrastructure (5 hari)
- **Day 1-2:** Setup (CORS, Traits, Exception Handler) - NO AUTH
- **Day 3-5:** Architecture Foundation (Base Repository, Base Service, Providers)

### Week 2: Material Refactoring (5 hari)
- **Day 1:** FileUploadService, AutocompleteService
- **Day 2:** Brick (Repository + Service + Controller)
- **Day 3:** Cement (Repository + Service + Controller)
- **Day 4:** Sand (Repository + Service + Controller)
- **Day 5:** Cat (Repository + Service + Controller)

### Week 3: Calculation Refactoring (5 hari)
- **Day 1-2:** MaterialCalculationService breakdown
- **Day 3:** CombinationGeneratorService
- **Day 4:** MaterialSelectorService
- **Day 5:** MaterialCalculationController refactor

### Week 4: Supporting Features (5 hari)
- **Day 1-2:** WorkItem (Repository + Service + Controller)
- **Day 3:** RecommendedCombination (Repository + Service + Controller)
- **Day 4:** Unit (Repository + Service + Controller)
- **Day 5:** API Resources & Form Requests

### Week 5: Testing (5 hari)
- **Day 1-2:** Unit tests untuk Services
- **Day 3:** Integration tests
- **Day 4:** Feature tests untuk API endpoints
- **Day 5:** Performance testing & optimization

### Week 6: Documentation & Deployment (5 hari)
- **Day 1-2:** API documentation (Swagger)
- **Day 3:** Code documentation (PHPDoc)
- **Day 4:** Deployment ke staging
- **Day 5:** Production deployment

**Total: 6 minggu (tanpa auth)**

### LATER (When ready for multi-user):
### Week 7-8: Authentication & Authorization (bila diperlukan)
- **Day 1:** Install Sanctum
- **Day 2-3:** AuthController + User management
- **Day 4-5:** Policies & Permissions (role-based access control)
- **Day 6:** Testing authentication
- **Day 7:** Frontend integration dengan tokens

---

## ✅ DELIVERABLES

### Phase 1-6 (No Auth):
1. ✅ Clean Architecture implemented
2. ✅ Service Layer Pattern
3. ✅ Repository Pattern
4. ✅ Reusable business logic
5. ✅ REST API complete
6. ✅ Unit tests (80%+ coverage)
7. ✅ API documentation
8. ✅ Production-ready (internal use)

### Phase 7-8 (Later - Multi-user):
1. ✅ Laravel Sanctum authentication
2. ✅ Role-based permissions
3. ✅ User management
4. ✅ Audit trail
5. ✅ Production-ready (multi-tenant)

---

## 🎯 NEXT STEPS

Mau saya mulai implement sekarang?

### Option A: **Start Phase 1 (Infrastructure Setup)**
- Setup CORS
- Create ApiResponse Trait
- Update Exception Handler
- Test basic API

### Option B: **Start Phase 2 (Architecture Foundation)**
- Create BaseRepository
- Create BaseService
- Create RepositoryServiceProvider
- Create Common Services

### Option C: **Full Demo (Brick Module)**
- Complete refactoring 1 module (Brick)
- Dari Fat Controller → Clean Architecture
- Sebagai template untuk modules lainnya

**Pilih mana? Atau ada pertanyaan dulu?**

---

**END OF DOCUMENT**
