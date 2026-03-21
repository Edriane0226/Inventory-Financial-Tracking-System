<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductBatchTable extends Migration
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
            'batch_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'expiration_date' => [
                'type' => 'DATETIME',
            ],
            'stock_in_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('stock_in_id', 'stock_in', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_batch');
    }

    public function down()
    {
        $this->forge->dropTable('product_batch');
    }
}
