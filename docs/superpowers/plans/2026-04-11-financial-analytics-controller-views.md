# Financial Analytics Controller and Views Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an Owner-only Financial Analytics module with automated revenue tracking, expense CRUD/categorization, net income and margin metrics, daily/weekly/monthly reporting, and monthly CSV export.

**Architecture:** Add normalized `expense_categories` and `expenses` tables plus a receipts timestamp migration, then build a new `FinancialAnalyticsController` that aggregates receipts and expenses into dashboard/report data. Provide two dedicated views (`financial/dashboard`, `financial/expenses`) and wire routes/menu/dashboard links for Owner users only.

**Tech Stack:** PHP 8.2+, CodeIgniter 4.7, Query Builder, Bootstrap 5 UI, PHPUnit 10 (Feature and Unit/Database tests).

---

## File Structure and Responsibilities

- Create: `app/Controllers/FinancialAnalyticsController.php` — Owner-only financial endpoints, aggregates, CSV export, CRUD handlers.
- Create: `app/Models/ExpenseCategory.php` — expense category persistence and lookups.
- Create: `app/Models/Expense.php` — expense record persistence and filtered retrieval.
- Create: `app/Database/Migrations/2026-04-11-050000_AddCreatedAtToReceiptsTable.php` — add/backfill receipts timestamp.
- Create: `app/Database/Migrations/2026-04-11-050100_CreateExpenseCategoriesTable.php` — category table schema.
- Create: `app/Database/Migrations/2026-04-11-050200_CreateExpensesTable.php` — expenses schema.
- Modify: `app/Models/Receipt.php` — allow `created_at` assignment.
- Modify: `app/Controllers/StockOutController.php` — include `created_at` when creating receipts.
- Modify: `app/Config/Routes.php` — add `/financial` routes.
- Modify: `app/Views/Reusables/menu.php` — add Owner-only Financial Analytics nav link.
- Modify: `app/Views/auth/dashboard.php` — link Financial Tracking cards to `/financial`.
- Create: `app/Views/financial/dashboard.php` — KPI cards + daily/weekly/monthly report blocks.
- Create: `app/Views/financial/expenses.php` — expense/category forms + filterable history table.
- Create: `tests/feature/FinancialAnalyticsAccessTest.php` — role access checks.
- Create: `tests/feature/FinancialAnalyticsDashboardTest.php` — aggregate/net/margin/CSV tests.
- Create: `tests/feature/FinancialAnalyticsExpenseCrudTest.php` — expense/category CRUD and filtering tests.

### Task 1: Add financial route guards and access tests

**Files:**
- Create: `tests/feature/FinancialAnalyticsAccessTest.php`
- Modify: `app/Config/Routes.php`
- Create: `app/Controllers/FinancialAnalyticsController.php`

