# REST API MIGRATION PLAN
## Material Database - Laravel Application

**Version:** 1.0
**Date:** December 30, 2025
**Estimasi Total:** 7-8 minggu (1 developer)

---

## EXECUTIVE SUMMARY

Proyek ini akan melakukan migrasi dari **web-based Laravel application** menjadi **RESTful API backend** yang dapat digunakan oleh multiple frontend (web, mobile, dll). Migrasi ini bersifat **non-destructive** terhadap business logic yang sudah ada.

**Strategi:** Dual mode (Web + API) untuk transisi bertahap, kemudian full migration ke API.

---

## DAFTAR LENGKAP FILE YANG PERLU DIBUAT

### 1. AUTHENTICATION (3 files)

```
app/Http/Controllers/Api/
└── AuthController.php

app/Http/Requests/Auth/
├── LoginRequest.php
└── RegisterRequest.php
```

**AuthController Methods:**
- `register(RegisterRequest $request)` - User registration, return token
- `login(LoginRequest $request)` - User login, return token
- `logout(Request $request)` - Revoke current token
- `me(Request $request)` - Get authenticated user info

---

### 2. API RESOURCES (10 files)

```
app/Http/Resources/
├── BrickResource.php
├── BrickCollection.php
├── CementResource.php
├── SandResource.php
├── CatResource.php
├── MaterialCalculationResource.php
├── MaterialCalculationCollection.php
├── WorkItemResource.php
├── RecommendedCombinationResource.php
└── UnitResource.php
```

**Purpose:** Transform model data ke format API response yang konsisten

**Template Structure:**
```php
class BrickResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'material_name' => $this->material_name,
            'brand' => $this->brand,
            'photo_url' => $this->photo_url, // accessor
            'price_per_piece' => $this->price_per_piece,
            'created_at' => $this->created_at->toISOString(),
            // ... other fields
        ];
    }
}
```

---

### 3. FORM REQUESTS (14 files)

```
app/Http/Requests/
├── Brick/
│   ├── StoreBrickRequest.php
│   └── UpdateBrickRequest.php
├── Cement/
│   ├── StoreCementRequest.php
│   └── UpdateCementRequest.php
├── Sand/
│   ├── StoreSandRequest.php
│   └── UpdateSandRequest.php
├── Cat/
│   ├── StoreCatRequest.php
│   └── UpdateCatRequest.php
├── MaterialCalculation/
│   ├── StoreCalculationRequest.php
│   ├── UpdateCalculationRequest.php
│   └── CalculateRequest.php
└── WorkItem/
    ├── StoreWorkItemRequest.php
    └── UpdateWorkItemRequest.php
```

**Benefits:**
- Validation logic terpisah dari controller
- Authorization logic di method `authorize()`
- Reusable dan testable

---

### 4. TRAITS (1 file)

```
app/Traits/
└── ApiResponse.php
```

**Methods:**
```php
trait ApiResponse {
    // Success responses
    protected function successResponse($data, $message = null, $code = 200);
    protected function createdResponse($data, $message = 'Created');
    protected function noContentResponse();

    // Error responses
    protected function errorResponse($message, $code = 400, $errors = null);
    protected function unauthorizedResponse($message = 'Unauthorized');
    protected function forbiddenResponse($message = 'Forbidden');
    protected function notFoundResponse($message = 'Not found');
    protected function validationErrorResponse($errors);

    // Paginated response
    protected function paginatedResponse($paginator, $resourceClass);
}
```

---

### 5. MIDDLEWARE (2 files)

```
app/Http/Middleware/
├── ForceJsonResponse.php
└── EnsureApiToken.php (optional)
```

**ForceJsonResponse:**
- Force `Accept: application/json` header untuk API routes
- Ensure semua response dalam format JSON

---

### 6. POLICIES (6 files)

```
app/Policies/
├── BrickPolicy.php
├── CementPolicy.php
├── SandPolicy.php
├── CatPolicy.php
├── MaterialCalculationPolicy.php
└── WorkItemPolicy.php
```

**Methods per Policy:**
```php
- viewAny(User $user)
- view(User $user, Model $model)
- create(User $user)
- update(User $user, Model $model)
- delete(User $user, Model $model)
```

---

### 7. TESTS (10+ files)

```
tests/Feature/Api/
├── Auth/
│   ├── LoginTest.php
│   ├── LogoutTest.php
│   └── RegisterTest.php
├── BrickTest.php
├── CementTest.php
├── SandTest.php
├── CatTest.php
├── MaterialCalculationTest.php
├── WorkItemTest.php
└── RecommendedCombinationTest.php
```

