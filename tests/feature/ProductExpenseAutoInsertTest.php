<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class ProductExpenseAutoInsertTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
        $this->resetTables();
        $this->seedLookupRows();
    }

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

    private function ensureTables(): void
    {
        $db = db_connect();
        $forge = Database::forge();

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
    }

    private function resetTables(): void
    {
        $db = db_connect();
        $db->table('product_expenses')->truncate();
        $db->table('sales_price')->truncate();
        $db->table('products')->truncate();
        $db->table('capital')->truncate();
        $db->table('product_batch')->truncate();
        $db->table('product_barcodes')->truncate();
        $db->table('stock_in')->truncate();
        $db->table('categories')->truncate();
        $db->table('unit_types')->truncate();
    }

    private function seedLookupRows(): void
    {
        $db = db_connect();

        $db->table('categories')->insert([
            'id' => 1,
            'category_name' => 'Dairy',
        ]);

        $db->table('unit_types')->insert([
            'id' => 1,
            'unit_type_name' => 'Bottle',
        ]);
    }
}
