# Audit Trail Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an Owner-only Audit Trail module that records Owner and Employee create/update/delete actions across core functions, adds an Audit Trail menu item under Management, and supports filtered CSV/PDF exports.

**Architecture:** Add an immutable `audit_trails` table, an `AuditTrailModel`, and a shared `AuditTrailService` used explicitly from mutation endpoints. Create an Owner-only `AuditTrailController` with shared filtering logic for list/CSV/PDF outputs and a dedicated management view. Wire routes and navigation, then integrate logging into Auth user registration, Financial CRUD, Stock-In, and Stock-Out flows.

**Tech Stack:** PHP 8.2+, CodeIgniter 4.7, Query Builder, Bootstrap 5, PHPUnit 10 feature/unit tests, dompdf for PDF export.

---

## File Structure and Responsibilities

- Create: `app/Database/Migrations/2026-04-11-071500_CreateAuditTrailsTable.php` — immutable audit table schema.
- Create: `app/Models/AuditTrailModel.php` — persistence for audit rows.
- Create: `app/Services/AuditTrailService.php` — normalized audit entry writer.
- Create: `app/Controllers/AuditTrailController.php` — Owner-only listing and export endpoints.
- Create: `app/Views/management/audit_trail.php` — filter form + table + export actions.
- Create: `app/Views/management/audit_trail_pdf.php` — printable PDF template.
- Modify: `app/Config/Routes.php` — add audit page/export routes.
- Modify: `app/Views/Reusables/menu.php` — add Management → Audit Trail button (Owner-only).
- Modify: `app/Controllers/Auth.php` — log user creation in register flow.
- Modify: `app/Controllers/FinancialAnalyticsController.php` — log bill/expense/category create/update/delete.
- Modify: `app/Controllers/StockInController.php` — log stock-in create/merge updates.
- Modify: `app/Controllers/StockOutController.php` — log stock-out inventory and cashier confirmations.
- Modify: `composer.json` and `composer.lock` — add dompdf dependency.
- Create: `tests/feature/AuditTrailAccessTest.php` — route-level access control checks.
- Create: `tests/unit/AuditTrailNavigationViewTest.php` — menu content assertion.
- Create: `tests/feature/AuditTrailServiceWriteTest.php` — service write behavior and JSON snapshots.
- Create: `tests/feature/AuditTrailFilterAndCsvExportTest.php` — list filtering + CSV export parity.
- Create: `tests/feature/AuditTrailPdfExportTest.php` — PDF export checks.
- Create: `tests/feature/AuditTrailFinancialAndUserLoggingTest.php` — Auth + Financial logging coverage.
- Create: `tests/feature/AuditTrailStockLoggingTest.php` — Stock-In/Stock-Out logging coverage.

### Task 1: Add access/nav tests plus minimal route and menu wiring

**Files:**
- Create: `tests/feature/AuditTrailAccessTest.php`
- Create: `tests/unit/AuditTrailNavigationViewTest.php`
- Modify: `app/Config/Routes.php`
- Modify: `app/Views/Reusables/menu.php`
- Create: `app/Controllers/AuditTrailController.php`

- [ ] **Step 1: Write failing access and menu tests**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class AuditTrailAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get('management/audit-trail')->assertRedirectTo('/login');
    }

    public function testEmployeeIsRedirectedToDashboard(): void
    {
        $this->withSession([
            'logged_in' => true,
            'role' => 'Employee',
            'user_id' => 2,
        ])->get('management/audit-trail')->assertRedirectTo('/dashboard');
    }
}
```

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;

final class AuditTrailNavigationViewTest extends CIUnitTestCase
{
    public function testManagementMenuContainsOwnerOnlyAuditTrail(): void
    {
        $menu = file_get_contents(APPPATH . 'Views/Reusables/menu.php');

        $this->assertNotFalse($menu);
        $this->assertStringContainsString("'Management'", (string) $menu);
        $this->assertStringContainsString("'Audit Trail'", (string) $menu);
        $this->assertStringContainsString("'management/audit-trail'", (string) $menu);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test -- --no-coverage --filter "AuditTrailAccessTest|AuditTrailNavigationViewTest"`
Expected: FAIL because route/controller/menu entry do not exist yet.

- [ ] **Step 3: Add minimal route/controller guard and menu item**

```php
// app/Config/Routes.php
$routes->get('management/audit-trail', 'AuditTrailController::index');
```

```php
<?php
// app/Controllers/AuditTrailController.php

namespace App\Controllers;

class AuditTrailController extends BaseController
{
    public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        if ((string) session()->get('role') !== 'Owner') {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Audit Trail.');
        }

        return 'audit-trail-index';
    }
}
```

```php
// app/Views/Reusables/menu.php (inside Management group items)
['label' => 'Audit Trail', 'icon' => 'bi-clipboard-data', 'path' => 'management/audit-trail', 'roles' => ['Owner']],
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `composer test -- --no-coverage --filter "AuditTrailAccessTest|AuditTrailNavigationViewTest"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/feature/AuditTrailAccessTest.php tests/unit/AuditTrailNavigationViewTest.php app/Config/Routes.php app/Views/Reusables/menu.php app/Controllers/AuditTrailController.php
git commit -m "feat(audit): add owner-only audit trail route and menu entry"
```

### Task 2: Add schema, model, and service for immutable audit writes

**Files:**
- Create: `tests/feature/AuditTrailServiceWriteTest.php`
- Create: `app/Database/Migrations/2026-04-11-071500_CreateAuditTrailsTable.php`
- Create: `app/Models/AuditTrailModel.php`
- Create: `app/Services/AuditTrailService.php`

- [ ] **Step 1: Write failing service behavior test**

```php
<?php