**Test Coverage:**
- CRUD operations (index, show, store, update, destroy)
- Validation errors (422)
- Authentication (401 Unauthorized)
- Authorization (403 Forbidden)
- Pagination
- File uploads

---

### 8. ROUTES (1 file)

```
routes/
└── api.php (update existing atau create new)
```

**Structure:**
```php
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Materials (RESTful resources)
        Route::apiResource('bricks', BrickController::class);
        Route::apiResource('cements', CementController::class);
        Route::apiResource('sands', SandController::class);
        Route::apiResource('cats', CatController::class);

        // Material helpers (autocomplete)
        Route::get('/{material}/field-values/{field}', 'getFieldValues');
        Route::get('/{material}/all-stores', 'getAllStores');
        Route::get('/{material}/addresses-by-store', 'getAddressesByStore');

        // Calculations
        Route::apiResource('calculations', MaterialCalculationController::class);
        Route::post('/calculations/calculate', 'calculate');
        Route::post('/calculations/compare', 'compare');
        Route::post('/calculations/trace', 'traceCalculation');

        // Work Items
        Route::apiResource('work-items', WorkItemController::class);
        Route::get('/work-items/{code}/analytics', 'analytics');

        // Recommendations
        Route::get('/recommendations', [RecommendedCombinationController::class, 'index']);
        Route::post('/recommendations', 'store');

        // Units
        Route::apiResource('units', UnitController::class);
    });
});
```

---

## DAFTAR LENGKAP FILE YANG PERLU DIUBAH

### 1. CONTROLLERS (9 files)

#### Material Controllers (4 files)
```
app/Http/Controllers/
├── BrickController.php
├── CementController.php
├── SandController.php
└── CatController.php
```

**Perubahan:**
1. **REMOVE Methods:**
   - `create()` - Form rendering, frontend yang handle
   - `edit()` - Form rendering, frontend yang handle

2. **UPDATE Methods (return JSON):**
   - `index()` - Return `BrickResource::collection()` dengan pagination
   - `store()` - Return `new BrickResource($brick)` dengan status 201
   - `show()` - Return `new BrickResource($brick)`
   - `update()` - Return `new BrickResource($brick)`
   - `destroy()` - Return `response()->noContent()` (204)

3. **KEEP Methods (already JSON):**
   - `getFieldValues($field)` ✓
   - `getAllStores()` ✓
   - `getAddressesByStore()` ✓

4. **ADD:**
   - `use App\Http\Resources\BrickResource;`
   - `use App\Http\Requests\Brick\{StoreBrickRequest, UpdateBrickRequest};`
   - `use App\Traits\ApiResponse;`
   - Trait usage: `use ApiResponse;`

**Example Transformation:**

**BEFORE:**
```php
public function store(Request $request) {
    $request->validate([...]);
    $brick = Brick::create($request->all());
    return redirect()->route('bricks.index')
        ->with('success', 'Brick created!');
}
```

**AFTER:**
```php
public function store(StoreBrickRequest $request) {
    $brick = Brick::create($request->validated());

    if ($request->hasFile('photo')) {
        // handle photo upload
        $brick->photo = $request->file('photo')->store('bricks', 'public');
        $brick->save();
    }

    return $this->createdResponse(
        new BrickResource($brick),
        'Brick created successfully'
    );
}
```

---

#### MaterialCalculationController (1 file - COMPLEX)
```
app/Http/Controllers/MaterialCalculationController.php
```

**Perubahan:**
1. **REMOVE Methods:**
   - `create()` - Frontend forms
   - `edit()` - Frontend forms
   - `traceView()` - Frontend view
   - `dashboard()` - Frontend view
   - `compareBricks()` - Merge ke `compare()`

2. **UPDATE Methods:**
   - `index()` - Return JSON pagination
   - `log()` - Return JSON pagination
   - `store()` - Return 201 dengan MaterialCalculationResource
   - `show()` - Return MaterialCalculationResource
   - `update()` - Return MaterialCalculationResource
   - `destroy()` - Return 204

3. **KEEP Methods (already JSON):**
   - `calculate()` ✓
   - `compare()` ✓
   - `traceCalculation()` ✓
   - `getBrickDimensions()` ✓
   - **All protected helper methods** ✓

