# API TESTING GUIDE
Material Database REST API - Manual Testing

---

## 🚀 Quick Start

**Server URL:** `http://localhost:8000`

**API Base URL:** `http://localhost:8000/api/v1`

---

## 📋 TEST CHECKLIST

### ✅ BRICK API

#### 1. List Bricks (GET)
```bash
URL: http://localhost:8000/api/v1/bricks
Method: GET
Expected: JSON with pagination

# With search
URL: http://localhost:8000/api/v1/bricks?search=merah
```

#### 2. Get Single Brick (GET)
```bash
URL: http://localhost:8000/api/v1/bricks/1
Method: GET
Expected: Single brick JSON
```

#### 3. Create Brick (POST)
```bash
URL: http://localhost:8000/api/v1/bricks
Method: POST
Headers: Content-Type: multipart/form-data
Body:
  - brand: "Merah Delanggu"
  - type: "Press"
  - form: "Bata Merah"
  - dimension_length: 23
  - dimension_width: 11
  - dimension_height: 5
  - price_per_piece: 1200
  - store: "Toko ABC"
  - photo: [file upload]

Expected: 201 Created with brick data
```

#### 4. Update Brick (PUT)
```bash
URL: http://localhost:8000/api/v1/bricks/1
Method: PUT
Body: Same as create (partial update OK)
Expected: 200 OK with updated brick
```

#### 5. Delete Brick (DELETE)
```bash
URL: http://localhost:8000/api/v1/bricks/1
Method: DELETE
Expected: 204 No Content
```

#### 6. Autocomplete - Field Values (GET)
```bash
URL: http://localhost:8000/api/v1/bricks/field-values/brand
Method: GET
Expected: Array of unique brands

# With search
URL: http://localhost:8000/api/v1/bricks/field-values/brand?search=merah
```

#### 7. Get All Stores (GET)
```bash
URL: http://localhost:8000/api/v1/bricks/all-stores
Method: GET
Expected: Array of unique stores
```

#### 8. Get Addresses by Store (GET)
```bash
URL: http://localhost:8000/api/v1/bricks/addresses-by-store?store=Toko%20ABC
Method: GET
Expected: Array of addresses for that store
```

---

### ✅ CEMENT API

Same endpoints as Brick, just replace `/bricks` with `/cements`

```bash
GET    /api/v1/cements
POST   /api/v1/cements
GET    /api/v1/cements/1
PUT    /api/v1/cements/1
DELETE /api/v1/cements/1
GET    /api/v1/cements/field-values/{field}
GET    /api/v1/cements/all-stores
GET    /api/v1/cements/addresses-by-store?store=xxx
```

**Sample Create Body:**
```json
{
  "cement_name": "Semen Gresik 40kg",
  "brand": "Gresik",
  "type": "Portland Cement",
  "package_unit": "sak",
  "package_weight_gross": 40,
  "package_price": 65000,
  "store": "Toko Material Jaya"
}
```

---

### ✅ SAND API

Same endpoints, replace with `/sands`

**Sample Create Body:**
```json
{
  "sand_name": "Pasir Bangka Halus",
  "brand": "Bangka",
  "type": "Pasir Halus",
  "package_unit": "kubik",
  "package_price": 350000,
  "store": "Supplier Pasir XYZ"
}
```

---

### ✅ CAT API

Same endpoints, replace with `/cats`

**Sample Create Body:**
```json
{
  "cat_name": "Cat Tembok Avitex White",
  "brand": "Avitex",
  "type": "Cat Tembok",
  "color_name": "White",
  "color_code": "#FFFFFF",
  "package_unit": "kaleng",
  "package_weight_gross": 5,
  "volume": 5,
  "volume_unit": "liter",
  "purchase_price": 125000,
  "store": "Toko Cat Warna Warni"
}
```

---

## 🧪 TESTING TOOLS

### Option 1: Browser
- Simple GET requests bisa langsung di browser
- Contoh: `http://localhost:8000/api/v1/bricks`

### Option 2: Postman
1. Download Postman
2. Import collection (akan dibuat)
3. Test semua endpoints

### Option 3: cURL (Command Line)
```bash
# List bricks
curl http://localhost:8000/api/v1/bricks

# Get single brick
curl http://localhost:8000/api/v1/bricks/1

# Create brick
curl -X POST http://localhost:8000/api/v1/bricks \
  -F "brand=Test Brand" \
  -F "type=Test Type" \
  -F "price_per_piece=1000"

# Delete brick
curl -X DELETE http://localhost:8000/api/v1/bricks/1
```

### Option 4: PHP Test Script
See `test-api.php` in project root

---

## ✅ SUCCESS CRITERIA

### Expected Responses:

**Success (200):**
```json
{
  "success": true,
  "data": { /* resource data */ },
  "message": "optional message"
}
```

**Created (201):**
```json
{
  "success": true,
  "data": { /* created resource */ },
  "message": "Brick created successfully"
}
```

**No Content (204):**
```
(empty response)
```

**Error (422 - Validation):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "brand": ["The brand field is required."]
  }
}
```

**Error (404 - Not Found):**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

---

## 🐛 COMMON ISSUES

### 1. CORS Error
**Solution:** Check `config/cors.php` - `allowed_origins` should include frontend URL

### 2. 404 on API routes
**Solution:** Check `routes/api.php` registered, run `php artisan route:clear`

### 3. 500 Internal Server Error
**Solution:** Check `storage/logs/laravel.log` for details

### 4. Photo upload fails
**Solution:** Check `storage/app/public` exists, run `php artisan storage:link`

---

## 📊 TEST RESULTS TEMPLATE

```
TEST RESULTS - [DATE]

BRICK API:
✅ GET /bricks - List working
✅ GET /bricks/1 - Show working
✅ POST /bricks - Create working
✅ PUT /bricks/1 - Update working
✅ DELETE /bricks/1 - Delete working
✅ Autocomplete endpoints - Working

CEMENT API:
[ ] GET /cements - List
[ ] POST /cements - Create
[ ] ...

SAND API:
[ ] ...

CAT API:
[ ] ...

ISSUES FOUND:
- None / [List issues here]

NOTES:
- [Any observations]
```

---

**Last Updated:** December 30, 2025
