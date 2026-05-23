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
        $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('stockin', [
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
        $this->assertNotNull($row);
        $this->assertSame('stock_in', (string) ($row['module'] ?? ''));
        $this->assertContains((string) ($row['action'] ?? ''), ['create', 'update']);
    }

    public function testStockOutInventoryWriteCreatesAuditEntry(): void
    {
        $barcode = '2000000123455';
        $this->seedStockOutRows($barcode);

        $this->withSession([
            'logged_in' => true,
            'role' => 'Owner',
            'user_id' => 1,
        ])->post('stock-out/inventory', [
            'barcode' => $barcode,
            'quantity' => 1,
            'stock_out_date' => '2026-04-11',
            'reason' => 'Damaged',
        ])->assertRedirectTo('/stock-out/inventory');

        $row = db_connect()->table('audit_trails')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($row);
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

        if (!$db->tableExists('product_barcodes')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'normalized_product_name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'unit_type_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'barcode' => ['type' => 'CHAR', 'constraint' => 13],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->addUniqueKey(['normalized_product_name', 'category_id', 'unit_type_id'], 'uq_product_identity_feature');
            $forge->addUniqueKey('barcode', 'uq_product_barcode_feature');
            $forge->createTable('product_barcodes', true);
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
        $db->table('product_barcodes')->truncate();
        $db->table('product_expenses')->truncate();
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
        $db->table('products')->insert([
            'id' => 50,
            'stock_in_id' => 10,
        ]);
        $db->table('capital')->insert([
            'id' => 40,
            'capital' => '100.00',
            'date' => '2026-04-10 09:00:00',
            'stock_in_id' => 10,
        ]);
        $db->table('sales_price')->insert([
            'id' => 60,
            'sale_price' => '25.00',
            'effective_date' => '2026-04-10 09:00:00',
            'product_id' => 50,
        ]);
    }
}