4. **NO CHANGE to Business Logic:**
   - `generateCombinations()` ✓
   - `calculateCombinationsForBrick()` ✓
   - `getBestCombinations()` ✓
   - All selection helpers ✓

---

#### Supporting Controllers (4 files)
```
app/Http/Controllers/
├── WorkItemController.php
├── RecommendedCombinationController.php
├── UnitController.php
└── MaterialController.php (consider removing)
```

**Same pattern:** Remove create/edit, update CRUD to JSON, use Resources

---

### 2. MODELS (1 file)

```
app/Models/User.php
```

**Perubahan:**
```php
// Add at top
use Laravel\Sanctum\HasApiTokens;

// Add to class
class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable; // Add HasApiTokens

    // ... rest unchanged
}
```

**NO CHANGES to other models** (Brick, Cement, Sand, Cat, etc) - Business logic tetap intact!

---

### 3. CONFIG FILES (3 files)

#### config/auth.php
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    // ADD THIS:
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

#### config/cors.php
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // IMPORTANT for Sanctum
];
```

#### config/sanctum.php
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),

'expiration' => null, // Tokens never expire (atau set ke 60 * 24 untuk 24 jam)
```

---

### 4. EXCEPTION HANDLER (1 file)

```
app/Exceptions/Handler.php
```

**Perubahan:**
```php
public function render($request, Throwable $exception)
{
    // For API routes, return JSON
    if ($request->is('api/*')) {
        return $this->handleApiException($request, $exception);
    }

    return parent::render($request, $exception);
}

protected function handleApiException($request, Throwable $exception)
{
    $status = 500;
    $message = 'Internal server error';

    if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
        $status = 404;
        $message = 'Resource not found';
    } elseif ($exception instanceof \Illuminate\Validation\ValidationException) {
        $status = 422;
        $message = 'Validation error';
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $exception->errors(),
        ], $status);
    } elseif ($exception instanceof \Illuminate\Auth\AuthenticationException) {
        $status = 401;
        $message = 'Unauthenticated';
    } elseif ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
        $status = $exception->getStatusCode();
        $message = $exception->getMessage();
    }

    return response()->json([
        'success' => false,
        'message' => $message,
    ], $status);
}
```

---

### 5. KERNEL (1 file)

```
app/Http/Kernel.php
```

**Perubahan:**
```php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \App\Http\Middleware\ForceJsonResponse::class, // ADD
    ],
];
```

---

### 6. ENVIRONMENT (.env)

**ADD:**
```env
# Frontend URL for CORS
FRONTEND_URL=http://localhost:3000

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost
```

---

### 7. COMPOSER (composer.json)

**ADD Dependency:**
```bash
composer require laravel/sanctum
```

---

## ROADMAP PENGERJAAN LENGKAP

### 📦 PHASE 1: SETUP & INSTALLATION (Week 1)
**Duration:** 5 hari
**Status:** Foundation

#### Tasks:
1. **Install Laravel Sanctum** (2 jam)
   ```bash
   composer require laravel/sanctum
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   php artisan migrate
   ```

2. **Configure CORS** (1 jam)
   - Update `config/cors.php`
   - Test dengan frontend dummy request

3. **Update User Model** (30 menit)
   - Add `HasApiTokens` trait

4. **Configure Sanctum** (1 jam)
   - Update `config/sanctum.php`
   - Add stateful domains

5. **Update Kernel** (30 menit)
   - Add middleware ke `api` group

6. **Create ForceJsonResponse Middleware** (1 jam)
   ```bash
   php artisan make:middleware ForceJsonResponse
   ```

7. **Update Exception Handler** (2 jam)
   - Add `handleApiException()` method

8. **Test Setup** (2 jam)
   - Create simple test endpoint
   - Verify JSON responses
   - Verify CORS working

**Deliverable:** ✅ Laravel Sanctum installed dan configured, API infrastructure ready

---

### 🏗️ PHASE 2: CORE INFRASTRUCTURE (Week 1-2)
**Duration:** 3 hari
**Status:** Reusable components

#### Tasks:
1. **Create ApiResponse Trait** (2 jam)
   ```bash
   # Manual create: app/Traits/ApiResponse.php
   ```
   - Implement all response methods
   - Add PHPDoc

2. **Create API Resources** (1 hari)
   ```bash
   php artisan make:resource BrickResource
   php artisan make:resource BrickCollection
   php artisan make:resource CementResource
   php artisan make:resource SandResource
   php artisan make:resource CatResource
   php artisan make:resource MaterialCalculationResource
   php artisan make:resource MaterialCalculationCollection
   php artisan make:resource WorkItemResource
   php artisan make:resource RecommendedCombinationResource
   php artisan make:resource UnitResource
   ```
   - Implement `toArray()` untuk setiap resource
   - Add conditional fields (photo_url, etc)