use App\Services\AuditTrailService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class AuditTrailServiceWriteTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureUsersTable();
        $this->ensureAuditTable();
        db_connect()->table('audit_trails')->truncate();
    }

    public function testServiceWritesCreateEntryWithAfterJson(): void
    {
        $service = new AuditTrailService();
        $insertId = $service->log([
            'actor_user_id' => 1,
            'actor_role' => 'Owner',
            'module' => 'financial',
            'action' => 'create',
            'entity_type' => 'bill',
            'entity_id' => '10',
            'summary' => 'Created bill Electric Bill',
            'before_data' => null,
            'after_data' => ['id' => 10, 'bill_name' => 'Electric Bill'],
            'request_method' => 'POST',
            'request_path' => 'financial/bills/create',
        ]);

        $this->assertIsInt($insertId);
        $row = db_connect()->table('audit_trails')->where('id', $insertId)->get()->getRowArray();
        $this->assertSame('financial', (string) ($row['module'] ?? ''));
        $this->assertSame('create', (string) ($row['action'] ?? ''));
        $this->assertSame('{"id":10,"bill_name":"Electric Bill"}', (string) ($row['after_data'] ?? ''));
    }

    private function ensureUsersTable(): void
    {
        $db = db_connect();
        if ($db->tableExists('users')) {
            return;
        }

        $forge = Database::forge();
        $forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 30],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 30],
            'email' => ['type' => 'VARCHAR', 'constraint' => 255],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('users', true);
    }

    private function ensureAuditTable(): void
    {
        $db = db_connect();
        if ($db->tableExists('audit_trails')) {
            return;
        }

        $forge = Database::forge();
        $forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_role' => ['type' => 'VARCHAR', 'constraint' => 20],
            'module' => ['type' => 'VARCHAR', 'constraint' => 40],
            'action' => ['type' => 'VARCHAR', 'constraint' => 20],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'summary' => ['type' => 'VARCHAR', 'constraint' => 255],
            'before_data' => ['type' => 'TEXT', 'null' => true],
            'after_data' => ['type' => 'TEXT', 'null' => true],
            'request_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'request_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('audit_trails', true);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter AuditTrailServiceWriteTest`
Expected: FAIL because `AuditTrailService` class does not exist yet.

- [ ] **Step 3: Add migration, model, and service**

```php
<?php
// app/Database/Migrations/2026-04-11-071500_CreateAuditTrailsTable.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditTrailsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_role' => ['type' => 'VARCHAR', 'constraint' => 20],
            'module' => ['type' => 'VARCHAR', 'constraint' => 40],
            'action' => ['type' => 'VARCHAR', 'constraint' => 20],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'summary' => ['type' => 'VARCHAR', 'constraint' => 255],
            'before_data' => ['type' => 'TEXT', 'null' => true],
            'after_data' => ['type' => 'TEXT', 'null' => true],
            'request_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'request_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['module', 'action', 'created_at'], false, false, 'idx_audit_module_action_created_at');
        $this->forge->createTable('audit_trails');
    }

    public function down()
    {
        $this->forge->dropTable('audit_trails');
    }
}
```

```php
<?php
// app/Models/AuditTrailModel.php

namespace App\Models;

use CodeIgniter\Model;

class AuditTrailModel extends Model
{
    protected $table = 'audit_trails';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'actor_user_id',
        'actor_role',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'summary',
        'before_data',
        'after_data',
        'request_method',
        'request_path',
        'ip_address',
        'created_at',
    ];
    protected $useTimestamps = false;
}
```

```php
<?php
// app/Services/AuditTrailService.php

namespace App\Services;

use App\Models\AuditTrailModel;
use RuntimeException;

class AuditTrailService
{
    private const MODULES = ['inventory', 'stock_in', 'stock_out', 'sales', 'financial', 'user_management'];
    private const ACTIONS = ['create', 'update', 'delete'];

