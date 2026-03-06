<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReasonTable extends Migration
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
            'reason_text' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('reasons');
    }

    public function down()
    {
        $this->forge->dropTable('reasons');
    }
}
