# Financial Analytics Total Breakdown Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add daily/weekly/monthly drill-down panels on Financial Analytics so owners can see every source row behind each total.

**Architecture:** Keep `/financial` as the single dashboard, add a new owner-only JSON endpoint (`/financial/breakdown`) that returns grouped rows and subtotals for a requested period, and lazy-load each period’s details from collapsible UI panels. Reuse existing window and total-source rules so drilldown math matches current totals (`receipts`, `paid bills`, `product expenses`).

**Tech Stack:** PHP 8.2+, CodeIgniter 4.7, Query Builder, Bootstrap 5 collapse UI, vanilla JavaScript fetch, PHPUnit 10 feature/unit tests.

---

## File Structure and Responsibilities

- Modify: `app/Config/Routes.php` — register `GET /financial/breakdown`.
- Modify: `app/Controllers/FinancialAnalyticsController.php` — add breakdown endpoint, period resolver, row fetchers, subtotal payload builder.
- Modify: `app/Views/financial/dashboard.php` — add per-period collapsible drilldown containers and lazy-load script.
- Create: `tests/feature/FinancialAnalyticsBreakdownEndpointTest.php` — endpoint auth/validation/payload/subtotal behavior.
- Create: `tests/unit/FinancialDashboardBreakdownViewTest.php` — view-level assertions for drilldown triggers/placeholders/script hook.
- Modify: `tests/feature/FinancialAnalyticsDashboardTest.php` — keep dashboard regression coverage after UI expansion.
- Modify: `tests/feature/FinancialTotalsSourceRulesTest.php` — assert breakdown reads the same source families as totals.

### Task 1: Add breakdown endpoint route + access/validation contract tests

**Files:**
- Create: `tests/feature/FinancialAnalyticsBreakdownEndpointTest.php`
- Modify: `app/Config/Routes.php`
- Modify: `app/Controllers/FinancialAnalyticsController.php`

