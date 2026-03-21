<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCapitalTable extends Migration
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
            'capital' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'date' => [
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
        $this->forge->createTable('capital');
    }

    public function down()
    {
        $this->forge->dropTable('capital');
    }
}
