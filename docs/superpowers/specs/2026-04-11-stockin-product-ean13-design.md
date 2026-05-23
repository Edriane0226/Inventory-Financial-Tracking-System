# Stock-In Product-Level EAN-13 Barcode Design

## Problem and Goal
Current stock-in barcode generation is client-side and non-standard (CODE128-like custom format). The goal is to move barcode generation to the server, enforce valid **EAN-13** structure (with checksum), and make barcode reuse happen at the **product level** so all batches of the same product share one barcode.

Approved product identity for barcode reuse:
- `normalized_product_name + category_id + unit_type_id`

This means:
- Same product identity, different batch/expiry: **reuse same barcode**
- Different product identity: **generate new barcode**

## Scope
In scope:
1. Refactor Stock In barcode generation to server-side EAN-13.
2. Introduce reusable product-level barcode registry.
3. Keep existing stock/batch FIFO behavior intact.
4. Keep existing records unchanged.

Out of scope:
1. Backfilling legacy barcode values.
2. Financial analytics controller/views (separate sub-project after this spec).

## Evaluated Approaches
1. **Chosen: dedicated barcode registry table + service**
   - Strong uniqueness guarantees via DB constraints.
   - Clean reuse rules independent from batch records.
   - Reliable under concurrent writes.
2. Direct generation in `stock_in` only
   - Smaller schema change but weak long-term reuse/uniqueness enforcement.
3. Client-first generation + server validation
   - Faster UI feedback but lower integrity and easier to bypass.

## Architecture
Add a `BarcodeService` used by `StockInController` during POST processing:
1. Normalize product identity (`name/category/unit`).
2. Resolve existing barcode from registry.
3. If missing, generate a new EAN-13 and insert registry row.
4. Persist resolved barcode into `stock_in.barcode`.

UI remains read-only for barcode display/preview and does not act as source of truth.

## Components
1. **New table:** `product_barcodes`
   - `id` (PK)
   - `normalized_product_name` (VARCHAR)
   - `category_id` (INT, FK categories.id)
   - `unit_type_id` (INT, FK unit_types.id)
   - `barcode` (CHAR(13), unique)
   - timestamps (`created_at`, `updated_at`)
   - unique key: (`normalized_product_name`, `category_id`, `unit_type_id`)
2. **New model:** `ProductBarcode` for registry CRUD.
3. **New service:** `BarcodeService`
   - Product identity normalization.
   - EAN-13 generation and checksum.
   - Resolve-or-create logic with retry on race collisions.
4. **Controller update:** `StockInController`
   - Remove barcode as trusted user input in validation/write path.
   - Use service result inside transaction.
5. **View update:** `Stock_in/index.php`
   - Remove custom JS generation logic.
   - Keep barcode field read-only and preview from server value/old input.

## Data Flow
1. User submits stock-in form (without authoritative barcode input).
2. Controller validates business fields.
3. Controller calls `BarcodeService->resolveForProduct(...)`.
4. Service:
   - checks existing registry by normalized product identity;
   - if found, returns existing barcode;
   - if not found, generates EAN-13 and inserts registry row.
5. Controller continues existing stock merge/insert + batch insert behavior.
6. Controller stores resolved barcode in `stock_in.barcode`.
7. Stock-out/cashier barcode lookup continues working; FIFO still chooses oldest batch internally.

## EAN-13 Generation Rules
1. Use internal 7-digit prefix (non-GS1-owned but format-valid), configurable with default `2000000`.
2. Build 12-digit body as:
   - `prefix(7)` + `item_reference(5)`
3. Compute check digit using standard EAN-13 checksum algorithm.
4. Final barcode is 13 digits: `body12 + check_digit`.
5. Enforce uniqueness at DB level (`barcode` unique).

## Error Handling
1. If registry insert conflicts on product unique key, re-read and reuse existing row.
2. If barcode unique collision occurs, retry generation up to a bounded attempt count.
3. If retries fail, abort with explicit error and rollback.
4. No silent fallback to client-generated values.
5. Existing legacy barcode rows remain untouched.

## Testing Strategy
1. Unit tests for checksum generation/validation.
2. Unit tests for normalization rules of product identity.
3. Integration test: same product identity across different batches reuses one barcode.
4. Integration test: different product identity creates new barcode.
5. Concurrency test: parallel resolve calls do not create duplicate registry rows/barcodes.
6. Controller test: stock-in persists resolved server barcode and ignores user-provided barcode.
7. Regression test: stock-out lookup still resolves and FIFO behavior remains unchanged.

## Notes for Next Sub-Project
After this barcode sub-project is implemented, create a separate design for the financial analytics module (revenue tracking, expenses, net income, summaries, reports, margin analysis, dashboard, and expense history/filtering).
