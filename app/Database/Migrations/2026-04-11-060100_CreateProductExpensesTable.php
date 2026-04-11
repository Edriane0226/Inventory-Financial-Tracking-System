<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductExpensesTable extends Migration
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
            'stock_in_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'expense_date' => [
                'type' => 'DATETIME',
            ],
            'source_label' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'stock-in-capital',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('stock_in_id', 'stock_in', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_expenses');
    }

    public function down()
    {
        $this->forge->dropTable('product_expenses');
    }
}