    public function log(array $payload): int
    {
        $module = (string) ($payload['module'] ?? '');
        $action = (string) ($payload['action'] ?? '');
        if (!in_array($module, self::MODULES, true) || !in_array($action, self::ACTIONS, true)) {
            throw new RuntimeException('Invalid audit module or action.');
        }

        $beforeJson = $payload['before_data'] === null ? null : json_encode($payload['before_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $afterJson = $payload['after_data'] === null ? null : json_encode($payload['after_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (($payload['before_data'] !== null && $beforeJson === false) || ($payload['after_data'] !== null && $afterJson === false)) {
            throw new RuntimeException('Could not encode audit snapshot data.');
        }

        $model = new AuditTrailModel();
        $inserted = $model->insert([
            'actor_user_id' => $payload['actor_user_id'] ?? null,
            'actor_role' => (string) ($payload['actor_role'] ?? ''),
            'module' => $module,
            'action' => $action,
            'entity_type' => (string) ($payload['entity_type'] ?? ''),
            'entity_id' => isset($payload['entity_id']) ? (string) $payload['entity_id'] : null,
            'summary' => (string) ($payload['summary'] ?? ''),
            'before_data' => $beforeJson,
            'after_data' => $afterJson,
            'request_method' => $payload['request_method'] ?? null,
            'request_path' => $payload['request_path'] ?? null,
            'ip_address' => $payload['ip_address'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        if ($inserted === false) {
            throw new RuntimeException('Could not write audit trail entry.');
        }

        return (int) $inserted;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter AuditTrailServiceWriteTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Database/Migrations/2026-04-11-071500_CreateAuditTrailsTable.php app/Models/AuditTrailModel.php app/Services/AuditTrailService.php tests/feature/AuditTrailServiceWriteTest.php
git commit -m "feat(audit): add audit trail schema model and writer service"
```

### Task 3: Implement list/filter page and CSV export from one shared query path

**Files:**
- Create: `tests/feature/AuditTrailFilterAndCsvExportTest.php`
- Modify: `app/Config/Routes.php`
- Modify: `app/Controllers/AuditTrailController.php`
- Create: `app/Views/management/audit_trail.php`

- [ ] **Step 1: Write failing filter/list and CSV tests**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuditTrailFilterAndCsvExportTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureAuditTable();
        $db = db_connect();
        $db->table('audit_trails')->truncate();
        $db->table('audit_trails')->insertBatch([
            ['actor_user_id' => 1, 'actor_role' => 'Owner', 'module' => 'financial', 'action' => 'create', 'entity_type' => 'bill', 'entity_id' => '1', 'summary' => 'Bill created', 'before_data' => null, 'after_data' => '{"id":1}', 'request_method' => 'POST', 'request_path' => 'financial/bills/create', 'ip_address' => null, 'created_at' => '2026-04-11 01:00:00'],
            ['actor_user_id' => 2, 'actor_role' => 'Employee', 'module' => 'stock_in', 'action' => 'update', 'entity_type' => 'stock_in', 'entity_id' => '5', 'summary' => 'Stock merged', 'before_data' => '{"quantity":2}', 'after_data' => '{"quantity":5}', 'request_method' => 'POST', 'request_path' => 'stockin', 'ip_address' => null, 'created_at' => '2026-04-11 02:00:00'],
        ]);
    }

    public function testOwnerCanFilterByModule(): void
    {
        $result = $this->withSession(['logged_in' => true, 'role' => 'Owner', 'user_id' => 1])
            ->get('management/audit-trail?module=financial');

        $result->assertStatus(200);
        $result->assertSee('Bill created');
        $result->assertDontSee('Stock merged');
    }

    public function testCsvExportUsesSameFilterSet(): void
    {
        $result = $this->withSession(['logged_in' => true, 'role' => 'Owner', 'user_id' => 1])
            ->get('management/audit-trail/export-csv?module=financial');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $result->assertHeaderContains('Content-Disposition', 'audit-trail-');
    }

    private function ensureAuditTable(): void
    {
        $db = db_connect();
        if ($db->tableExists('audit_trails')) {
            return;
        }
        $forge = Database::forge();
        $forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_role' => ['type' => 'VARCHAR', 'constraint' => 20],
            'module' => ['type' => 'VARCHAR', 'constraint' => 40],
            'action' => ['type' => 'VARCHAR', 'constraint' => 20],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'summary' => ['type' => 'VARCHAR', 'constraint' => 255],
            'before_data' => ['type' => 'TEXT', 'null' => true],
            'after_data' => ['type' => 'TEXT', 'null' => true],
            'request_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'request_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('audit_trails', true);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter AuditTrailFilterAndCsvExportTest`
Expected: FAIL because CSV route and full listing/filter logic are not implemented.

- [ ] **Step 3: Implement shared filters, listing page, and CSV endpoint**

```php
// app/Config/Routes.php
$routes->get('management/audit-trail/export-csv', 'AuditTrailController::exportCsv');
```

```php
<?php
// app/Controllers/AuditTrailController.php (controller implementation)

namespace App\Controllers;

use App\Models\AuditTrailModel;

class AuditTrailController extends BaseController
{
    public function index()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $filters = $this->readFilters();
        $rows = $this->buildFilteredQuery($filters)->orderBy('id', 'DESC')->limit(200)->findAll();

        return view('Reusables/menu') . view('management/audit_trail', [
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    public function exportCsv()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $filters = $this->readFilters();
        $rows = $this->buildFilteredQuery($filters)->orderBy('id', 'DESC')->findAll();

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['id', 'actor_user_id', 'actor_role', 'module', 'action', 'entity_type', 'entity_id', 'summary', 'before_data', 'after_data', 'request_method', 'request_path', 'created_at']);
        foreach ($rows as $row) {
            fputcsv($stream, [
                $row['id'] ?? '',
                $row['actor_user_id'] ?? '',
                $row['actor_role'] ?? '',
                $row['module'] ?? '',
                $row['action'] ?? '',
                $row['entity_type'] ?? '',
                $row['entity_id'] ?? '',
                $row['summary'] ?? '',
                $row['before_data'] ?? '',
                $row['after_data'] ?? '',
                $row['request_method'] ?? '',
                $row['request_path'] ?? '',
                $row['created_at'] ?? '',
            ]);
        }
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="audit-trail-' . date('YmdHis') . '.csv"')
            ->setBody($csv);
    }

    private function requireOwner()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        if ((string) session()->get('role') !== 'Owner') {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Audit Trail.');
        }

        return null;
    }

    private function readFilters(): array
    {
        return [
            'module' => trim((string) ($this->request->getGet('module') ?? '')),
            'action' => trim((string) ($this->request->getGet('action') ?? '')),
            'actor_user_id' => trim((string) ($this->request->getGet('actor_user_id') ?? '')),
            'date_from' => trim((string) ($this->request->getGet('date_from') ?? '')),
            'date_to' => trim((string) ($this->request->getGet('date_to') ?? '')),
        ];
    }

    private function buildFilteredQuery(array $filters): AuditTrailModel
    {
        $model = new AuditTrailModel();
        if ($filters['module'] !== '') {
            $model->where('module', $filters['module']);
        }
        if ($filters['action'] !== '') {
            $model->where('action', $filters['action']);
        }
        if ($filters['actor_user_id'] !== '') {
            $model->where('actor_user_id', (int) $filters['actor_user_id']);
        }
        if ($filters['date_from'] !== '') {
            $model->where('created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if ($filters['date_to'] !== '') {
            $model->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        return $model;
    }
}
```

```php
<?php
// app/Views/management/audit_trail.php (view implementation)
$rows = $rows ?? [];
$filters = $filters ?? [];
?>
<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h2 class="h4 mb-1">Audit Trail</h2>
            <p class="text-muted mb-0">Track create, update, and delete activities by function.</p>
        </div>
    </div>
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-2"><input class="form-control" name="module" value="<?= esc($filters['module'] ?? '') ?>" placeholder="module"></div>
        <div class="col-md-2"><input class="form-control" name="action" value="<?= esc($filters['action'] ?? '') ?>" placeholder="action"></div>
        <div class="col-md-2"><input class="form-control" name="actor_user_id" value="<?= esc($filters['actor_user_id'] ?? '') ?>" placeholder="actor id"></div>
        <div class="col-md-2"><input class="form-control" type="date" name="date_from" value="<?= esc($filters['date_from'] ?? '') ?>"></div>
        <div class="col-md-2"><input class="form-control" type="date" name="date_to" value="<?= esc($filters['date_to'] ?? '') ?>"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Apply Filters</button></div>
    </form>
    <div class="mb-3 d-flex gap-2">
        <a class="btn btn-outline-success btn-sm" href="<?= base_url('management/audit-trail/export-csv?' . http_build_query($filters)) ?>">Export CSV</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead><tr><th>When</th><th>Module</th><th>Action</th><th>Entity</th><th>Summary</th><th>Actor</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= esc((string) ($row['created_at'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['module'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['action'] ?? '')) ?></td>
                    <td><?= esc((string) (($row['entity_type'] ?? '') . ':' . ($row['entity_id'] ?? ''))) ?></td>
                    <td><?= esc((string) ($row['summary'] ?? '')) ?></td>
                    <td><?= esc((string) (($row['actor_role'] ?? '') . ' #' . ($row['actor_user_id'] ?? ''))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter AuditTrailFilterAndCsvExportTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/AuditTrailController.php app/Config/Routes.php app/Views/management/audit_trail.php tests/feature/AuditTrailFilterAndCsvExportTest.php
git commit -m "feat(audit): add filtered audit trail page and csv export"
```

### Task 4: Add PDF export support with dompdf and tests

**Files:**
- Create: `tests/feature/AuditTrailPdfExportTest.php`
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `app/Config/Routes.php`
- Modify: `app/Controllers/AuditTrailController.php`
- Create: `app/Views/management/audit_trail_pdf.php`

- [ ] **Step 1: Write failing PDF export test**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuditTrailPdfExportTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureAuditTable();
        $db = db_connect();
        $db->table('audit_trails')->truncate();
        $db->table('audit_trails')->insert([
            'actor_user_id' => 1,
            'actor_role' => 'Owner',
            'module' => 'financial',
            'action' => 'create',
            'entity_type' => 'bill',
            'entity_id' => '1',
            'summary' => 'Bill created',
            'before_data' => null,
            'after_data' => '{"id":1}',
            'request_method' => 'POST',
            'request_path' => 'financial/bills/create',
            'ip_address' => null,
            'created_at' => '2026-04-11 01:00:00',
        ]);
    }

    public function testPdfExportReturnsPdfDownload(): void
    {
        $result = $this->withSession(['logged_in' => true, 'role' => 'Owner', 'user_id' => 1])
            ->get('management/audit-trail/export-pdf?module=financial');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'application/pdf');
        $result->assertHeaderContains('Content-Disposition', 'audit-trail-');
    }

    private function ensureAuditTable(): void
    {
        $db = db_connect();
        if ($db->tableExists('audit_trails')) {
            return;
        }
        $forge = Database::forge();
        $forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_role' => ['type' => 'VARCHAR', 'constraint' => 20],
            'module' => ['type' => 'VARCHAR', 'constraint' => 40],
            'action' => ['type' => 'VARCHAR', 'constraint' => 20],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'summary' => ['type' => 'VARCHAR', 'constraint' => 255],
            'before_data' => ['type' => 'TEXT', 'null' => true],
            'after_data' => ['type' => 'TEXT', 'null' => true],
            'request_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'request_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('audit_trails', true);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter AuditTrailPdfExportTest`
Expected: FAIL because PDF route/dependency/renderer are missing.

- [ ] **Step 3: Install dompdf and implement export endpoint**

Run: `composer require dompdf/dompdf:^3.1`
Expected: composer updates `composer.json` and `composer.lock` successfully.

```php
// app/Config/Routes.php
$routes->get('management/audit-trail/export-pdf', 'AuditTrailController::exportPdf');
```

```php
<?php
// app/Controllers/AuditTrailController.php (key method)

use Dompdf\Dompdf;
use Dompdf\Options;

public function exportPdf()
{
    $guard = $this->requireOwner();
    if ($guard !== null) {
        return $guard;
    }

    $filters = $this->readFilters();
    $rows = $this->buildFilteredQuery($filters)->orderBy('id', 'DESC')->findAll();

    $html = view('management/audit_trail_pdf', [
        'rows' => $rows,
        'filters' => $filters,
        'generatedAt' => date('Y-m-d H:i:s'),
    ]);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $pdf = $dompdf->output();

    return $this->response
        ->setHeader('Content-Type', 'application/pdf')
        ->setHeader('Content-Disposition', 'attachment; filename="audit-trail-' . date('YmdHis') . '.pdf"')
        ->setBody($pdf);
}
```

```php
<?php
// app/Views/management/audit_trail_pdf.php (minimal printable template)
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h2>Audit Trail Report</h2>
    <p>Generated: <?= esc((string) ($generatedAt ?? '')) ?></p>
    <p>Filters: <?= esc(json_encode($filters ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') ?></p>
    <table>
        <thead>
            <tr><th>When</th><th>Module</th><th>Action</th><th>Entity</th><th>Summary</th><th>Actor</th></tr>
        </thead>
        <tbody>
        <?php foreach (($rows ?? []) as $row): ?>
            <tr>
                <td><?= esc((string) ($row['created_at'] ?? '')) ?></td>
                <td><?= esc((string) ($row['module'] ?? '')) ?></td>
                <td><?= esc((string) ($row['action'] ?? '')) ?></td>
                <td><?= esc((string) (($row['entity_type'] ?? '') . ':' . ($row['entity_id'] ?? ''))) ?></td>
                <td><?= esc((string) ($row['summary'] ?? '')) ?></td>
                <td><?= esc((string) (($row['actor_role'] ?? '') . ' #' . ($row['actor_user_id'] ?? ''))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter AuditTrailPdfExportTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock app/Config/Routes.php app/Controllers/AuditTrailController.php app/Views/management/audit_trail_pdf.php tests/feature/AuditTrailPdfExportTest.php
git commit -m "feat(audit): add pdf export for filtered audit trail"
```

### Task 5: Integrate audit logging into Financial and User Management mutations

**Files:**
- Create: `tests/feature/AuditTrailFinancialAndUserLoggingTest.php`
- Modify: `app/Controllers/FinancialAnalyticsController.php`
- Modify: `app/Controllers/Auth.php`

- [ ] **Step 1: Write failing logging tests for financial + register flows**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuditTrailFinancialAndUserLoggingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
        $this->resetTables();
        $this->seedOwnerUser();
    }

    public function testCreateBillWritesAuditTrail(): void
    {
        $this->withSession(['logged_in' => true, 'role' => 'Owner', 'user_id' => 1])->post('financial/bills/create', [
            'bill_name' => 'Water',
            'amount' => '800.00',
            'bill_date' => '2026-04-11',
            'due_date' => '2026-04-20',
            'status' => 'unpaid',
            'notes' => 'May bill',
        ])->assertRedirectTo('/financial/expenses');

        $row = db_connect()->table('audit_trails')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertSame('financial', (string) ($row['module'] ?? ''));
        $this->assertSame('create', (string) ($row['action'] ?? ''));
        $this->assertSame('bill', (string) ($row['entity_type'] ?? ''));
    }

    public function testRegisterUserWritesAuditTrail(): void
    {
        $this->withSession(['logged_in' => true, 'role' => 'Owner', 'user_id' => 1])->post('register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'role_id' => 1,
        ])->assertRedirectTo('/register');

        $row = db_connect()->table('audit_trails')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertSame('user_management', (string) ($row['module'] ?? ''));
        $this->assertSame('create', (string) ($row['action'] ?? ''));
        $this->assertSame('user', (string) ($row['entity_type'] ?? ''));
    }

    private function ensureTables(): void
    {
        $db = db_connect();
        $forge = Database::forge();

        if (!$db->tableExists('roles')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'role_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('roles', true);
        }

        if (!$db->tableExists('users')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'first_name' => ['type' => 'VARCHAR', 'constraint' => 30],
                'last_name' => ['type' => 'VARCHAR', 'constraint' => 30],
                'email' => ['type' => 'VARCHAR', 'constraint' => 255],
                'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
                'role_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('users', true);
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

        if (!$db->tableExists('expense_categories')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 120],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('expense_categories', true);
        }

        if (!$db->tableExists('expenses')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'expense_date' => ['type' => 'DATETIME'],
                'recorded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('expenses', true);
        }

        if (!$db->tableExists('audit_trails')) {
            $forge->addField([
                'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'actor_role' => ['type' => 'VARCHAR', 'constraint' => 20],
                'module' => ['type' => 'VARCHAR', 'constraint' => 40],
                'action' => ['type' => 'VARCHAR', 'constraint' => 20],
                'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80],
                'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'summary' => ['type' => 'VARCHAR', 'constraint' => 255],
                'before_data' => ['type' => 'TEXT', 'null' => true],
                'after_data' => ['type' => 'TEXT', 'null' => true],
                'request_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
                'request_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('audit_trails', true);
        }
    }

    private function resetTables(): void
    {
        $db = db_connect();
        $db->table('audit_trails')->truncate();
        $db->table('expenses')->truncate();
        $db->table('expense_categories')->truncate();
        $db->table('bills')->truncate();
        $db->table('users')->truncate();
        $db->table('roles')->truncate();
    }

    private function seedOwnerUser(): void
    {
        $db = db_connect();
        $db->table('roles')->insert(['id' => 1, 'role_name' => 'Owner']);
        $db->table('users')->insert([
            'id' => 1,
            'first_name' => 'Owner',
            'last_name' => 'User',
            'email' => 'owner@example.com',
            'password_hash' => password_hash('secret123', PASSWORD_DEFAULT),
            'role_id' => 1,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter AuditTrailFinancialAndUserLoggingTest`
Expected: FAIL because controller methods do not write audit entries yet.

- [ ] **Step 3: Add explicit audit writes to Auth + Financial mutation methods**

```php
<?php
// app/Controllers/FinancialAnalyticsController.php (shared helper)

use App\Services\AuditTrailService;

private function writeAudit(string $action, string $entityType, string|int|null $entityId, string $summary, ?array $beforeData, ?array $afterData): void
{
    (new AuditTrailService())->log([
        'actor_user_id' => (int) (session()->get('user_id') ?? 0) ?: null,
        'actor_role' => (string) (session()->get('role') ?? ''),
        'module' => 'financial',
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId === null ? null : (string) $entityId,
        'summary' => $summary,
        'before_data' => $beforeData,
        'after_data' => $afterData,
        'request_method' => $this->request->getMethod(),
        'request_path' => $this->request->getPath(),
        'ip_address' => $this->request->getIPAddress(),
    ]);
}
```

```php
// app/Controllers/FinancialAnalyticsController.php (example in createBill)
$inserted = (new Bill())->insert([
    'bill_name' => trim((string) $this->request->getPost('bill_name')),
    'amount' => (float) $this->request->getPost('amount'),
    'bill_date' => (string) $this->request->getPost('bill_date'),
    'due_date' => (string) $this->request->getPost('due_date'),
    'status' => (string) $this->request->getPost('status'),
    'notes' => trim((string) $this->request->getPost('notes')),
    'recorded_by' => (int) (session()->get('user_id') ?? 0),
], true);
if ($inserted === false) {
    return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not save bill.');
}
$created = (new Bill())->find((int) $inserted);
$this->writeAudit('create', 'bill', (int) $inserted, 'Created bill ' . ($created['bill_name'] ?? ''), null, $created);
```

```php
// app/Controllers/Auth.php (register success block)
$newUserId = $userModel->insert([
    'first_name' => $this->request->getPost('first_name'),
    'last_name' => $this->request->getPost('last_name'),
    'email' => $this->request->getPost('email'),
    'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
    'role_id' => $this->request->getPost('role_id'),
], true);

if ($newUserId === false) {
    return redirect()->back()->withInput()->with('error', 'Could not register user.');
}

$createdUser = $userModel->select('id, first_name, last_name, email, role_id')->find((int) $newUserId);
(new \App\Services\AuditTrailService())->log([
    'actor_user_id' => (int) (session()->get('user_id') ?? 0) ?: null,
    'actor_role' => (string) (session()->get('role') ?? ''),
    'module' => 'user_management',
    'action' => 'create',
    'entity_type' => 'user',
    'entity_id' => (string) $newUserId,
    'summary' => 'Registered user ' . (($createdUser['email'] ?? '')),
    'before_data' => null,
    'after_data' => $createdUser,
    'request_method' => $this->request->getMethod(),
    'request_path' => $this->request->getPath(),
    'ip_address' => $this->request->getIPAddress(),
]);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter AuditTrailFinancialAndUserLoggingTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/FinancialAnalyticsController.php app/Controllers/Auth.php tests/feature/AuditTrailFinancialAndUserLoggingTest.php
git commit -m "feat(audit): log financial and user-management mutations"
```

### Task 6: Integrate audit logging into Stock-In and Stock-Out mutation flows

**Files:**
- Create: `tests/feature/AuditTrailStockLoggingTest.php`
- Modify: `app/Controllers/StockInController.php`
- Modify: `app/Controllers/StockOutController.php`

- [ ] **Step 1: Write failing stock flow logging tests**

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuditTrailStockLoggingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
        $this->resetTables();
        $this->seedLookupRows();
    }

    public function testStockInWriteCreatesAuditEntry(): void
    {
        $this->withSession(['logged_in' => true, 'role' => 'Owner', 'user_id' => 1])->post('stockin', [
            'product_name' => 'Milk',
            'quantity' => 5,
            'category' => 'Dairy',
            'batch_number' => 'A-1',
            'expiration_date' => '2026-12-31',
            'capital' => '100.00',
            'stockin_date' => '2026-04-11',
            'sales_price' => '25.00',
            'unit_type' => 'Bottle',
        ])->assertRedirectTo('/stockin');

        $row = db_connect()->table('audit_trails')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertSame('stock_in', (string) ($row['module'] ?? ''));
        $this->assertContains((string) ($row['action'] ?? ''), ['create', 'update']);
    }

    public function testStockOutInventoryWriteCreatesAuditEntry(): void
    {
        $barcode = '2000000123455';
        $this->seedStockOutRows($barcode);

        $this->withSession(['logged_in' => true, 'role' => 'Owner', 'user_id' => 1])->post('stock-out/inventory', [
            'barcode' => $barcode,
            'quantity' => 1,
            'stock_out_date' => '2026-04-11',
            'reason' => 'Damaged',
        ])->assertRedirectTo('/stock-out/inventory');

        $row = db_connect()->table('audit_trails')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertSame('stock_out', (string) ($row['module'] ?? ''));
        $this->assertSame('create', (string) ($row['action'] ?? ''));
    }

    private function ensureTables(): void
    {
        $db = db_connect();
        $forge = Database::forge();

        if (!$db->tableExists('roles')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'role_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('roles', true);
        }

        if (!$db->tableExists('users')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'first_name' => ['type' => 'VARCHAR', 'constraint' => 30],
                'last_name' => ['type' => 'VARCHAR', 'constraint' => 30],
                'email' => ['type' => 'VARCHAR', 'constraint' => 255],
                'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
                'role_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('users', true);
        }

        if (!$db->tableExists('categories')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'category_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('categories', true);
        }

        if (!$db->tableExists('unit_types')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'unit_type_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('unit_types', true);
        }

        if (!$db->tableExists('stock_in')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'product_name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'quantity' => ['type' => 'INT', 'constraint' => 11],
                'stock_in_date' => ['type' => 'DATETIME'],
                'barcode' => ['type' => 'VARCHAR', 'constraint' => 255],
                'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'unit_type_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'recorded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('stock_in', true);
        }

        if (!$db->tableExists('product_batch')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'batch_number' => ['type' => 'VARCHAR', 'constraint' => 255],
                'expiration_date' => ['type' => 'DATETIME'],
                'stock_in_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('product_batch', true);
        }

        if (!$db->tableExists('capital')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'capital' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'date' => ['type' => 'DATETIME'],
                'stock_in_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('capital', true);
        }

        if (!$db->tableExists('products')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'stock_in_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('products', true);
        }

        if (!$db->tableExists('sales_price')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'sale_price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'effective_date' => ['type' => 'DATETIME'],
                'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('sales_price', true);
        }

        if (!$db->tableExists('reasons')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'reason_text' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('reasons', true);
        }

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

        if (!$db->tableExists('stock_out')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'batch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'quantity' => ['type' => 'INT', 'constraint' => 11],
                'unit_type_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'reason_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'receipt_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'capital_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'recorded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'stock_out_date' => ['type' => 'DATETIME'],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('stock_out', true);
        }

        if (!$db->tableExists('audit_trails')) {
            $forge->addField([
                'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'actor_role' => ['type' => 'VARCHAR', 'constraint' => 20],
                'module' => ['type' => 'VARCHAR', 'constraint' => 40],
                'action' => ['type' => 'VARCHAR', 'constraint' => 20],
                'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80],
                'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'summary' => ['type' => 'VARCHAR', 'constraint' => 255],
                'before_data' => ['type' => 'TEXT', 'null' => true],
                'after_data' => ['type' => 'TEXT', 'null' => true],
                'request_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
                'request_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('audit_trails', true);
        }
    }

    private function resetTables(): void
    {
        $db = db_connect();
        $db->table('audit_trails')->truncate();
        $db->table('stock_out')->truncate();
        $db->table('receipts')->truncate();
        $db->table('reasons')->truncate();
        $db->table('sales_price')->truncate();
        $db->table('products')->truncate();
        $db->table('capital')->truncate();
        $db->table('product_batch')->truncate();
        $db->table('stock_in')->truncate();
        $db->table('users')->truncate();
        $db->table('roles')->truncate();
        $db->table('categories')->truncate();
        $db->table('unit_types')->truncate();
    }

    private function seedLookupRows(): void
    {
        $db = db_connect();
        $db->table('roles')->insert(['id' => 1, 'role_name' => 'Owner']);
        $db->table('users')->insert([
            'id' => 1,
            'first_name' => 'Owner',
            'last_name' => 'User',
            'email' => 'owner@example.com',
            'password_hash' => password_hash('secret123', PASSWORD_DEFAULT),
            'role_id' => 1,
        ]);
        $db->table('categories')->insert(['id' => 1, 'category_name' => 'Dairy']);
        $db->table('unit_types')->insert(['id' => 1, 'unit_type_name' => 'Bottle']);
    }

    private function seedStockOutRows(string $barcode): void
    {
        $db = db_connect();
        $db->table('stock_in')->insert([
            'id' => 10,
            'product_name' => 'Milk',
            'quantity' => 5,
            'stock_in_date' => '2026-04-10 09:00:00',
            'barcode' => $barcode,
            'category_id' => 1,
            'unit_type_id' => 1,
            'recorded_by' => 1,
        ]);
        $db->table('product_batch')->insert([
            'id' => 100,
            'batch_number' => 'A-1',
            'expiration_date' => '2026-12-31 00:00:00',
            'stock_in_id' => 10,
        ]);
        $db->table('products')->insert(['id' => 50, 'stock_in_id' => 10]);
        $db->table('capital')->insert(['id' => 40, 'capital' => '100.00', 'date' => '2026-04-10 09:00:00', 'stock_in_id' => 10]);
        $db->table('sales_price')->insert(['id' => 60, 'sale_price' => '25.00', 'effective_date' => '2026-04-10 09:00:00', 'product_id' => 50]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test -- --no-coverage --filter AuditTrailStockLoggingTest`
Expected: FAIL because stock controllers do not write audit entries yet.

- [ ] **Step 3: Add explicit stock mutation audit writes**

```php
<?php
// app/Controllers/StockInController.php (after successful commit and before redirect)

$auditAction = $mergedToExisting ? 'update' : 'create';
$beforeData = $mergedToExisting ? ['quantity_before' => (int) $existingStockRow['quantity']] : null;
$afterData = $db->table('stock_in')->where('id', $stockInID)->get()->getRowArray();

(new \App\Services\AuditTrailService())->log([
    'actor_user_id' => (int) (session()->get('user_id') ?? 0) ?: null,
    'actor_role' => (string) (session()->get('role') ?? ''),
    'module' => 'stock_in',
    'action' => $auditAction,
    'entity_type' => 'stock_in',
    'entity_id' => (string) $stockInID,
    'summary' => $mergedToExisting ? 'Merged stock-in quantity for ' . $productName : 'Created stock-in entry for ' . $productName,
    'before_data' => $beforeData,
    'after_data' => $afterData,
    'request_method' => $this->request->getMethod(),
    'request_path' => $this->request->getPath(),
    'ip_address' => $this->request->getIPAddress(),
]);
```

```php
<?php
// app/Controllers/StockOutController.php (inside processStockOut loop after stockOut insert)

$auditPayload = [
    'product_id' => (int) $product['id'],
    'batch_id' => (int) $row['batch_id'],
    'quantity' => $take,
    'reason_id' => (int) $reason['id'],
    'receipt_id' => (int) $receiptId,
    'stock_out_date' => $stockOutDateTime,
];

(new \App\Services\AuditTrailService())->log([
    'actor_user_id' => $recordedBy,
    'actor_role' => (string) (session()->get('role') ?? ''),
    'module' => 'stock_out',
    'action' => 'create',
    'entity_type' => 'stock_out',
    'entity_id' => (string) $stockOutInserted,
    'summary' => 'Recorded stock-out for ' . $scannedBatch['product_name'] . ' qty ' . $take,
    'before_data' => null,
    'after_data' => $auditPayload,
    'request_method' => $this->request->getMethod(),
    'request_path' => $this->request->getPath(),
    'ip_address' => $this->request->getIPAddress(),
]);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test -- --no-coverage --filter AuditTrailStockLoggingTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/StockInController.php app/Controllers/StockOutController.php tests/feature/AuditTrailStockLoggingTest.php
git commit -m "feat(audit): log stock-in and stock-out mutation flows"
```

### Task 7: End-to-end verification and cleanup pass

**Files:**
- Modify: `app/Controllers/AuditTrailController.php` (final guard/validation polish)
- Modify: `tests/feature/AuditTrailFilterAndCsvExportTest.php` (final filter parity assertions)
- Modify: `tests/feature/AuditTrailPdfExportTest.php` (final export header assertions)

- [ ] **Step 1: Add final failing assertions for empty-export behavior**

```php
// tests/feature/AuditTrailFilterAndCsvExportTest.php (empty-result test)
public function testCsvExportWithNoRowsStillReturnsHeaderRow(): void
{
    db_connect()->table('audit_trails')->truncate();
    $result = $this->withSession(['logged_in' => true, 'role' => 'Owner', 'user_id' => 1])
        ->get('management/audit-trail/export-csv?module=inventory');

    $result->assertStatus(200);
    $result->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
}
```

- [ ] **Step 2: Run targeted test bundle**

Run: `composer test -- --no-coverage --filter "AuditTrail(Access|NavigationView|ServiceWrite|FilterAndCsvExport|PdfExport|FinancialAndUserLogging|StockLogging)"`
Expected: FAIL until final edge-case handling is complete.

- [ ] **Step 3: Implement final edge-case handling**

```php
<?php
// app/Controllers/AuditTrailController.php (validate filter inputs)

private function assertValidFilters(array $filters)
{
    if ($filters['date_from'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) {
        return redirect()->to('/management/audit-trail')->with('error', 'Invalid from-date filter.');
    }
    if ($filters['date_to'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) {
        return redirect()->to('/management/audit-trail')->with('error', 'Invalid to-date filter.');
    }
    return null;
}
```

- [ ] **Step 4: Run full repository tests**

Run: `composer test`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/AuditTrailController.php tests/feature/AuditTrailFilterAndCsvExportTest.php tests/feature/AuditTrailPdfExportTest.php tests/feature/AuditTrailAccessTest.php tests/feature/AuditTrailFinancialAndUserLoggingTest.php tests/feature/AuditTrailStockLoggingTest.php tests/unit/AuditTrailNavigationViewTest.php
git commit -m "test(audit): finalize export edge-cases and audit trail verification"
```
