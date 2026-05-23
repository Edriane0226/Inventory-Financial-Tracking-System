<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class StockOutBarcodeLookupRegressionTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
        $this->resetTables();
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

    private function seedSharedBarcodeData(string $barcode): void
    {
        $db = db_connect();

        $db->table('roles')->insert([
            'id' => 1,
            'role_name' => 'Owner',
        ]);

        $db->table('users')->insert([
            'id' => 1,
            'first_name' => 'Owner',
            'last_name' => 'User',
            'email' => 'owner@example.com',
            'password_hash' => password_hash('secret123', PASSWORD_DEFAULT),
            'role_id' => 1,
        ]);

        $db->table('categories')->insert([
            'id' => 1,
            'category_name' => 'Dairy',
        ]);

        $db->table('unit_types')->insert([
            'id' => 1,
            'unit_type_name' => 'Bottle',
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

        $db->table('products')->insert([
            'id' => 90,
            'stock_in_id' => 10,
        ]);

        $db->table('products')->insert([
            'id' => 91,
            'stock_in_id' => 11,
        ]);

        $db->table('sales_price')->insert([
            'id' => 1,
            'sale_price' => '25.00',
            'effective_date' => '2026-04-01 08:00:00',
            'product_id' => 90,
        ]);

        $db->table('sales_price')->insert([
            'id' => 2,
            'sale_price' => '25.00',
            'effective_date' => '2026-04-02 08:00:00',
            'product_id' => 91,
        ]);
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
    }

    private function resetTables(): void
    {
        $db = db_connect();
        $db->table('sales_price')->truncate();
        $db->table('products')->truncate();
        $db->table('product_batch')->truncate();
        $db->table('stock_in')->truncate();
        $db->table('users')->truncate();
        $db->table('roles')->truncate();
        $db->table('categories')->truncate();
        $db->table('unit_types')->truncate();
    }
}

