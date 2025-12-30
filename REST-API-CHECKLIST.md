# REST API MIGRATION - CHECKLIST

Quick reference untuk tracking progress harian.

---

## 📦 PHASE 1: SETUP (Week 1 - 5 hari)

- [ ] Install Laravel Sanctum (`composer require laravel/sanctum`)
- [ ] Publish Sanctum config (`php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`)
- [ ] Run migration (`php artisan migrate`)
- [ ] Update `config/cors.php` (allowed_origins, supports_credentials)
- [ ] Update `config/sanctum.php` (stateful domains)
- [ ] Update `config/auth.php` (add 'api' guard)
- [ ] Update `app/Models/User.php` (add HasApiTokens trait)
- [ ] Update `app/Http/Kernel.php` (add middleware to api group)
- [ ] Create `app/Http/Middleware/ForceJsonResponse.php`
- [ ] Update `app/Exceptions/Handler.php` (add handleApiException method)
- [ ] Add `.env` variables (FRONTEND_URL, SANCTUM_STATEFUL_DOMAINS)
- [ ] Test: Create dummy API endpoint, verify JSON response
- [ ] Test: Verify CORS working dari frontend

---

## 🏗️ PHASE 2: CORE INFRASTRUCTURE (Week 1-2 - 3 hari)

### Trait
- [ ] Create `app/Traits/ApiResponse.php`
  - [ ] successResponse()
  - [ ] createdResponse()
  - [ ] errorResponse()
  - [ ] validationErrorResponse()
  - [ ] paginatedResponse()

### API Resources (10 files)
- [ ] `php artisan make:resource BrickResource`
- [ ] `php artisan make:resource BrickCollection`
- [ ] `php artisan make:resource CementResource`
- [ ] `php artisan make:resource SandResource`
- [ ] `php artisan make:resource CatResource`
- [ ] `php artisan make:resource MaterialCalculationResource`
- [ ] `php artisan make:resource MaterialCalculationCollection`
- [ ] `php artisan make:resource WorkItemResource`
- [ ] `php artisan make:resource RecommendedCombinationResource`
- [ ] `php artisan make:resource UnitResource`

### Form Requests - Auth
- [ ] `php artisan make:request Auth/LoginRequest`
- [ ] `php artisan make:request Auth/RegisterRequest`

### Form Requests - Materials
- [ ] `php artisan make:request Brick/StoreBrickRequest`
- [ ] `php artisan make:request Brick/UpdateBrickRequest`
- [ ] `php artisan make:request Cement/StoreCementRequest`
- [ ] `php artisan make:request Cement/UpdateCementRequest`
- [ ] `php artisan make:request Sand/StoreSandRequest`
- [ ] `php artisan make:request Sand/UpdateSandRequest`
- [ ] `php artisan make:request Cat/StoreCatRequest`
- [ ] `php artisan make:request Cat/UpdateCatRequest`

### Form Requests - Calculations
- [ ] `php artisan make:request MaterialCalculation/StoreCalculationRequest`
- [ ] `php artisan make:request MaterialCalculation/UpdateCalculationRequest`
- [ ] `php artisan make:request MaterialCalculation/CalculateRequest`

### Form Requests - WorkItem
- [ ] `php artisan make:request WorkItem/StoreWorkItemRequest`
- [ ] `php artisan make:request WorkItem/UpdateWorkItemRequest`

---

## 🔐 PHASE 3: AUTHENTICATION (Week 2 - 3 hari)

### AuthController
- [ ] Create `app/Http/Controllers/Api/AuthController.php`
- [ ] Implement register() method
- [ ] Implement login() method
- [ ] Implement logout() method
- [ ] Implement me() method

### Routes
- [ ] Update `routes/api.php`
- [ ] Add POST /v1/register
- [ ] Add POST /v1/login
- [ ] Add POST /v1/logout (protected)
- [ ] Add GET /v1/me (protected)

### Testing
- [ ] Manual test: Register new user
- [ ] Manual test: Login (get token)
- [ ] Manual test: Access /me with token
- [ ] Manual test: Logout (revoke token)
- [ ] Create `tests/Feature/Api/Auth/LoginTest.php`
- [ ] Create `tests/Feature/Api/Auth/RegisterTest.php`
- [ ] Create `tests/Feature/Api/Auth/LogoutTest.php`
- [ ] Run tests: `php artisan test`

---

## 🧱 PHASE 4: MATERIAL CRUD (Week 2-3 - 8 hari)

