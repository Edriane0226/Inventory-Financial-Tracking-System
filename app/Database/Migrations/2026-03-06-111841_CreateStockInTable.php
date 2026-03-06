<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockInTable extends Migration
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
            'product_batch_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'stock_in_date' => [
                'type' => 'DATETIME',
            ],
            'capital_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'barcode' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'unit_type_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'recorded_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_batch_id', 'product_batch', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_type_id', 'unit_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('recorded_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('stock_in');
    }

    public function down()
    {
        $this->forge->dropTable('stock_in');
    }
}