- [ ] **Step 1: Write the failing access test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FinancialAnalyticsAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get('financial')->assertRedirectTo('/login');
    }

    public function testEmployeeIsRedirectedToDashboard(): void
    {
        $this->withSession([
            'logged_in' => true,
            'role' => 'Employee',
            'user_id' => 1,
        ])->get('financial')->assertRedirectTo('/dashboard');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsAccessTest`
Expected: FAIL because financial route/controller does not exist.

- [ ] **Step 3: Add route and minimal controller guard**

```php
// app/Config/Routes.php
$routes->get('financial', 'FinancialAnalyticsController::index');
```

```php
<?php
// app/Controllers/FinancialAnalyticsController.php

namespace App\Controllers;

class FinancialAnalyticsController extends BaseController
{
    public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        if ((string) session()->get('role') !== 'Owner') {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Financial Analytics.');
        }

        return 'ok';
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsAccessTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/feature/FinancialAnalyticsAccessTest.php app/Config/Routes.php app/Controllers/FinancialAnalyticsController.php
git commit -m "feat(financial): add owner-only financial route guard"
```

### Task 2: Build schema and models for receipts timestamp, categories, and expenses

**Files:**
- Create: `app/Database/Migrations/2026-04-11-050000_AddCreatedAtToReceiptsTable.php`
- Create: `app/Database/Migrations/2026-04-11-050100_CreateExpenseCategoriesTable.php`
- Create: `app/Database/Migrations/2026-04-11-050200_CreateExpensesTable.php`
- Modify: `app/Models/Receipt.php`
- Create: `app/Models/ExpenseCategory.php`
- Create: `app/Models/Expense.php`

- [ ] **Step 1: Write failing dashboard data test for missing expense tables**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialSchemaSmokeTest extends CIUnitTestCase
{
    public function testExpenseTablesExistAfterMigrations(): void
    {
        $db = db_connect();
        $this->assertTrue($db->tableExists('expense_categories'));
        $this->assertTrue($db->tableExists('expenses'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialSchemaSmokeTest`
Expected: FAIL with missing tables.

- [ ] **Step 3: Add migrations and models**

```php
<?php
// app/Database/Migrations/2026-04-11-050100_CreateExpenseCategoriesTable.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExpenseCategoriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name', 'uq_expense_category_name');
        $this->forge->createTable('expense_categories');
    }

    public function down()
    {
        $this->forge->dropTable('expense_categories');
    }
}
```

```php
<?php
// app/Database/Migrations/2026-04-11-050200_CreateExpensesTable.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExpensesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'expense_date' => ['type' => 'DATETIME'],
            'recorded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('category_id', 'expense_categories', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('recorded_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('expenses');
    }

    public function down()
    {
        $this->forge->dropTable('expenses');
    }
}
```

```php
<?php
// app/Database/Migrations/2026-04-11-050000_AddCreatedAtToReceiptsTable.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedAtToReceiptsTable extends Migration
{
    public function up()
    {
        $columnExists = $this->db->fieldExists('created_at', 'receipts');
        if (!$columnExists) {
            $this->forge->addColumn('receipts', [
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'total_amount',
                ],
            ]);
        }

        $this->db->table('receipts')
            ->set('created_at', date('Y-m-d H:i:s'))
            ->where('created_at IS NULL', null, false)
            ->update();
    }

    public function down()
    {
        if ($this->db->fieldExists('created_at', 'receipts')) {
            $this->forge->dropColumn('receipts', 'created_at');
        }
    }
}
```

```php
<?php
// app/Models/Expense.php
namespace App\Models;

use CodeIgniter\Model;

class Expense extends Model
{
    protected $table = 'expenses';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['category_id', 'amount', 'note', 'expense_date', 'recorded_by'];
    protected $useTimestamps = true;
}
```

```php
<?php
// app/Models/ExpenseCategory.php
namespace App\Models;

use CodeIgniter\Model;

class ExpenseCategory extends Model
{
    protected $table = 'expense_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'is_active'];
    protected $useTimestamps = true;
}
```

```php
<?php
// app/Models/Receipt.php
protected $allowedFields = ['receipt_number', 'total_amount', 'created_at'];
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php spark migrate` then `composer test -- --no-coverage --filter FinancialSchemaSmokeTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Database/Migrations/2026-04-11-050000_AddCreatedAtToReceiptsTable.php app/Database/Migrations/2026-04-11-050100_CreateExpenseCategoriesTable.php app/Database/Migrations/2026-04-11-050200_CreateExpensesTable.php app/Models/Receipt.php app/Models/ExpenseCategory.php app/Models/Expense.php
git commit -m "feat(financial): add receipts timestamp and expense schema"
```

### Task 3: Implement dashboard aggregates and CSV export

**Files:**
- Modify: `app/Controllers/FinancialAnalyticsController.php`
- Create: `app/Views/financial/dashboard.php`
- Create: `tests/feature/FinancialAnalyticsDashboardTest.php`

- [ ] **Step 1: Write failing dashboard aggregate and CSV tests**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FinancialAnalyticsDashboardTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testDashboardShowsRevenueExpenseNetIncomeAndMargin(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('financial');

        $result->assertStatus(200);
        $result->assertSee('Net Income');
        $result->assertSee('Profit Margin');
    }

    public function testMonthlyCsvExportReturnsCsv(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('financial/export-csv?year=2026&month=4');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsDashboardTest`
Expected: FAIL due missing route/action/view/export.

- [ ] **Step 3: Implement dashboard query methods and CSV endpoint**

```php
// app/Controllers/FinancialAnalyticsController.php (core additions)
public function index()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $windows = $this->dateWindows();
    $metrics = [
        'daily' => $this->buildMetrics($windows['daily_start'], $windows['daily_end']),
        'weekly' => $this->buildMetrics($windows['weekly_start'], $windows['weekly_end']),
        'monthly' => $this->buildMetrics($windows['monthly_start'], $windows['monthly_end']),
    ];

    return view('Reusables/menu')
        . view('financial/dashboard', ['metrics' => $metrics, 'windows' => $windows]);
}

private function requireOwner()
{
    if (!session()->get('logged_in')) {
        return redirect()->to('/login');
    }

    if ((string) session()->get('role') !== 'Owner') {
        return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Financial Analytics.');
    }

    return null;
}

private function dateWindows(): array
{
    $now = new \DateTimeImmutable('now');
    $dailyStart = $now->setTime(0, 0, 0);
    $dailyEnd = $now->setTime(23, 59, 59);
    $weekStart = $now->modify('monday this week')->setTime(0, 0, 0);
    $weekEnd = $weekStart->modify('+6 days')->setTime(23, 59, 59);
    $monthStart = $now->modify('first day of this month')->setTime(0, 0, 0);
    $monthEnd = $now->modify('last day of this month')->setTime(23, 59, 59);

    return [
        'daily_start' => $dailyStart->format('Y-m-d H:i:s'),
        'daily_end' => $dailyEnd->format('Y-m-d H:i:s'),
        'weekly_start' => $weekStart->format('Y-m-d H:i:s'),
        'weekly_end' => $weekEnd->format('Y-m-d H:i:s'),
        'monthly_start' => $monthStart->format('Y-m-d H:i:s'),
        'monthly_end' => $monthEnd->format('Y-m-d H:i:s'),
    ];
}

private function monthRange(int $year, int $month): array
{
    $start = (new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month)));
    $end = $start->modify('last day of this month')->setTime(23, 59, 59);

    return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
}

