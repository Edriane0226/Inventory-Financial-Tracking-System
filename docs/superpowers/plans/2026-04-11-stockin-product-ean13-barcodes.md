# Stock-In Product-Level EAN-13 Barcodes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor stock-in barcode handling to server-generated EAN-13 codes reused per product identity (`name + category + unit`) across all batches while preserving existing FIFO stock-out behavior.

**Architecture:** Add a dedicated barcode registry table and model, then centralize barcode rules in a new `BarcodeService` used by `StockInController`. Remove client-side barcode generation logic from the stock-in form so the UI only previews resolved server values. Keep existing batch and stock deduction behavior unchanged.

**Tech Stack:** PHP 8.2, CodeIgniter 4.7, MySQL migrations, PHPUnit 10 (CIUnitTestCase, DatabaseTestTrait, FeatureTestTrait), Bootstrap + existing JsBarcode rendering.

---

## File Structure and Responsibilities

- Create: `app/Database/Migrations/2026-04-11-040000_CreateProductBarcodesTable.php` — adds durable barcode registry with product identity and unique barcode constraints.
- Create: `app/Models/ProductBarcode.php` — model for product barcode registry rows.
- Create: `app/Services/BarcodeService.php` — normalization, checksum, EAN-13 generation, resolve-or-create logic.
- Modify: `app/Controllers/StockInController.php` — remove trusted barcode input and resolve barcode server-side inside current stock-in flow.
- Modify: `app/Views/Stock_in/index.php` — remove custom client barcode generator; keep read-only display and preview rendering only.
- Create: `tests/unit/Services/BarcodeServiceTest.php` — unit tests for normalization/checksum/EAN output.
- Create: `tests/database/ProductBarcodeRegistryTest.php` — database tests for product-level reuse and uniqueness.
- Create: `tests/feature/StockInBarcodeFeatureTest.php` — feature test for stock-in POST using server-generated barcode.
- Create: `tests/feature/StockOutBarcodeLookupRegressionTest.php` — regression coverage for barcode lookup and FIFO compatibility.

### Task 1: Build BarcodeService algorithm with unit tests

**Files:**
- Create: `tests/unit/Services/BarcodeServiceTest.php`
- Create: `app/Services/BarcodeService.php`

- [ ] **Step 1: Write the failing unit tests**

```php
<?php

namespace Tests\Unit\Services;

use App\Services\BarcodeService;
use CodeIgniter\Test\CIUnitTestCase;

final class BarcodeServiceTest extends CIUnitTestCase
{
    public function testNormalizeProductName(): void
    {
        $service = new BarcodeService();
        $this->assertSame('deluxe milk 1l', $service->normalizeProductName('  Deluxe   MILK 1L '));
    }

    public function testComputeCheckDigit(): void
    {
        $service = new BarcodeService();
        $this->assertSame('5', $service->computeCheckDigit('200000012345'));
    }

    public function testBuildEan13FromBody(): void
    {
        $service = new BarcodeService();
        $this->assertSame('2000000123455', $service->buildEan13('200000012345'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --filter BarcodeServiceTest`
Expected: FAIL with class or method not found (service not implemented yet).

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Services;

