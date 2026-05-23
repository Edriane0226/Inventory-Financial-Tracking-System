# Bills and Product Expenses Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Bills tracking plus automatic Product Expense tracking from Stock-In capital, with tabbed Financial Analytics expenses UI and updated totals.

**Architecture:** Introduce two explicit data sources: `bills` (manual, full CRUD, paid/unpaid) and `product_expenses` (auto-generated, read-only). Hook Product Expense creation into `StockInController` transaction flow. Update Financial Analytics controller/views so `/financial/expenses` has Bills/Product Expense tabs and dashboard totals compute `paid bills + product expenses`.

**Tech Stack:** PHP 8.2+, CodeIgniter 4.7, Query Builder, Bootstrap 5 views, PHPUnit 10 with Feature/Unit tests.

---

## File Structure and Responsibilities

- Create: `app/Database/Migrations/2026-04-11-060000_CreateBillsTable.php` — bills schema with paid/unpaid status.
- Create: `app/Database/Migrations/2026-04-11-060100_CreateProductExpensesTable.php` — read-only product expense ledger schema.
- Create: `app/Models/Bill.php` — bills CRUD model.
- Create: `app/Models/ProductExpense.php` — product expense read model.
- Modify: `app/Controllers/StockInController.php` — auto-write `product_expenses` row from incoming capital.
- Modify: `app/Controllers/FinancialAnalyticsController.php` — bills CRUD + tab datasets + totals update.
- Modify: `app/Config/Routes.php` — bills CRUD routes under `/financial/bills/*`.
- Modify: `app/Views/financial/expenses.php` — tabs and Bills/Product Expense sections.
- Modify: `app/Views/financial/dashboard.php` — totals from bills + product expenses.
- Modify: `app/Views/Reusables/menu.php` — keep one Financial Analytics link only.
- Create: `tests/feature/BillsCrudFeatureTest.php` — Bills CRUD and validation tests.
- Create: `tests/feature/ProductExpenseAutoInsertTest.php` — Product Expense auto-insert on Stock-In.
- Create: `tests/feature/FinancialTotalsSourceRulesTest.php` — totals include paid bills and product expenses only.
- Create: `tests/unit/FinancialExpensesTabsViewTest.php` — verifies tab labels/sections in expenses view.

### Task 1: Add bills and product_expenses schema + models

**Files:**
- Create: `app/Database/Migrations/2026-04-11-060000_CreateBillsTable.php`
- Create: `app/Database/Migrations/2026-04-11-060100_CreateProductExpensesTable.php`
- Create: `app/Models/Bill.php`
- Create: `app/Models/ProductExpense.php`
- Create: `tests/unit/FinancialSchemaArtifactsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialSchemaArtifactsTest extends CIUnitTestCase
{
    public function testBillsAndProductExpensesArtifactsExist(): void
    {
        $this->assertFileExists(APPPATH . 'Database/Migrations/2026-04-11-060000_CreateBillsTable.php');
        $this->assertFileExists(APPPATH . 'Database/Migrations/2026-04-11-060100_CreateProductExpensesTable.php');
        $this->assertTrue(class_exists(\App\Models\Bill::class));
        $this->assertTrue(class_exists(\App\Models\ProductExpense::class));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialSchemaArtifactsTest`
Expected: FAIL with missing files/classes.

- [ ] **Step 3: Write minimal implementation**

```php
<?php
// app/Database/Migrations/2026-04-11-060000_CreateBillsTable.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'bill_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'bill_date' => ['type' => 'DATE'],
            'due_date' => ['type' => 'DATE'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'unpaid'],
            'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'recorded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('recorded_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bills');
    }

    public function down()
    {
        $this->forge->dropTable('bills');
    }
}
```

```php
<?php
// app/Database/Migrations/2026-04-11-060100_CreateProductExpensesTable.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductExpensesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'stock_in_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'expense_date' => ['type' => 'DATETIME'],
            'source_label' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'stock-in-capital'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('stock_in_id', 'stock_in', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_expenses');
    }

    public function down()
    {
        $this->forge->dropTable('product_expenses');
    }
}
```

```php
<?php
// app/Models/Bill.php

namespace App\Models;

use CodeIgniter\Model;

class Bill extends Model
{
    protected $table = 'bills';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['bill_name', 'amount', 'bill_date', 'due_date', 'status', 'notes', 'recorded_by'];
    protected $useTimestamps = true;
}
```

