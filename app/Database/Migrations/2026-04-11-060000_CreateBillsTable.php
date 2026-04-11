<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillsTable extends Migration
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
            'bill_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'bill_date' => [
                'type' => 'DATE',
            ],
            'due_date' => [
                'type' => 'DATE',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'unpaid',
            ],
            'notes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'recorded_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
        $this->forge->addForeignKey('recorded_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bills');
    }

    public function down()
    {
        $this->forge->dropTable('bills');
    }
}