### BrickController (2 hari)
- [ ] Add `use ApiResponse, BrickResource, BrickCollection`
- [ ] Add `use StoreBrickRequest, UpdateBrickRequest`
- [ ] Remove create() method
- [ ] Remove edit() method
- [ ] Update index() - return BrickResource::collection()
- [ ] Update store() - use StoreBrickRequest, return 201
- [ ] Update show() - return BrickResource
- [ ] Update update() - use UpdateBrickRequest
- [ ] Update destroy() - return 204
- [ ] Keep getFieldValues() unchanged
- [ ] Keep getAllStores() unchanged
- [ ] Keep getAddressesByStore() unchanged
- [ ] Add routes to api.php
- [ ] Manual test: GET /bricks (list)
- [ ] Manual test: POST /bricks (create with photo)
- [ ] Manual test: GET /bricks/{id}
- [ ] Manual test: PUT /bricks/{id}
- [ ] Manual test: DELETE /bricks/{id}
- [ ] Manual test: Autocomplete endpoints
- [ ] Create `tests/Feature/Api/BrickTest.php`
- [ ] Run tests

### CementController (2 hari)
- [ ] Same steps as BrickController
- [ ] Replace "Brick" with "Cement"

### SandController (2 hari)
- [ ] Same steps as BrickController
- [ ] Replace "Brick" with "Sand"

### CatController (2 hari)
- [ ] Same steps as BrickController
- [ ] Replace "Brick" with "Cat"

---

## 🧮 PHASE 5: CALCULATION APIs (Week 3-4 - 7 hari)

### MaterialCalculationController
- [ ] Add `use ApiResponse, MaterialCalculationResource`
- [ ] Add `use StoreCalculationRequest, UpdateCalculationRequest, CalculateRequest`
- [ ] Remove create() method
- [ ] Remove edit() method
- [ ] Remove traceView() method
- [ ] Remove dashboard() method
- [ ] Remove compareBricks() method
- [ ] Update index() - return paginated JSON
- [ ] Update log() - return paginated JSON
- [ ] Update store() - use StoreCalculationRequest, return 201
- [ ] Update show() - return MaterialCalculationResource
- [ ] Update update() - use UpdateCalculationRequest
- [ ] Update destroy() - return 204
- [ ] Verify calculate() still working (already JSON)
- [ ] Verify compare() still working (already JSON)
- [ ] Verify traceCalculation() still working (already JSON)
- [ ] Verify getBrickDimensions() still working (already JSON)
- [ ] **VERIFY: All protected helper methods unchanged**
- [ ] Add routes to api.php

### Testing (CRITICAL)
- [ ] Manual test: POST /calculations (save calculation)
- [ ] Manual test: GET /calculations (list)
- [ ] Manual test: GET /calculations/{id}
- [ ] Manual test: PUT /calculations/{id}
- [ ] Manual test: DELETE /calculations/{id}
- [ ] Manual test: POST /calculations/calculate (real-time)
- [ ] Manual test: POST /calculations/compare
- [ ] Manual test: POST /calculations/trace
- [ ] **Compare hasil calculation dengan old system** (sama persis!)
- [ ] Test material selection: best
- [ ] Test material selection: cheapest
- [ ] Test material selection: medium
- [ ] Test material selection: expensive
- [ ] Test material selection: common
- [ ] Test material selection: custom
- [ ] Test multi-brick scenarios
- [ ] Create `tests/Feature/Api/MaterialCalculationTest.php`
- [ ] Run tests

---

## 🔧 PHASE 6: SUPPORTING APIs (Week 4-5 - 6 hari)

### WorkItemController (2 hari)
- [ ] Add `use ApiResponse, WorkItemResource`
- [ ] Add `use StoreWorkItemRequest, UpdateWorkItemRequest`
- [ ] Remove create(), edit()
- [ ] Update CRUD methods to JSON
- [ ] Update analytics() - return JSON
- [ ] Add routes to api.php
- [ ] Manual testing
- [ ] Create `tests/Feature/Api/WorkItemTest.php`

### RecommendedCombinationController (2 hari)
- [ ] Add `use ApiResponse, RecommendedCombinationResource`
- [ ] Update index() - return JSON grouped data
- [ ] Update store() - return JSON with validation
- [ ] Add routes to api.php
- [ ] Manual testing
- [ ] Create tests

### UnitController (2 hari)
- [ ] Standard CRUD to API
- [ ] Add routes to api.php
- [ ] Manual testing
- [ ] Create tests

---

## 🧪 PHASE 7: TESTING & DOCS (Week 6-7 - 10 hari)

