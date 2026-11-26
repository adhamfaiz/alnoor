Al-Noor Invoice API
1. Configure environment

php artisan key:generate


2. Run migrations
php artisan migrate

3. Start the development server
php artisan serve

The API is now available at `http://127.0.0.1:8000/api`.

4. Interactive API Documentation (Swagger UI)
- **Swagger UI:** `http://127.0.0.1:8000/api/documentation`

Regenerate the OpenAPI spec after updating annotations:

php artisan l5-swagger:generate

5. Run tests

php artisan test


## API Endpoints

### `POST /api/invoices` — Create Invoice

Creates a new invoice. The server computes `subtotal`, `tax`, and `total`; any client-supplied totals are **ignored**.

**Request:**

```bash
curl -s -X POST http://127.0.0.1:8000/api/invoices \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer": "محمد الحميري",
    "items": [
      { "name": "رز بسمتي 10 كيلو", "qty": 1, "price": 18000 },
      { "name": "سكر 5 كيلو",       "qty": 2, "price": 6500  }
    ]
  }'
```

**Response (`201 Created`):**

```json
{
  "data": {
    "id": 1,
    "customer": "محمد الحميري",
    "currency": "YER",
    "subtotal": 31000,
    "tax": 4650,
    "total": 35650,
    "items": [
      { "id": 1, "name": "رز بسمتي 10 كيلو", "qty": 1, "price": 18000, "line_total": 18000 },
      { "id": 2, "name": "سكر 5 كيلو",       "qty": 2, "price": 6500,  "line_total": 13000 }
    ],
    "created_at": "2024-01-15T12:30:00+00:00",
    "updated_at": "2024-01-15T12:30:00+00:00"
  }
}
```

**Validation error (`422 Unprocessable Entity`):**

```bash
curl -s -X POST http://127.0.0.1:8000/api/invoices \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{ "customer": "", "items": [] }'
```

```json
{
  "message": "The customer field is required. (and 1 more error)",
  "errors": {
    "customer": ["The customer field is required."],
    "items": ["The items field must have at least 1 items."]
  }
}
```

---

### `GET /api/invoices/{id}` — Get Invoice by ID

**Request:**

```bash
curl -s http://127.0.0.1:8000/api/invoices/1 \
  -H "Accept: application/json"
```

**Response (`200 OK`):** Same shape as the create response above.

**Not found (`404`):**

```bash
curl -s http://127.0.0.1:8000/api/invoices/99999 \
  -H "Accept: application/json"
```

```json
{
  "message": "Invoice not found."
}
```

---

### `GET /api/invoices` — List Invoices (Paginated + Search)

**Request:**

```bash
curl -s "http://127.0.0.1:8000/api/invoices?customer=الحميري&page=1&per_page=10" \
  -H "Accept: application/json"
```

**Response (`200 OK`):**

```json
{
  "data": [
    {
      "id": 1,
      "customer": "محمد الحميري",
      "currency": "YER",
      "subtotal": 31000,
      "tax": 4650,
      "total": 35650,
      "items": [ ... ],
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1
  }
}


## Running the Full Test Suite

php artisan test

All 16 tests cover:
- ✅ 3 valid invoice creation scenarios (Arabic names, multi-item, single-item)
- ✅ Client-sent `total` is ignored and server computes the correct value
- ✅ Rejects negative qty, missing price, empty items, missing customer, missing item name, zero qty, negative price, missing items field
- ✅ `GET /api/invoices/{id}` returns `200` with data or `404`
- ✅ `GET /api/invoices` pagination and Arabic customer name search