private function buildMetrics(string $from, string $to): array
{
    $db = db_connect();

    $revenueRow = $db->table('receipts')
        ->select('COALESCE(SUM(total_amount), 0) AS total_revenue')
        ->where('created_at >=', $from)
        ->where('created_at <=', $to)
        ->get()->getRowArray();

    $expenseRow = $db->table('expenses')
        ->select('COALESCE(SUM(amount), 0) AS total_expenses')
        ->where('expense_date >=', $from)
        ->where('expense_date <=', $to)
        ->get()->getRowArray();

    $revenue = (float) ($revenueRow['total_revenue'] ?? 0);
    $expenses = (float) ($expenseRow['total_expenses'] ?? 0);
    $net = $revenue - $expenses;
    $margin = $revenue > 0 ? round(($net / $revenue) * 100, 2) : 0.0;

    return [
        'revenue' => round($revenue, 2),
        'expenses' => round($expenses, 2),
        'net_income' => round($net, 2),
        'profit_margin' => $margin,
    ];
}

public function exportMonthlyCsv()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $year = (int) $this->request->getGet('year');
    $month = (int) $this->request->getGet('month');
    if ($year < 2000 || $month < 1 || $month > 12) {
        return redirect()->to('/financial')->with('error', 'Invalid export period.');
    }

    [$start, $end] = $this->monthRange($year, $month);
    $m = $this->buildMetrics($start, $end);

    $csv = fopen('php://temp', 'r+');
    fputcsv($csv, ['year', 'month', 'revenue', 'expenses', 'net_income', 'profit_margin_percent']);
    fputcsv($csv, [$year, $month, $m['revenue'], $m['expenses'], $m['net_income'], $m['profit_margin']]);
    rewind($csv);
    $content = stream_get_contents($csv) ?: '';
    fclose($csv);

    return $this->response
        ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->setHeader('Content-Disposition', 'attachment; filename=\"financial-statement-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.csv\"')
        ->setBody($content);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsDashboardTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/FinancialAnalyticsController.php app/Views/financial/dashboard.php tests/feature/FinancialAnalyticsDashboardTest.php
git commit -m "feat(financial): add dashboard metrics and monthly CSV export"
```

### Task 4: Implement expense and category CRUD with filtering

**Files:**
- Modify: `app/Controllers/FinancialAnalyticsController.php`
- Create: `app/Views/financial/expenses.php`
- Create: `tests/feature/FinancialAnalyticsExpenseCrudTest.php`

- [ ] **Step 1: Write failing CRUD/filter tests**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FinancialAnalyticsExpenseCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testOwnerCanCreateExpense(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('financial/expenses/create', [
            'category_id' => 1,
            'amount' => '150.75',
            'note' => 'Fuel',
            'expense_date' => '2026-04-11',
        ]);

        $result->assertRedirectTo('/financial/expenses');
    }

    public function testExpenseHistoryFiltersByCategoryAndKeyword(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('financial/expenses?category_id=1&keyword=fuel');

        $result->assertStatus(200);
        $result->assertSee('Expense History');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsExpenseCrudTest`