- [ ] **Step 1: Write the failing feature tests for access and period validation**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FinancialAnalyticsBreakdownEndpointTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testBreakdownGuestIsRedirectedToLogin(): void
    {
        $this->get('financial/breakdown?period=daily')->assertRedirectTo('/login');
    }

    public function testBreakdownEmployeeIsRedirectedToDashboard(): void
    {
        $this->withSession([
            'logged_in' => true,
            'role' => 'Employee',
            'user_id' => 2,
        ])->get('financial/breakdown?period=daily')->assertRedirectTo('/dashboard');
    }

    public function testBreakdownOwnerRejectsInvalidPeriod(): void
    {
        $response = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('financial/breakdown?period=yearly');

        $response->assertStatus(400);
        $response->assertJSONFragment(['error' => 'Invalid period.']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsBreakdownEndpointTest`
Expected: FAIL because `/financial/breakdown` route/action does not exist yet.

- [ ] **Step 3: Add route and minimal controller method contract**

```php
// app/Config/Routes.php
$routes->get('financial/breakdown', 'FinancialAnalyticsController::breakdown');
```

```php
// app/Controllers/FinancialAnalyticsController.php (inside class)
public function breakdown()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $period = trim((string) $this->request->getGet('period'));
    if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
        return $this->response->setStatusCode(400)->setJSON([
            'error' => 'Invalid period.',
        ]);
    }

    return $this->response->setJSON([
        'period' => $period,
        'from' => null,
        'to' => null,
        'sources' => [
            'receipts' => ['subtotal' => 0.0, 'rows' => []],
            'paid_bills' => ['subtotal' => 0.0, 'rows' => []],
            'product_expenses' => ['subtotal' => 0.0, 'rows' => []],
        ],
        'totals' => ['revenue' => 0.0, 'expenses' => 0.0],
    ]);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsBreakdownEndpointTest`
Expected: PASS for access and invalid-period checks.

- [ ] **Step 5: Commit**

```bash
git add tests/feature/FinancialAnalyticsBreakdownEndpointTest.php app/Config/Routes.php app/Controllers/FinancialAnalyticsController.php
git commit -m "feat(financial): add breakdown endpoint contract and route"
```

### Task 2: Implement breakdown data queries and subtotal payload behavior

**Files:**
- Modify: `tests/feature/FinancialAnalyticsBreakdownEndpointTest.php`
- Modify: `app/Controllers/FinancialAnalyticsController.php`

- [ ] **Step 1: Extend test with seeded data + payload assertions (failing first)**

```php
// tests/feature/FinancialAnalyticsBreakdownEndpointTest.php (additions)
use Config\Database;

protected function setUp(): void
{
    parent::setUp();
    $this->ensureTables();
    $this->resetTables();
    $this->seedRows();
}

public function testBreakdownOwnerGetsDailyRowsAndSubtotals(): void
{
    $response = $this->withSession([
        'logged_in' => true,
        'role' => 'Owner',
        'user_id' => 1,
    ])->get('financial/breakdown?period=daily');

    $response->assertStatus(200);
    $json = json_decode((string) $response->getBody(), true);

    $this->assertIsArray($json);
    $this->assertSame('daily', (string) ($json['period'] ?? ''));
    $this->assertSame('500.00', number_format((float) ($json['sources']['receipts']['subtotal'] ?? 0), 2, '.', ''));
    $this->assertSame('200.00', number_format((float) ($json['sources']['paid_bills']['subtotal'] ?? 0), 2, '.', ''));
    $this->assertSame('100.00', number_format((float) ($json['sources']['product_expenses']['subtotal'] ?? 0), 2, '.', ''));
    $this->assertSame('500.00', number_format((float) ($json['totals']['revenue'] ?? 0), 2, '.', ''));
    $this->assertSame('300.00', number_format((float) ($json['totals']['expenses'] ?? 0), 2, '.', ''));
}

private function ensureTables(): void
{
    $db = db_connect();
    $forge = Database::forge();

    if (!$db->tableExists('receipts')) {
        $forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'receipt_number' => ['type' => 'VARCHAR', 'constraint' => 255],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('receipts', true);
    }

    if (!$db->tableExists('bills')) {
        $forge->addField([
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
        $forge->addKey('id', true);
        $forge->createTable('bills', true);
    }

    if (!$db->tableExists('product_expenses')) {
        $forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'stock_in_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'expense_date' => ['type' => 'DATETIME'],
            'source_label' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'stock-in-capital'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('product_expenses', true);
    }
}

private function resetTables(): void
{
    $db = db_connect();
    $db->table('receipts')->truncate();
    $db->table('bills')->truncate();
    $db->table('product_expenses')->truncate();
}

private function seedRows(): void
{
    $db = db_connect();
    $today = date('Y-m-d');

    $db->table('receipts')->insert([
        'receipt_number' => 'R-1001',
        'total_amount' => 500.00,
        'created_at' => $today . ' 09:00:00',
    ]);

    $db->table('bills')->insert([
        'bill_name' => 'Electricity',
        'amount' => 200.00,
        'bill_date' => $today,
        'due_date' => $today,
        'status' => 'paid',
        'notes' => 'Daily utility',
        'recorded_by' => 1,
    ]);

    $db->table('product_expenses')->insert([
        'stock_in_id' => 1,
        'amount' => 100.00,
        'expense_date' => $today . ' 10:00:00',
        'source_label' => 'stock-in-capital',
    ]);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsBreakdownEndpointTest::testBreakdownOwnerGetsDailyRowsAndSubtotals`
Expected: FAIL because endpoint still returns empty stub payload.

- [ ] **Step 3: Implement real period window + grouped row query payload**

```php
// app/Controllers/FinancialAnalyticsController.php (inside class)
public function breakdown()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $period = trim((string) $this->request->getGet('period'));
    if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
        return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid period.']);
    }

    [$from, $to] = $this->periodRange($period);
    $receiptRows = $this->receiptRows($from, $to);
    $paidBillRows = $this->paidBillRows($from, $to);
    $productExpenseRows = $this->productExpenseRows($from, $to);

    $receiptSubtotal = array_sum(array_map(static fn(array $row): float => (float) ($row['total_amount'] ?? 0), $receiptRows));
    $paidBillSubtotal = array_sum(array_map(static fn(array $row): float => (float) ($row['amount'] ?? 0), $paidBillRows));
    $productExpenseSubtotal = array_sum(array_map(static fn(array $row): float => (float) ($row['amount'] ?? 0), $productExpenseRows));

    return $this->response->setJSON([
        'period' => $period,
        'from' => $from,
        'to' => $to,
        'sources' => [
            'receipts' => ['subtotal' => round($receiptSubtotal, 2), 'rows' => $receiptRows],
            'paid_bills' => ['subtotal' => round($paidBillSubtotal, 2), 'rows' => $paidBillRows],
            'product_expenses' => ['subtotal' => round($productExpenseSubtotal, 2), 'rows' => $productExpenseRows],
        ],
        'totals' => [
            'revenue' => round($receiptSubtotal, 2),
            'expenses' => round($paidBillSubtotal + $productExpenseSubtotal, 2),
        ],
    ]);
}

private function periodRange(string $period): array
{
    $windows = $this->dateWindows();
    if ($period === 'daily') {
        return [$windows['daily_start'], $windows['daily_end']];
    }
    if ($period === 'weekly') {
        return [$windows['weekly_start'], $windows['weekly_end']];
    }
    return [$windows['monthly_start'], $windows['monthly_end']];
}

private function receiptRows(string $from, string $to): array
{
    $db = db_connect();
    if (!$db->tableExists('receipts') || !$db->fieldExists('created_at', 'receipts')) {
        return [];
    }

    return $db->table('receipts')
        ->select('id, receipt_number, total_amount, created_at')
        ->where('created_at >=', $from)
        ->where('created_at <=', $to)
        ->orderBy('created_at', 'DESC')
        ->get()
        ->getResultArray();
}

private function paidBillRows(string $from, string $to): array
{
    $db = db_connect();
    if (!$db->tableExists('bills')) {
        return [];
    }

    return $db->table('bills')
        ->select('id, bill_name, amount, bill_date, due_date, status, notes')
        ->where('status', 'paid')
        ->where('bill_date >=', substr($from, 0, 10))
        ->where('bill_date <=', substr($to, 0, 10))
        ->orderBy('bill_date', 'DESC')
        ->get()
        ->getResultArray();
}

private function productExpenseRows(string $from, string $to): array
{
    $db = db_connect();
    if (!$db->tableExists('product_expenses')) {
        return [];
    }

    return $db->table('product_expenses')
        ->select('id, stock_in_id, amount, expense_date, source_label')
        ->where('expense_date >=', $from)
        ->where('expense_date <=', $to)
        ->orderBy('expense_date', 'DESC')
        ->get()
        ->getResultArray();
}
```

- [ ] **Step 4: Run endpoint tests to verify pass**

Run: `composer test -- --no-coverage --filter FinancialAnalyticsBreakdownEndpointTest`
Expected: PASS with valid period payload containing grouped rows and correct subtotals.

- [ ] **Step 5: Commit**

```bash
git add tests/feature/FinancialAnalyticsBreakdownEndpointTest.php app/Controllers/FinancialAnalyticsController.php
git commit -m "feat(financial): implement period breakdown endpoint with source rows"
```

### Task 3: Add dashboard drilldown UI (collapsible + lazy fetch) with unit view coverage

**Files:**
- Create: `tests/unit/FinancialDashboardBreakdownViewTest.php`
- Modify: `app/Views/financial/dashboard.php`

- [ ] **Step 1: Write failing unit view test for drilldown triggers and containers**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;

final class FinancialDashboardBreakdownViewTest extends CIUnitTestCase
{
    public function testDashboardViewContainsBreakdownTogglesForAllPeriods(): void
    {
        $html = view('financial/dashboard', [
            'metrics' => [
                'daily' => ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0],
                'weekly' => ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0],
                'monthly' => ['revenue' => 0, 'expenses' => 0, 'net_income' => 0, 'profit_margin' => 0],
            ],
        ]);

        $this->assertStringContainsString('data-period="daily"', $html);
        $this->assertStringContainsString('data-period="weekly"', $html);
        $this->assertStringContainsString('data-period="monthly"', $html);
        $this->assertStringContainsString('financial-breakdown-panel-daily', $html);
        $this->assertStringContainsString('financial-breakdown-panel-weekly', $html);
        $this->assertStringContainsString('financial-breakdown-panel-monthly', $html);
        $this->assertStringContainsString('/financial/breakdown?period=', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter FinancialDashboardBreakdownViewTest`
Expected: FAIL because dashboard does not yet include breakdown toggle markup/script.

- [ ] **Step 3: Add collapsible panels and lazy-load script to dashboard view**

```php
<!-- app/Views/financial/dashboard.php (add beneath each period section) -->
<button
    class="btn btn-sm btn-outline-secondary mt-2 js-financial-breakdown-toggle"
    type="button"
    data-bs-toggle="collapse"
    data-bs-target="#financial-breakdown-panel-daily"
    data-period="daily"
    aria-expanded="false"
    aria-controls="financial-breakdown-panel-daily"
>
    Show source rows
</button>

<div id="financial-breakdown-panel-daily" class="collapse mt-3">
    <div class="border rounded-3 p-3 bg-light js-breakdown-content" data-period="daily">
        <div class="text-muted small">Expand to load breakdown...</div>
    </div>
</div>

<script>
(() => {
    const cache = {};
    const toggles = document.querySelectorAll('.js-financial-breakdown-toggle');

    const currency = (value) => `₱${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    const renderRows = (rows, columns) => {
        if (!Array.isArray(rows) || rows.length === 0) {
            return '<div class="text-muted small">No rows for this period.</div>';
        }
        const head = `<tr>${columns.map((col) => `<th>${col.label}</th>`).join('')}</tr>`;
        const body = rows.map((row) => `<tr>${columns.map((col) => `<td>${col.format ? col.format(row[col.key]) : (row[col.key] ?? '')}</td>`).join('')}</tr>`).join('');
        return `<div class="table-responsive"><table class="table table-sm table-striped mb-3"><thead>${head}</thead><tbody>${body}</tbody></table></div>`;
    };

    const renderBreakdown = (payload) => {
        const receipts = payload.sources?.receipts ?? { subtotal: 0, rows: [] };
        const paidBills = payload.sources?.paid_bills ?? { subtotal: 0, rows: [] };
        const productExpenses = payload.sources?.product_expenses ?? { subtotal: 0, rows: [] };

        return `
            <div class="small fw-semibold mb-2">Revenue total = ${currency(payload.totals?.revenue)}</div>
            <div class="small mb-3">Receipts subtotal: ${currency(receipts.subtotal)}</div>
            ${renderRows(receipts.rows, [
                { key: 'receipt_number', label: 'Receipt #' },
                { key: 'created_at', label: 'Date/Time' },
                { key: 'total_amount', label: 'Amount', format: currency },
            ])}
            <div class="small fw-semibold mt-3">Expense total = ${currency(payload.totals?.expenses)}</div>
            <div class="small">Paid bills subtotal: ${currency(paidBills.subtotal)}</div>
            ${renderRows(paidBills.rows, [
                { key: 'bill_name', label: 'Bill' },
                { key: 'bill_date', label: 'Bill Date' },
                { key: 'notes', label: 'Notes' },
                { key: 'amount', label: 'Amount', format: currency },
            ])}
            <div class="small">Product expenses subtotal: ${currency(productExpenses.subtotal)}</div>
            ${renderRows(productExpenses.rows, [
                { key: 'source_label', label: 'Source' },
                { key: 'expense_date', label: 'Expense Date' },
                { key: 'stock_in_id', label: 'Stock-In ID' },
                { key: 'amount', label: 'Amount', format: currency },
            ])}
        `;
    };

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', async () => {
            const period = toggle.dataset.period;
            const host = document.querySelector(`.js-breakdown-content[data-period="${period}"]`);
            if (!host) return;
            if (cache[period]) {
                host.innerHTML = cache[period];
                return;
            }
            host.innerHTML = '<div class="text-muted small">Loading source rows...</div>';
            try {
                const response = await fetch(`<?= base_url('financial/breakdown') ?>?period=${encodeURIComponent(period)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Request failed');
                const payload = await response.json();
                const html = renderBreakdown(payload);
                cache[period] = html;
                host.innerHTML = html;
            } catch (error) {
                host.innerHTML = '<div class="text-danger small">Unable to load source rows.</div>';
            }
        });
    });
})();
</script>
```

- [ ] **Step 4: Run view test to verify it passes**

Run: `composer test -- --no-coverage --filter FinancialDashboardBreakdownViewTest`
Expected: PASS with drilldown triggers and endpoint hook present.

- [ ] **Step 5: Commit**

```bash
git add tests/unit/FinancialDashboardBreakdownViewTest.php app/Views/financial/dashboard.php
git commit -m "feat(financial): add collapsible dashboard breakdown UI"
```

### Task 4: Add regression coverage and run final financial test sweep

**Files:**
- Modify: `tests/feature/FinancialAnalyticsDashboardTest.php`
- Modify: `tests/feature/FinancialTotalsSourceRulesTest.php`

- [ ] **Step 1: Add dashboard and source-rule regression assertions**

```php
// tests/feature/FinancialAnalyticsDashboardTest.php (inside testDashboardShowsRevenueExpenseNetIncomeAndMargin)
$result->assertSee('Financial Analytics');
$result->assertSee('Show source rows');
```

```php
// tests/feature/FinancialTotalsSourceRulesTest.php
public function testBreakdownUsesSameSourcesAsTotalFormula(): void
{
    $controllerFile = file_get_contents(APPPATH . 'Controllers/FinancialAnalyticsController.php');
    $this->assertNotFalse($controllerFile);
    $this->assertStringContainsString('receiptRows', (string) $controllerFile);
    $this->assertStringContainsString('paidBillRows', (string) $controllerFile);
    $this->assertStringContainsString('productExpenseRows', (string) $controllerFile);
}
```

- [ ] **Step 2: Run regression tests to verify they fail before updates are complete**

Run: `composer test -- --no-coverage --filter "FinancialAnalyticsDashboardTest|FinancialTotalsSourceRulesTest"`
Expected: FAIL until all new hooks/methods are present.

- [ ] **Step 3: Run the focused financial suite and confirm all pass**

Run: `composer test -- --no-coverage --filter "FinancialAnalyticsBreakdownEndpointTest|FinancialDashboardBreakdownViewTest|FinancialAnalyticsDashboardTest|FinancialTotalsSourceRulesTest|FinancialAnalyticsAccessTest"`
Expected: PASS.

- [ ] **Step 4: Run the full test suite once for safety**

Run: `composer test -- --no-coverage`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/feature/FinancialAnalyticsDashboardTest.php tests/feature/FinancialTotalsSourceRulesTest.php
git commit -m "test(financial): add breakdown regressions and source consistency checks"
```