```php
<?php
// app/Models/ProductExpense.php

namespace App\Models;

use CodeIgniter\Model;

class ProductExpense extends Model
{
    protected $table = 'product_expenses';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['stock_in_id', 'amount', 'expense_date', 'source_label', 'created_at'];
    protected $useTimestamps = false;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialSchemaArtifactsTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Database/Migrations/2026-04-11-060000_CreateBillsTable.php app/Database/Migrations/2026-04-11-060100_CreateProductExpensesTable.php app/Models/Bill.php app/Models/ProductExpense.php tests/unit/FinancialSchemaArtifactsTest.php
git commit -m "feat(financial): add bills and product expense schema/models"
```

### Task 2: Auto-insert Product Expense from Stock-In capital

**Files:**
- Modify: `app/Controllers/StockInController.php`
- Create: `tests/feature/ProductExpenseAutoInsertTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class ProductExpenseAutoInsertTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testStockInCreatesProductExpenseRow(): void
    {
        $response = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('stockin', [
            'product_name' => 'Milk',
            'quantity' => 5,
            'category' => 'Dairy',
            'batch_number' => 'BILL-1',
            'expiration_date' => '2026-12-31',
            'capital' => '300.00',
            'stockin_date' => '2026-04-11',
            'sales_price' => '75.00',
            'unit_type' => 'Bottle',
        ]);

        $response->assertRedirectTo('/stockin');

        $db = db_connect();
        $row = $db->table('product_expenses')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('300.00', number_format((float) ($row['amount'] ?? 0), 2, '.', ''));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter ProductExpenseAutoInsertTest`
Expected: FAIL because Stock-In does not yet insert `product_expenses`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php
// app/Controllers/StockInController.php (imports)
use App\Models\ProductExpense;
```

```php
<?php
// app/Controllers/StockInController.php (inside POST logic, after $stockInID is known)
$productExpenseModel = new ProductExpense();
$productExpenseInserted = $productExpenseModel->insert([
    'stock_in_id' => $stockInID,
    'amount' => $incomingCapital,
    'expense_date' => $stockInDate,
    'source_label' => 'stock-in-capital',
    'created_at' => date('Y-m-d H:i:s'),
]);

