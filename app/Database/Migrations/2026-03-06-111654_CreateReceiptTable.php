<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReceiptTable extends Migration
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
            'receipt_number' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('receipts');
    }

    public function down()
    {
        $this->forge->dropTable('receipts');
    }
}
