<?php

use App\Models\ProductBarcode;
use App\Services\BarcodeService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class ProductBarcodeRegistryTest extends CIUnitTestCase
{
    protected \CodeIgniter\Database\BaseConnection $testDb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = db_connect();
        $this->ensureTables();
        $this->testDb->table('product_barcodes')->truncate();
        $this->testDb->table('categories')->truncate();
        $this->testDb->table('unit_types')->truncate();
        $this->insertLookupRows();
    }

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

    private function insertLookupRows(): void
    {
        $this->testDb->table('categories')->insert([
            'id' => 1,
            'category_name' => 'Dairy',
        ]);
        $this->testDb->table('unit_types')->insert([
            'id' => 1,
            'unit_type_name' => 'Bottle',
        ]);
    }

    private function ensureTables(): void
    {
        $forge = Database::forge();

        if (!$this->testDb->tableExists('categories')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'category_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('categories', true);
        }

        if (!$this->testDb->tableExists('unit_types')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'unit_type_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('unit_types', true);
        }

        if (!$this->testDb->tableExists('product_barcodes')) {
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
            $forge->addUniqueKey(['normalized_product_name', 'category_id', 'unit_type_id'], 'uq_product_identity_test');
            $forge->addUniqueKey('barcode', 'uq_product_barcode_test');
            $forge->createTable('product_barcodes', true);
        }
    }
}