3. **Create Form Requests - Auth** (2 jam)
   ```bash
   php artisan make:request Auth/LoginRequest
   php artisan make:request Auth/RegisterRequest
   ```

4. **Create Form Requests - Materials** (4 jam)
   ```bash
   php artisan make:request Brick/StoreBrickRequest
   php artisan make:request Brick/UpdateBrickRequest
   # Repeat for Cement, Sand, Cat
   ```
   - Copy validation rules dari controllers
   - Add `authorize()` method (return true untuk sekarang)

5. **Create Form Requests - Calculations** (2 jam)
   ```bash
   php artisan make:request MaterialCalculation/StoreCalculationRequest
   php artisan make:request MaterialCalculation/UpdateCalculationRequest
   php artisan make:request MaterialCalculation/CalculateRequest
   ```

6. **Create Form Requests - WorkItem** (1 jam)
   ```bash
   php artisan make:request WorkItem/StoreWorkItemRequest
   php artisan make:request WorkItem/UpdateWorkItemRequest
   ```

**Deliverable:** ✅ Reusable components ready (Resources, Requests, Traits)

---

### 🔐 PHASE 3: AUTHENTICATION SYSTEM (Week 2)
**Duration:** 3 hari
**Status:** Auth infrastructure

#### Tasks:
1. **Create AuthController** (4 jam)
   ```bash
   mkdir app/Http/Controllers/Api
   php artisan make:controller Api/AuthController
   ```
   - Implement `register()`
   - Implement `login()`
   - Implement `logout()`
   - Implement `me()`

2. **Create API Routes** (2 jam)
   - Update `routes/api.php`
   - Add `/v1/register`, `/v1/login`, `/v1/logout`, `/v1/me`
   - Add `auth:sanctum` middleware group

3. **Test Authentication** (4 jam)
   - Manual testing dengan Postman/Insomnia
   - Test register flow
   - Test login flow (get token)
   - Test protected endpoint dengan token
   - Test logout (revoke token)

4. **Create Auth Tests** (4 jam)
   ```bash
   php artisan make:test Api/Auth/LoginTest
   php artisan make:test Api/Auth/RegisterTest
   php artisan make:test Api/Auth/LogoutTest
   ```
   - Test successful login
   - Test validation errors
   - Test unauthorized access

**Deliverable:** ✅ Working authentication system dengan Sanctum tokens

---

### 🧱 PHASE 4: MATERIAL CRUD APIs (Week 2-3)
**Duration:** 8 hari (2 hari per controller)
**Status:** Core business functionality

#### 4.1 BrickController (2 hari)
1. **Refactor Controller** (4 jam)
   - Add `use ApiResponse, BrickResource, BrickCollection`
   - Remove `create()`, `edit()`
   - Update `index()` - return BrickResource::collection()
   - Update `store()` - use StoreBrickRequest, return 201
   - Update `show()` - return BrickResource
   - Update `update()` - use UpdateBrickRequest
   - Update `destroy()` - return 204
   - Handle photo upload dengan return URL

2. **Add Routes** (30 menit)
   ```php
   Route::apiResource('bricks', BrickController::class);
   Route::get('/bricks/field-values/{field}', 'getFieldValues');
   Route::get('/bricks/all-stores', 'getAllStores');
   Route::get('/bricks/addresses-by-store', 'getAddressesByStore');
   ```

3. **Manual Testing** (2 jam)
   - GET /api/v1/bricks (list dengan pagination)
   - POST /api/v1/bricks (create dengan photo)
   - GET /api/v1/bricks/{id}
   - PUT /api/v1/bricks/{id}
   - DELETE /api/v1/bricks/{id}
   - Test autocomplete endpoints

4. **Write Tests** (3 jam)
   ```bash
   php artisan make:test Api/BrickTest
   ```
   - Test CRUD operations
   - Test validation
   - Test file upload
   - Test autocomplete

5. **Bug Fixes** (2 jam)
   - Fix issues dari testing

#### 4.2 CementController (2 hari)
- **Same pattern as BrickController**
- Copy-paste approach dengan adjustment untuk Cement specifics

#### 4.3 SandController (2 hari)
- **Same pattern**

