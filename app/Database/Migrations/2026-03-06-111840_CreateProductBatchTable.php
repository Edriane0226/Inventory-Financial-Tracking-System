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
            'expiration_date' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('product_batch');
    }

    public function down()
    {
        $this->forge->dropTable('product_batch');
    }
}