if ($productExpenseInserted === false) {
    $db->transRollback();
    $expenseErrors = implode(' ', $productExpenseModel->errors());
    return redirect()->back()->withInput()->with('error', 'Could not save product expense row. ' . ($expenseErrors ?: 'Please check database schema.'));
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter ProductExpenseAutoInsertTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/StockInController.php tests/feature/ProductExpenseAutoInsertTest.php
git commit -m "feat(stockin): auto-record product expense from incoming capital"
```

### Task 3: Add Bills CRUD routes and controller actions

**Files:**
- Modify: `app/Config/Routes.php`
- Modify: `app/Controllers/FinancialAnalyticsController.php`
- Create: `tests/feature/BillsCrudFeatureTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class BillsCrudFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testOwnerCanCreateBill(): void
    {
        $response = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('financial/bills/create', [
            'bill_name' => 'Electric Bill',
            'amount' => '1200.50',
            'bill_date' => '2026-04-11',
            'due_date' => '2026-04-20',
            'status' => 'unpaid',
            'notes' => 'April service',
        ]);

        $response->assertRedirectTo('/financial/expenses');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter BillsCrudFeatureTest`
Expected: FAIL because bills route/action does not exist.

- [ ] **Step 3: Write minimal implementation**

```php
<?php
// app/Config/Routes.php
$routes->post('financial/bills/create', 'FinancialAnalyticsController::createBill');
$routes->post('financial/bills/update/(:num)', 'FinancialAnalyticsController::updateBill/$1');
$routes->post('financial/bills/delete/(:num)', 'FinancialAnalyticsController::deleteBill/$1');
```

```php
<?php
// app/Controllers/FinancialAnalyticsController.php (imports)
use App\Models\Bill;
use App\Models\ProductExpense;
```

```php
<?php
// app/Controllers/FinancialAnalyticsController.php (new actions)
public function createBill()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $isValid = $this->validate([
        'bill_name' => 'required|max_length[150]',
        'amount' => 'required|decimal|greater_than[0]',
        'bill_date' => 'required|valid_date',
        'due_date' => 'required|valid_date',
        'status' => 'required|in_list[paid,unpaid]',
        'notes' => 'permit_empty|max_length[255]',
    ]);
    if (!$isValid) {
        return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
    }

    $inserted = (new Bill())->insert([
        'bill_name' => trim((string) $this->request->getPost('bill_name')),
        'amount' => (float) $this->request->getPost('amount'),
        'bill_date' => (string) $this->request->getPost('bill_date'),
        'due_date' => (string) $this->request->getPost('due_date'),
        'status' => (string) $this->request->getPost('status'),
        'notes' => trim((string) $this->request->getPost('notes')),
        'recorded_by' => (int) session()->get('user_id'),
    ]);

    if ($inserted === false) {
        return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not save bill.');
    }

    return redirect()->to('/financial/expenses')->with('success', 'Bill saved successfully.');
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter BillsCrudFeatureTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Config/Routes.php app/Controllers/FinancialAnalyticsController.php tests/feature/BillsCrudFeatureTest.php
git commit -m "feat(financial): add bills CRUD routes and create action"
```

### Task 4: Convert expenses page to Bills/Product Expenses tabs

**Files:**
- Modify: `app/Controllers/FinancialAnalyticsController.php`
- Modify: `app/Views/financial/expenses.php`
- Create: `tests/unit/FinancialExpensesTabsViewTest.php`

- [ ] **Step 1: Write the failing view test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialExpensesTabsViewTest extends CIUnitTestCase
{
    public function testExpensesViewContainsBillsAndProductExpenseTabs(): void
    {
        $html = view('financial/expenses', [
            'bills' => [],
            'productExpenses' => [],
            'filters' => [],
            'billFilters' => [],
        ]);

        $this->assertStringContainsString('Bills', $html);
        $this->assertStringContainsString('Product Expenses', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialExpensesTabsViewTest`
Expected: FAIL because current view has no tabbed Bills/Product Expenses sections.

- [ ] **Step 3: Write minimal implementation**

```php
<?php
// app/Controllers/FinancialAnalyticsController.php (inside expenses())
$billFilters = [
    'status' => trim((string) ($this->request->getGet('status') ?? '')),
    'from_date' => trim((string) ($this->request->getGet('from_date') ?? '')),
    'to_date' => trim((string) ($this->request->getGet('to_date') ?? '')),
    'keyword' => trim((string) ($this->request->getGet('keyword') ?? '')),
];

$billQuery = (new Bill())->orderBy('bill_date', 'DESC');
if ($billFilters['status'] !== '') {
    $billQuery->where('status', $billFilters['status']);
}
if ($billFilters['from_date'] !== '') {
    $billQuery->where('bill_date >=', $billFilters['from_date']);
}
if ($billFilters['to_date'] !== '') {
    $billQuery->where('bill_date <=', $billFilters['to_date']);
}
if ($billFilters['keyword'] !== '') {
    $billQuery->groupStart()->like('bill_name', $billFilters['keyword'])->orLike('notes', $billFilters['keyword'])->groupEnd();
}

$bills = $billQuery->findAll();
$productExpenses = (new ProductExpense())->orderBy('expense_date', 'DESC')->findAll();

return view('Reusables/menu') . view('financial/expenses', [
    'bills' => $bills,
    'productExpenses' => $productExpenses,
    'billFilters' => $billFilters,
]);
```

```php
<?php
$bills = $bills ?? [];
$productExpenses = $productExpenses ?? [];
$billFilters = $billFilters ?? ['status' => '', 'from_date' => '', 'to_date' => '', 'keyword' => ''];
?>

<ul class="nav nav-tabs mb-3" id="expenseTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#billsTab" type="button">Bills</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#productExpensesTab" type="button">Product Expenses</button>
  </li>
</ul>
<div class="tab-content">
  <div class="tab-pane fade show active" id="billsTab">
    <form method="post" action="<?= base_url('financial/bills/create') ?>" class="row g-2 mb-3">
      <?= csrf_field() ?>
      <div class="col-md-3"><input type="text" class="form-control" name="bill_name" placeholder="Bill name" required></div>
      <div class="col-md-2"><input type="number" class="form-control" name="amount" step="0.01" min="0.01" required></div>
      <div class="col-md-2"><input type="date" class="form-control" name="bill_date" required></div>
      <div class="col-md-2"><input type="date" class="form-control" name="due_date" required></div>
      <div class="col-md-2">
        <select class="form-select" name="status" required>
          <option value="unpaid">Unpaid</option>
          <option value="paid">Paid</option>
        </select>
      </div>
      <div class="col-md-1"><button type="submit" class="btn btn-primary w-100">Add</button></div>
      <div class="col-12"><input type="text" class="form-control" name="notes" placeholder="Notes"></div>
    </form>

    <table class="table table-sm align-middle">
      <thead><tr><th>Bill</th><th>Date</th><th>Due</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
      <tbody>
        <?php if (empty($bills)): ?>
          <tr><td colspan="5" class="text-center text-muted">No bills found.</td></tr>
        <?php else: ?>
          <?php foreach ($bills as $bill): ?>
            <tr>
              <td><?= esc($bill['bill_name']) ?></td>
              <td><?= esc($bill['bill_date']) ?></td>
              <td><?= esc($bill['due_date']) ?></td>
              <td><?= esc(ucfirst($bill['status'])) ?></td>
              <td class="text-end">₱<?= esc(number_format((float) $bill['amount'], 2)) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="tab-pane fade" id="productExpensesTab">
    <table class="table table-sm align-middle">
      <thead><tr><th>Date</th><th>Source</th><th>Stock-In ID</th><th class="text-end">Amount</th></tr></thead>
      <tbody>
        <?php if (empty($productExpenses)): ?>
          <tr><td colspan="4" class="text-center text-muted">No product expenses found.</td></tr>
        <?php else: ?>
          <?php foreach ($productExpenses as $row): ?>
            <tr>
              <td><?= esc(date('Y-m-d', strtotime((string) $row['expense_date']))) ?></td>
              <td><?= esc($row['source_label'] ?? 'stock-in-capital') ?></td>
              <td><?= esc((string) ($row['stock_in_id'] ?? '')) ?></td>
              <td class="text-end">₱<?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialExpensesTabsViewTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/FinancialAnalyticsController.php app/Views/financial/expenses.php tests/unit/FinancialExpensesTabsViewTest.php
git commit -m "feat(financial-ui): add bills and product expenses tabbed page"
```

### Task 5: Update totals logic and menu rule validation

**Files:**
- Modify: `app/Controllers/FinancialAnalyticsController.php`
- Modify: `app/Views/financial/dashboard.php`
- Modify: `app/Views/Reusables/menu.php`
- Create: `tests/feature/FinancialTotalsSourceRulesTest.php`
- Modify: `README.md`

- [ ] **Step 1: Write the failing totals rule test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialTotalsSourceRulesTest extends CIUnitTestCase
{
    public function testExpenseFormulaDocumentsPaidBillsPlusProductExpenses(): void
    {
        $controllerFile = file_get_contents(APPPATH . 'Controllers/FinancialAnalyticsController.php');
        $this->assertNotFalse($controllerFile);
        $this->assertStringContainsString('sumPaidBills', (string) $controllerFile);
        $this->assertStringContainsString('sumProductExpenses', (string) $controllerFile);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialTotalsSourceRulesTest`
Expected: FAIL because helper methods do not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
<?php
// app/Controllers/FinancialAnalyticsController.php (replace sumExpenses usage)
$expenses = $this->sumPaidBills($from, $to) + $this->sumProductExpenses($from, $to);
```

```php
<?php
// app/Controllers/FinancialAnalyticsController.php (new helpers)
private function sumPaidBills(string $from, string $to): float
{
    $db = db_connect();
    if (!$db->tableExists('bills')) {
        return 0.0;
    }

    $row = $db->table('bills')
        ->select('COALESCE(SUM(amount), 0) AS total_paid_bills')
        ->where('status', 'paid')
        ->where('bill_date >=', substr($from, 0, 10))
        ->where('bill_date <=', substr($to, 0, 10))
        ->get()->getRowArray();

    return (float) ($row['total_paid_bills'] ?? 0);
}

private function sumProductExpenses(string $from, string $to): float
{
    $db = db_connect();
    if (!$db->tableExists('product_expenses')) {
        return 0.0;
    }

    $row = $db->table('product_expenses')
        ->select('COALESCE(SUM(amount), 0) AS total_product_expenses')
        ->where('expense_date >=', $from)
        ->where('expense_date <=', $to)
        ->get()->getRowArray();

    return (float) ($row['total_product_expenses'] ?? 0);
}
```

```markdown
## Financial Analytics

Financial Analytics uses two expense sources: paid bills and automatic product expenses from Stock-In capital entries. Product expenses are read-only and generated per new Stock-In transaction.
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialTotalsSourceRulesTest`
Expected: PASS.

- [ ] **Step 5: Run full test suite**

Run: `composer test -- --no-coverage`
Expected: PASS with all tests green.

- [ ] **Step 6: Commit**

```bash
git add app/Controllers/FinancialAnalyticsController.php app/Views/financial/dashboard.php app/Views/Reusables/menu.php tests/feature/FinancialTotalsSourceRulesTest.php README.md
git commit -m "feat(financial): apply paid-bills-plus-product-expenses totals rule"
```