#### 4.4 CatController (2 hari)
- **Same pattern**

**Deliverable:** ✅ 4 Material CRUD APIs working dengan tests

---

### 🧮 PHASE 5: CALCULATION APIs (Week 3-4)
**Duration:** 7 hari
**Status:** Complex business logic

#### Tasks:
1. **Analyze Current API Methods** (4 jam)
   - Review `calculate()`, `compare()`, `trace()` - already JSON
   - Identify yang perlu minimal changes

2. **Refactor Controller** (2 hari)
   - Add `use ApiResponse, MaterialCalculationResource`
   - Remove `create()`, `edit()`, `traceView()`, `dashboard()`, `compareBricks()`
   - Update `index()` - return paginated JSON
   - Update `log()` - return paginated JSON
   - Update `store()` - use StoreCalculationRequest, return 201
   - Update `show()` - return MaterialCalculationResource
   - Update `update()` - use UpdateCalculationRequest
   - Update `destroy()` - return 204
   - **KEEP all helper methods unchanged**
   - Ensure `calculate()`, `compare()`, `trace()` masih working

3. **Add Routes** (1 jam)
   ```php
   Route::apiResource('calculations', MaterialCalculationController::class);
   Route::post('/calculations/calculate', 'calculate');
   Route::post('/calculations/compare', 'compare');
   Route::post('/calculations/trace', 'traceCalculation');
   Route::get('/calculations/brick-dimensions/{brickId}', 'getBrickDimensions');
   ```

4. **Manual Testing** (2 hari)
   - Test all CRUD operations
   - Test `calculate()` dengan berbagai parameter
   - Test `compare()` dengan multiple installation types
   - Test `trace()` untuk verify calculations
   - Test material selection (best, cheapest, medium, expensive, common, custom)
   - Test multi-brick scenarios
   - **CRITICAL:** Compare hasil calculation dengan old system

5. **Write Tests** (1 hari)
   ```bash
   php artisan make:test Api/MaterialCalculationTest
   ```
   - Test calculation accuracy
   - Test material selection logic
   - Test combination generation

6. **Bug Fixes & Validation** (1 hari)
   - Fix calculation discrepancies
   - Ensure business logic intact

**Deliverable:** ✅ Calculation APIs working, business logic verified correct

---

### 🔧 PHASE 6: SUPPORTING APIs (Week 4-5)
**Duration:** 6 hari (2 hari per controller)
**Status:** Supporting features

#### 6.1 WorkItemController (2 hari)
1. **Refactor Controller** (4 jam)
   - Same CRUD pattern
   - Update `analytics($code)` - return JSON

2. **Add Routes** (30 menit)
   ```php
   Route::apiResource('work-items', WorkItemController::class);
   Route::get('/work-items/{code}/analytics', 'analytics');
   ```

3. **Test** (3 jam)
   - CRUD operations
   - Analytics endpoint

#### 6.2 RecommendedCombinationController (2 hari)
1. **Refactor Controller** (3 jam)
   - Update `index()` - return JSON grouped data
   - Update `store()` - return JSON with validation

2. **Add Routes** (30 menit)
   ```php
   Route::get('/recommendations', 'index');
   Route::post('/recommendations', 'store');
   ```

3. **Test** (2 jam)

#### 6.3 UnitController (2 hari)
1. **Refactor Controller** (3 jam)
   - Standard CRUD to JSON

2. **Add Routes** (30 menit)
   ```php
   Route::apiResource('units', UnitController::class);
   ```

3. **Test** (2 jam)

**Deliverable:** ✅ All supporting APIs complete

---

### 🧪 PHASE 7: TESTING & DOCUMENTATION (Week 6-7)
**Duration:** 10 hari
**Status:** Quality assurance

#### Tasks:
1. **Integration Testing** (3 hari)
   - End-to-end flow testing
   - Material selection → Calculation → Save
   - Multi-user scenarios
   - Concurrent requests

2. **Performance Testing** (2 hari)
   - Load testing dengan Apache Bench / k6
   - Database query optimization (N+1 queries)
   - Response time benchmarks
   - Caching strategy (Redis)

3. **Security Audit** (2 hari)
   - SQL injection testing
   - Mass assignment testing
   - File upload security
   - Rate limiting testing
   - CORS verification

4. **API Documentation** (3 hari)
   - Install L5-Swagger:
     ```bash
     composer require darkaonline/l5-swagger
     php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
     ```
   - Add PHPDoc annotations ke controllers
   - Generate Swagger docs:
     ```bash
     php artisan l5-swagger:generate
     ```
   - Create Postman Collection
   - Write README.md dengan API usage examples