final class BarcodeService
{
    public function normalizeProductName(string $productName): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim(strtolower($productName)));
        return (string) $normalized;
    }

    public function computeCheckDigit(string $body12): string
    {
        $sum = 0;
        foreach (str_split($body12) as $index => $char) {
            $digit = (int) $char;
            $sum += (($index + 1) % 2 === 0) ? ($digit * 3) : $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    public function buildEan13(string $body12): string
    {
        return $body12 . $this->computeCheckDigit($body12);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --filter BarcodeServiceTest`
Expected: PASS (3 tests, 0 failures).

- [ ] **Step 5: Commit**

```bash
git add tests/unit/Services/BarcodeServiceTest.php app/Services/BarcodeService.php
git commit -m "test+feat: add BarcodeService EAN13 core methods" -m "Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
```

### Task 2: Add barcode registry persistence with product-level reuse

**Files:**
- Create: `app/Database/Migrations/2026-04-11-040000_CreateProductBarcodesTable.php`
- Create: `app/Models/ProductBarcode.php`
- Modify: `app/Services/BarcodeService.php`
- Create: `tests/database/ProductBarcodeRegistryTest.php`

- [ ] **Step 1: Write the failing database test**

```php
<?php

use App\Models\ProductBarcode;
use App\Services\BarcodeService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

final class ProductBarcodeRegistryTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    public function testResolveForProductReusesBarcodeAcrossBatches(): void
    {
        $service = new BarcodeService();

        $first = $service->resolveForProduct('Milk', 1, 1);
        $second = $service->resolveForProduct('Milk', 1, 1);

        $this->assertSame($first, $second);
    }

    public function testResolveForProductCreatesNewBarcodeForDifferentIdentity(): void
    {
        $service = new BarcodeService();

        $milk = $service->resolveForProduct('Milk', 1, 1);
        $bread = $service->resolveForProduct('Bread', 1, 1);

        $this->assertNotSame($milk, $bread);
    }

    public function testResolveForProductRemainsSingleRowUnderRapidRepeatedCalls(): void
    {
        $service = new BarcodeService();

        for ($i = 0; $i < 20; $i++) {
            $service->resolveForProduct('Milk', 1, 1);
        }

        $model = new ProductBarcode();
        $rows = $model->where('normalized_product_name', 'milk')
            ->where('category_id', 1)
            ->where('unit_type_id', 1)
            ->findAll();

        $this->assertCount(1, $rows);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --filter ProductBarcodeRegistryTest`
Expected: FAIL because migration/model/resolveForProduct do not exist yet.

- [ ] **Step 3: Implement migration, model, and resolve-or-create logic**

```php
<?php
// app/Database/Migrations/2026-04-11-040000_CreateProductBarcodesTable.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductBarcodesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'normalized_product_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unit_type_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'barcode' => ['type' => 'CHAR', 'constraint' => 13],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['normalized_product_name', 'category_id', 'unit_type_id'], 'uq_product_identity');
        $this->forge->addUniqueKey('barcode', 'uq_product_barcode');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_type_id', 'unit_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_barcodes');
    }
}
```

```php
<?php
// app/Models/ProductBarcode.php

namespace App\Models;

use CodeIgniter\Model;

class ProductBarcode extends Model
{
    protected $table = 'product_barcodes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['normalized_product_name', 'category_id', 'unit_type_id', 'barcode'];
    protected $useTimestamps = true;
}
```

```php
<?php
// app/Services/BarcodeService.php (add method)

use App\Models\ProductBarcode;

public function resolveForProduct(string $productName, int $categoryId, int $unitTypeId): string
{
    $model = new ProductBarcode();
    $normalized = $this->normalizeProductName($productName);

    $existing = $model->where('normalized_product_name', $normalized)
        ->where('category_id', $categoryId)
        ->where('unit_type_id', $unitTypeId)
        ->first();

    if ($existing) {
        return (string) $existing['barcode'];
    }

    $prefix = getenv('BARCODE_INTERNAL_PREFIX') ?: '2000000';
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $body12 = $prefix . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $barcode = $this->buildEan13($body12);

        $saved = $model->insert([
            'normalized_product_name' => $normalized,
            'category_id' => $categoryId,
            'unit_type_id' => $unitTypeId,
            'barcode' => $barcode,
        ]);

        if ($saved !== false) {
            return $barcode;
        }

        $existing = $model->where('normalized_product_name', $normalized)
            ->where('category_id', $categoryId)
            ->where('unit_type_id', $unitTypeId)
            ->first();

        if ($existing) {
            return (string) $existing['barcode'];
        }
    }

    throw new \RuntimeException('Could not allocate a unique EAN-13 barcode.');
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --filter ProductBarcodeRegistryTest`
Expected: PASS (3 tests, 0 failures).

- [ ] **Step 5: Commit**

```bash
git add app/Database/Migrations/2026-04-11-040000_CreateProductBarcodesTable.php app/Models/ProductBarcode.php app/Services/BarcodeService.php tests/database/ProductBarcodeRegistryTest.php
git commit -m "feat: add product barcode registry and resolve service flow" -m "Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
```

### Task 3: Wire server-generated barcode into StockInController

**Files:**
- Modify: `app/Controllers/StockInController.php`
- Create: `tests/feature/StockInBarcodeFeatureTest.php`

- [ ] **Step 1: Write failing feature test for stock-in barcode behavior**

```php
<?php

use App\Models\StockIn;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class StockInBarcodeFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testPostStockInGeneratesServerBarcode(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('stockin', [
            'product_name' => 'Milk',
            'quantity' => 5,
            'category' => 'Dairy',
            'batch_number' => 'B-100',
            'expiration_date' => '2026-12-31',
            'capital' => '100.00',
            'stockin_date' => '2026-04-11',
            'sales_price' => '25.00',
            'unit_type' => 'Bottle',
            'barcode' => 'HACKED123',
        ]);

        $result->assertRedirectTo('/stockin');

        $inserted = (new StockIn())->orderBy('id', 'DESC')->first();
        $this->assertNotSame('HACKED123', (string) ($inserted['barcode'] ?? ''));
        $this->assertMatchesRegularExpression('/^\d{13}$/', (string) ($inserted['barcode'] ?? ''));
    }

    public function testLegacyBarcodeRowsRemainUnchanged(): void
    {
        $model = new StockIn();
        $legacyId = $model->insert([
            'product_name' => 'Legacy Product',
            'quantity' => 1,
            'stock_in_date' => '2026-01-01 00:00:00',
            'barcode' => 'LEGACYCODE123',
            'category_id' => 1,
            'unit_type_id' => 1,
            'recorded_by' => 1,
        ]);

        $legacy = $model->find($legacyId);
        $this->assertSame('LEGACYCODE123', (string) $legacy['barcode']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --filter StockInBarcodeFeatureTest`
Expected: FAIL until controller stops trusting posted barcode and uses service logic.

- [ ] **Step 3: Implement controller integration**

```php
<?php
// app/Controllers/StockInController.php (key edits)

use App\Services\BarcodeService;

// Validation: remove required barcode input rule
$validate = $this->validate([
    'product_name' => 'required',
    'quantity' => 'required|integer',
    'category' => 'required',
    'batch_number' => 'required',
    'expiration_date' => 'required|date',
    'capital' => 'required|decimal',
    'stockin_date' => 'required|date',
    'sales_price' => 'required|decimal',
    'unit_type' => 'required',
]);

$barcodeService = new BarcodeService();
$resolvedBarcode = $barcodeService->resolveForProduct(
    $productName,
    (int) $category['id'],
    (int) $unitType['id'],
);

$stockInInserted = $stockInModel->insert([
    'product_name' => $productName,
    'quantity' => $incomingQty,
    'category_id' => $category['id'],
    'unit_type_id' => $unitType['id'],
    'stock_in_date' => $stockInDate,
    'barcode' => $resolvedBarcode,
    'recorded_by' => session()->get('user_id'),
]);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --filter StockInBarcodeFeatureTest`
Expected: PASS with redirect, persisted server-generated barcode, and unchanged legacy row assertion.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/StockInController.php tests/feature/StockInBarcodeFeatureTest.php
git commit -m "feat(stockin): resolve barcode server-side with BarcodeService" -m "Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
```

### Task 4: Remove client barcode generator and keep preview rendering

**Files:**
- Modify: `app/Views/Stock_in/index.php`
- Create: `tests/unit/StockInViewBarcodeTest.php`

- [ ] **Step 1: Write failing view test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;

final class StockInViewBarcodeTest extends CIUnitTestCase
{
    public function testViewDoesNotContainClientBarcodeGenerator(): void
    {
        $html = view('Stock_in/index', [
            'stockIns' => [],
            'capitals' => [],
            'categories' => [],
            'productBatches' => [],
            'unitTypes' => [],
            'salesPrices' => [],
        ]);

        $this->assertStringNotContainsString('const generateBarcode = () => {', $html);
        $this->assertStringContainsString('id="barcode"', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --filter StockInViewBarcodeTest`
Expected: FAIL because `generateBarcode` script still exists.

- [ ] **Step 3: Implement view changes**

```php
<!-- app/Views/Stock_in/index.php -->
<input type="text" class="form-control form-control-lg font-monospace"
       id="barcode" name="barcode"
       value="<?= esc(old('barcode')) ?>"
       readonly>
<small class="text-muted d-block mt-2">
    <i class="bi bi-info-circle"></i> Generated on save using server-side EAN-13
</small>
```

```js
// Keep only renderBarcodeSvg() + renderTableBarcodes(); remove generateBarcode() and its event listeners.
window.addEventListener('load', () => {
    const barcodeField = document.getElementById('barcode');
    if (barcodeField && barcodeField.value.trim() !== '') {
        renderBarcodeSvg('#barcodePreview', barcodeField.value.trim(), { width: 1.4, height: 48 });
    }
    renderTableBarcodes();
});
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --filter StockInViewBarcodeTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Views/Stock_in/index.php tests/unit/StockInViewBarcodeTest.php
git commit -m "refactor(stockin-view): remove client barcode generator logic" -m "Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
```

### Task 5: Add stock-out regression coverage for shared product barcode

**Files:**
- Create: `tests/feature/StockOutBarcodeLookupRegressionTest.php`
- Modify: `app/Controllers/StockOutController.php`

- [ ] **Step 1: Write failing regression test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class StockOutBarcodeLookupRegressionTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private function seedSharedBarcodeData(string $barcode): void
    {
        $db = \Config\Database::connect();

        $db->table('categories')->insert(['id' => 1, 'category_name' => 'Dairy']);
        $db->table('unit_types')->insert(['id' => 1, 'unit_type_name' => 'Bottle']);
        $db->table('users')->insert([
            'id' => 1,
            'role_id' => 1,
            'first_name' => 'Owner',
            'last_name' => 'User',
            'username' => 'owner',
            'email' => 'owner@example.com',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
        ]);

        $db->table('stock_in')->insert([
            'id' => 10,
            'product_name' => 'Milk',
            'quantity' => 3,
            'stock_in_date' => '2026-04-01 08:00:00',
            'barcode' => $barcode,
            'category_id' => 1,
            'unit_type_id' => 1,
            'recorded_by' => 1,
        ]);
        $db->table('stock_in')->insert([
            'id' => 11,
            'product_name' => 'Milk',
            'quantity' => 5,
            'stock_in_date' => '2026-04-02 08:00:00',
            'barcode' => $barcode,
            'category_id' => 1,
            'unit_type_id' => 1,
            'recorded_by' => 1,
        ]);

        $db->table('product_batch')->insert([
            'id' => 100,
            'batch_number' => 'BATCH-OLD',
            'expiration_date' => '2026-10-01 00:00:00',
            'stock_in_id' => 10,
        ]);
        $db->table('product_batch')->insert([
            'id' => 101,
            'batch_number' => 'BATCH-NEW',
            'expiration_date' => '2026-11-01 00:00:00',
            'stock_in_id' => 11,
        ]);

        $db->table('products')->insert(['id' => 90, 'stock_in_id' => 10]);
        $db->table('products')->insert(['id' => 91, 'stock_in_id' => 11]);
        $db->table('sales_price')->insert([
            'sale_price' => '25.00',
            'effective_date' => '2026-04-01 08:00:00',
            'product_id' => 90,
        ]);
        $db->table('sales_price')->insert([
            'sale_price' => '25.00',
            'effective_date' => '2026-04-02 08:00:00',
            'product_id' => 91,
        ]);
    }

    public function testBarcodeLookupWorksWithSharedProductBarcodeAcrossBatches(): void
    {
        $barcode = '2000000123455';
        $this->seedSharedBarcodeData($barcode);

        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('stock-out/barcode/' . $barcode);

        $result->assertStatus(200);
        $result->assertJSONFragment(['ok' => true]);
        $result->assertJSONFragment(['next_batch_number' => 'BATCH-OLD']);
        $result->assertJSONFragment(['total_available_qty' => 8]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --filter StockOutBarcodeLookupRegressionTest`
Expected: FAIL if lookup flow does not handle shared barcodes across batches.

- [ ] **Step 3: Ensure lookup query stays FIFO-safe with shared barcodes**

```php
<?php
// app/Controllers/StockOutController.php (keep this query shape)
$fifoBatch = $db->table('stock_in si')
    ->select('si.id as stock_in_id, si.product_name, si.quantity, si.barcode, si.unit_type_id, si.category_id, pb.id as product_batch_id, pb.batch_number, pb.expiration_date, ut.unit_type_name, c.category_name')
    ->join('product_batch pb', 'pb.stock_in_id = si.id', 'inner')
    ->join('unit_types ut', 'ut.id = si.unit_type_id', 'left')
    ->join('categories c', 'c.id = si.category_id', 'left')
    ->where('si.product_name', $scannedBatch['product_name'])
    ->where('si.quantity >', 0)
    ->orderBy('si.stock_in_date', 'ASC')
    ->orderBy('si.id', 'ASC')
    ->orderBy('pb.id', 'ASC')
    ->get()
    ->getRowArray();
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --filter StockOutBarcodeLookupRegressionTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/feature/StockOutBarcodeLookupRegressionTest.php
git commit -m "test(regression): keep stock-out lookup compatible with shared product barcode" -m "Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
```

### Task 6: Full verification and rollout safety checks

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Run targeted barcode-related tests**

```bash
composer test -- --filter BarcodeServiceTest
composer test -- --filter ProductBarcodeRegistryTest
composer test -- --filter StockInBarcodeFeatureTest
composer test -- --filter StockInViewBarcodeTest
composer test -- --filter StockOutBarcodeLookupRegressionTest
```

- [ ] **Step 2: Run full test suite**

Run: `composer test`
Expected: PASS with zero failures.

- [ ] **Step 3: Confirm migration safety on local DB**

```bash
php spark migrate
php spark migrate:status
```

Expected: `CreateProductBarcodesTable` applied and listed as migrated.

- [ ] **Step 4: Update README with barcode behavior**

```md
## Stock-In Barcode Rules

Stock-In now uses server-generated EAN-13 barcodes. A barcode is reused for the same product identity (`product_name + category + unit_type`) across all batches, while batch number and expiry stay separate for FIFO and expiration tracking.
```

- [ ] **Step 5: Commit**

```bash
git add README.md
git commit -m "docs: document server-generated stock-in EAN13 barcode rules" -m "Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>"
```