### Integration Testing (3 hari)
- [ ] End-to-end flow: Material selection → Calculation → Save
- [ ] Multi-user scenarios
- [ ] Concurrent requests testing
- [ ] Edge cases testing

### Performance Testing (2 hari)
- [ ] Install load testing tool (k6 / Apache Bench)
- [ ] Load test all endpoints
- [ ] Check for N+1 queries (`php artisan telescope`)
- [ ] Optimize slow queries
- [ ] Add database indexes if needed
- [ ] Consider caching strategy (Redis)

### Security Audit (2 hari)
- [ ] SQL injection testing
- [ ] Mass assignment testing
- [ ] File upload security (mime types, size)
- [ ] Rate limiting testing
- [ ] CORS verification
- [ ] Token security (expiration, revocation)

### Documentation (3 hari)
- [ ] Install L5-Swagger (`composer require darkaonline/l5-swagger`)
- [ ] Publish config (`php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"`)
- [ ] Add PHPDoc annotations to controllers
- [ ] Generate Swagger docs (`php artisan l5-swagger:generate`)
- [ ] Access docs at `/api/documentation`
- [ ] Create Postman Collection
- [ ] Export Postman Collection (share dengan team)
- [ ] Write README.md (API usage, authentication, examples)
- [ ] Write DEPLOYMENT.md (server setup, environment variables)

---

## 🚀 PHASE 8: DEPLOYMENT (Week 7-8 - 5 hari)

### Staging Environment (1 hari)
- [ ] Setup staging server
- [ ] Configure web server (Nginx/Apache)
- [ ] Install SSL certificate
- [ ] Configure `.env` untuk staging
- [ ] Run migrations
- [ ] Deploy code
- [ ] Test all endpoints di staging

### Production Deployment (1 hari)
- [ ] Backup production database
- [ ] Deploy to production server
- [ ] Run migrations
- [ ] Configure CORS untuk production domain
- [ ] Setup monitoring (Telescope, New Relic, Sentry)
- [ ] Setup logging (Papertrail, CloudWatch)
- [ ] Configure rate limiting

### Smoke Testing (1 hari)
- [ ] Verify all endpoints working
- [ ] Performance check (response times)
- [ ] Security check (HTTPS, tokens)
- [ ] Error logging working
- [ ] Monitoring dashboard setup

### Documentation & Handover (2 hari)
- [ ] Publish API documentation (public URL)
- [ ] Write deployment guide
- [ ] Write troubleshooting guide
- [ ] Team training session
- [ ] Knowledge transfer
- [ ] Support documentation (common issues, solutions)

---

## POST-DEPLOYMENT MONITORING

### Week 1-2 After Launch
- [ ] Monitor API response times daily
- [ ] Monitor error rates (setup alerts)
- [ ] Monitor token usage
- [ ] Monitor database performance
- [ ] Review logs for issues
- [ ] Collect user feedback
- [ ] Fix critical bugs immediately

### Ongoing
- [ ] Weekly performance review
- [ ] Monthly security audit
- [ ] Regular dependency updates
- [ ] Database optimization (analyze slow queries)
- [ ] Token cleanup (revoked tokens)
- [ ] Documentation updates

---

## OPTIONAL ENHANCEMENTS (Future)

- [ ] API versioning (v2 preparation)
- [ ] GraphQL endpoint (alternative to REST)
- [ ] Websocket support (real-time updates)
- [ ] Export to Excel/PDF via API
- [ ] Batch operations (bulk create/update/delete)
- [ ] Advanced filtering (query builder for frontend)
- [ ] API analytics (usage metrics, popular endpoints)
- [ ] Multi-language support (i18n)
- [ ] Admin panel untuk API management

---

## ROLLBACK CHECKLIST (Emergency)

If something goes wrong:
- [ ] Stop deployment
- [ ] Git revert to last stable commit
- [ ] Restore database from backup
- [ ] Re-deploy old version
- [ ] Verify old system working
- [ ] Investigate root cause
- [ ] Fix issue in development
- [ ] Test thoroughly
- [ ] Retry deployment

---

## NOTES & LEARNINGS

### Issues Encountered:
- [Tulis issue yang ditemukan selama development]

### Solutions:
- [Tulis solusi yang berhasil]

### Best Practices Learned:
- [Tulis lessons learned untuk future projects]

---

## CONTACTS

**Questions?** Contact:
- Backend Lead: [Name] - [Email]
- DevOps: [Name] - [Email]
- Frontend Lead: [Name] - [Email]

---

**Last Updated:** December 30, 2025
**Status:** Planning Phase
**Next Review:** [Date]