**Deliverable:** ✅ Tested, documented, production-ready API

---

### 🚀 PHASE 8: DEPLOYMENT (Week 7-8)
**Duration:** 5 hari
**Status:** Production release

#### Tasks:
1. **Staging Environment Setup** (1 hari)
   - Configure server (Nginx/Apache)
   - Setup SSL certificate
   - Configure `.env` untuk staging
   - Database migration

2. **Production Deployment** (1 hari)
   - Deploy ke production server
   - Configure CORS untuk production domain
   - Setup monitoring (Laravel Telescope, New Relic, dll)
   - Setup logging (Papertrail, Loggly, dll)

3. **Smoke Testing** (1 hari)
   - Verify all endpoints working
   - Performance check
   - Security check

4. **Documentation Finalization** (1 hari)
   - API documentation published
   - Deployment guide
   - Troubleshooting guide

5. **Handover** (1 hari)
   - Team training
   - Knowledge transfer
   - Support documentation

**Deliverable:** ✅ Production-ready REST API deployed

---

## DEPENDENCIES & PREREQUISITES

### Tools Required:
- PHP 8.x
- Composer
- Laravel 10.x
- MySQL/PostgreSQL
- Postman/Insomnia (API testing)
- Git

### Skills Required:
- Laravel framework
- RESTful API principles
- Sanctum authentication
- API testing
- JSON handling

---

## CRITICAL SUCCESS FACTORS

1. ✅ **Business Logic Preservation**
   - All calculation formulas must remain accurate
   - Formula Registry system unchanged
   - Model methods unchanged

2. ✅ **Backward Compatibility** (Optional)
   - Keep `routes/web.php` untuk transisi
   - Dual mode: Web + API

3. ✅ **Testing Coverage**
   - Minimum 80% code coverage
   - All critical paths tested

4. ✅ **Performance**
   - Response time < 500ms untuk CRUD
   - Calculation endpoint < 1s

5. ✅ **Security**
   - Sanctum tokens properly validated
   - Rate limiting configured
   - CORS properly configured

---

## ROLLBACK PLAN

Jika migration gagal:
1. Git revert ke commit sebelum migration
2. Restore database dari backup
3. Re-deploy old version
4. Investigate issue
5. Fix dan retry

**Mitigation:** Use feature branches, deploy ke staging first

---

## MONITORING & MAINTENANCE

Post-deployment:
1. **Monitoring:**
   - API response times
   - Error rates
   - Token usage
   - Database performance

2. **Logging:**
   - All API requests
   - Errors dengan stack trace
   - Authentication failures

3. **Maintenance:**
   - Regular security updates
   - Database optimization
   - Cache clearing
   - Token cleanup (revoked tokens)

---

## CONTACTS & SUPPORT

**Project Lead:** [Your Name]
**Backend Developer:** [Developer Name]
**Frontend Developer:** [Developer Name]
**DevOps:** [DevOps Name]

---

## APPENDIX

### A. Standard API Response Format

**Success:**
```json
{
  "success": true,
  "data": { /* resource */ },
  "message": "Operation successful"
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": { /* validation errors */ }
}
```

**Paginated:**
```json
{
  "data": [/* items */],
  "links": { /* pagination links */ },
  "meta": { /* pagination meta */ }
}
```

### B. HTTP Status Codes

- `200 OK` - GET, PUT success
- `201 Created` - POST success
- `204 No Content` - DELETE success
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Missing/invalid token
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

### C. Authentication Flow

1. **Register:** `POST /api/v1/register`
   ```json
   {
     "name": "John Doe",
     "email": "john@example.com",
     "password": "secret",
     "password_confirmation": "secret"
   }
   ```
   Response:
   ```json
   {
     "success": true,
     "data": {
       "user": { /* user data */ },
       "token": "1|xxxxxxxxxxxxx"
     }
   }
   ```

2. **Login:** `POST /api/v1/login`
   ```json
   {
     "email": "john@example.com",
     "password": "secret"
   }
   ```
   Response: Same as register

3. **Use Token:** Add header to all protected requests
   ```
   Authorization: Bearer 1|xxxxxxxxxxxxx
   ```

4. **Logout:** `POST /api/v1/logout`
   - Revokes current token

---

## REVISION HISTORY

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2025-12-30 | Initial plan | Claude Code |

---

**END OF DOCUMENT**
