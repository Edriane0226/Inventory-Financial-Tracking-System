<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class FinancialAnalyticsBreakdownEndpointTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
        $this->resetTables();
        $this->seedRows();
    }

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

    public function testBreakdownOwnerGetsDailyRowsAndSubtotals(): void
    {
        $response = $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->get('financial/breakdown?period=daily');

        $response->assertStatus(200);
        $json = json_decode((string) $response->getJSON(), true);

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
}