Expected: FAIL due missing expense routes/actions/views.

- [ ] **Step 3: Implement CRUD/filter actions and expenses view**

```php
// app/Controllers/FinancialAnalyticsController.php (essential actions)
public function expenses()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $filters = [
        'category_id' => (int) ($this->request->getGet('category_id') ?? 0),
        'keyword' => trim((string) ($this->request->getGet('keyword') ?? '')),
        'from_date' => (string) ($this->request->getGet('from_date') ?? ''),
        'to_date' => (string) ($this->request->getGet('to_date') ?? ''),
    ];

    $expenseModel = new \App\Models\Expense();
    $query = $expenseModel->select('expenses.*, expense_categories.name AS category_name')
        ->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left');

    if ($filters['category_id'] > 0) {
        $query->where('expenses.category_id', $filters['category_id']);
    }
    if ($filters['keyword'] !== '') {
        $query->like('expenses.note', $filters['keyword']);
    }
    if ($filters['from_date'] !== '') {
        $query->where('DATE(expenses.expense_date) >=', $filters['from_date']);
    }
    if ($filters['to_date'] !== '') {
        $query->where('DATE(expenses.expense_date) <=', $filters['to_date']);
    }

    $expenses = $query->orderBy('expenses.expense_date', 'DESC')->findAll();
    $categories = (new \App\Models\ExpenseCategory())->where('is_active', 1)->orderBy('name', 'ASC')->findAll();

    return view('Reusables/menu')
        . view('financial/expenses', compact('expenses', 'categories', 'filters'));
}

public function createExpense()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $isValid = $this->validate([
        'category_id' => 'required|integer|greater_than[0]',
        'amount' => 'required|decimal|greater_than[0]',
        'expense_date' => 'required|date',
        'note' => 'permit_empty|max_length[255]',
    ]);

    if (!$isValid) {
        return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
    }

    (new \App\Models\Expense())->insert([
        'category_id' => (int) $this->request->getPost('category_id'),
        'amount' => (float) $this->request->getPost('amount'),
        'note' => trim((string) $this->request->getPost('note')),
        'expense_date' => date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('expense_date'))),
        'recorded_by' => (int) session()->get('user_id'),
    ]);

    return redirect()->to('/financial/expenses')->with('success', 'Expense recorded successfully.');
}

public function updateExpense(int $id)
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $isValid = $this->validate([
        'category_id' => 'required|integer|greater_than[0]',
        'amount' => 'required|decimal|greater_than[0]',
        'expense_date' => 'required|date',
        'note' => 'permit_empty|max_length[255]',
    ]);

    if (!$isValid) {
        return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
    }

    (new \App\Models\Expense())->update($id, [
        'category_id' => (int) $this->request->getPost('category_id'),
        'amount' => (float) $this->request->getPost('amount'),
        'note' => trim((string) $this->request->getPost('note')),
        'expense_date' => date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('expense_date'))),
    ]);

    return redirect()->to('/financial/expenses')->with('success', 'Expense updated successfully.');
}

public function deleteExpense(int $id)
{
    (new \App\Models\Expense())->delete($id);
    return redirect()->to('/financial/expenses')->with('success', 'Expense deleted successfully.');
}

public function createCategory()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $isValid = $this->validate([
        'name' => 'required|max_length[120]|is_unique[expense_categories.name]',
    ]);

    if (!$isValid) {
        return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
    }

    (new \App\Models\ExpenseCategory())->insert([
        'name' => trim((string) $this->request->getPost('name')),
        'is_active' => 1,
    ]);

    return redirect()->to('/financial/expenses')->with('success', 'Category created successfully.');
}

public function updateCategory(int $id)
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $isValid = $this->validate([
        'name' => 'required|max_length[120]',
        'is_active' => 'required|in_list[0,1]',
    ]);

    if (!$isValid) {
        return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
    }

    (new \App\Models\ExpenseCategory())->update($id, [
        'name' => trim((string) $this->request->getPost('name')),
        'is_active' => (int) $this->request->getPost('is_active'),
    ]);

    return redirect()->to('/financial/expenses')->with('success', 'Category updated successfully.');
}

public function deleteCategory(int $id)
{
    $inUse = (new \App\Models\Expense())->where('category_id', $id)->countAllResults() > 0;
    if ($inUse) {
        return redirect()->to('/financial/expenses')->with('error', 'Category cannot be deleted because expenses are linked to it.');
    }

    (new \App\Models\ExpenseCategory())->delete($id);
    return redirect()->to('/financial/expenses')->with('success', 'Category deleted successfully.');
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsExpenseCrudTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/FinancialAnalyticsController.php app/Views/financial/expenses.php tests/feature/FinancialAnalyticsExpenseCrudTest.php
git commit -m "feat(financial): add expense/category CRUD and history filters"
```

