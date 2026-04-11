<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductBarcodesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'normalized_product_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'category_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'unit_type_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'barcode' => [
                'type' => 'CHAR',
                'constraint' => 13,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['normalized_product_name', 'category_id', 'unit_type_id'], 'uq_product_identity');
        $this->forge->addUniqueKey('barcode', 'uq_product_barcode');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_type_id', 'unit_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_barcodes');
    }

    public function down()
    {
        $this->forge->dropTable('product_barcodes');
    }
}

