<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUnitTypeTable extends Migration
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
            'unit_type_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('unit_types');
    }

    public function down()
    {
        $this->forge->dropTable('unit_types');
    }
}