### Task 5: Wire receipts timestamp in sales flow and navigation links

**Files:**
- Modify: `app/Controllers/StockOutController.php`
- Modify: `app/Models/Receipt.php`
- Modify: `app/Views/Reusables/menu.php`
- Modify: `app/Views/auth/dashboard.php`
- Modify: `app/Config/Routes.php`

- [ ] **Step 1: Write failing integration assertions for links and receipt timestamps**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FinancialNavigationIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testOwnerMenuContainsFinancialLink(): void
    {
        $result = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('stockin');

        $result->assertStatus(200);
        $result->assertSee('Financial Analytics');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialNavigationIntegrationTest`
Expected: FAIL before menu/dashboard link updates.

- [ ] **Step 3: Implement link and timestamp updates**

```php
// app/Controllers/StockOutController.php (receipt insert payload)
$receiptInserted = $receiptModel->insert([
    'receipt_number' => $receiptNumber,
    'total_amount' => 0,
    'created_at' => date('Y-m-d H:i:s'),
]);
```

```php
// app/Models/Receipt.php
protected $allowedFields = ['receipt_number', 'total_amount', 'created_at'];
```

```php
// app/Views/Reusables/menu.php
['label' => 'Financial Analytics', 'icon' => 'bi-graph-up', 'url' => base_url('financial'), 'roles' => ['Owner']],
```

```php
// app/Views/auth/dashboard.php
<a href="<?= base_url('financial') ?>" class="text-decoration-none text-reset d-block h-100">
```

```php
// app/Config/Routes.php
$routes->get('financial', 'FinancialAnalyticsController::index');
$routes->get('financial/expenses', 'FinancialAnalyticsController::expenses');
$routes->post('financial/expenses/create', 'FinancialAnalyticsController::createExpense');
$routes->post('financial/expenses/update/(:num)', 'FinancialAnalyticsController::updateExpense/$1');
$routes->post('financial/expenses/delete/(:num)', 'FinancialAnalyticsController::deleteExpense/$1');
$routes->post('financial/categories/create', 'FinancialAnalyticsController::createCategory');
$routes->post('financial/categories/update/(:num)', 'FinancialAnalyticsController::updateCategory/$1');
$routes->post('financial/categories/delete/(:num)', 'FinancialAnalyticsController::deleteCategory/$1');
$routes->get('financial/export-csv', 'FinancialAnalyticsController::exportMonthlyCsv');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialNavigationIntegrationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/StockOutController.php app/Models/Receipt.php app/Views/Reusables/menu.php app/Views/auth/dashboard.php app/Config/Routes.php
git commit -m "feat(financial): wire receipts timestamps and owner financial navigation"
```

### Task 6: Full verification and cleanup

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Run focused financial tests**

```bash
composer test -- --no-coverage --filter FinancialAnalyticsAccessTest
composer test -- --no-coverage --filter FinancialAnalyticsDashboardTest
composer test -- --no-coverage --filter FinancialAnalyticsExpenseCrudTest
composer test -- --no-coverage --filter FinancialNavigationIntegrationTest
```

- [ ] **Step 2: Run full test suite**

Run: `composer test -- --no-coverage`
Expected: PASS.

- [ ] **Step 3: Run migration status check**

```bash
php spark migrate
php spark migrate:status
```

Expected: new financial migrations are applied and listed.

- [ ] **Step 4: Update docs snippet**

```md
Financial Analytics is available at `/financial` for Owner accounts, with expense tracking, period summaries, and monthly CSV export.
```

- [ ] **Step 5: Commit**

```bash
git add README.md
git commit -m "docs(financial): add financial analytics usage note"
```

