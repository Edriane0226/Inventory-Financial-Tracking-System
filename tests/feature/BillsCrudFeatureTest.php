<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class BillsCrudFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureBillsTable();
        db_connect()->table('bills')->truncate();
    }

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

        $inserted = db_connect()->table('bills')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($inserted);
        $this->assertSame('Electric Bill', (string) ($inserted['bill_name'] ?? ''));
    }

    private function ensureBillsTable(): void
    {
        $db = db_connect();
        if ($db->tableExists('bills')) {
            return;
        }

        $forge = Database::forge();
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
}
