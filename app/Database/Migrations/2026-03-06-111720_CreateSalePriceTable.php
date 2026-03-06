<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSalePriceTable extends Migration
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
            'sale_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'effective_date' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('sales_price');
    }

    public function down()
    {
        $this->forge->dropTable('sales_price');
    }
}
