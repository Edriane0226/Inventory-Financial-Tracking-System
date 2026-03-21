<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCategoryIdToStockInTable extends Migration
{
    public function up()
    {
        $categoryColumnExists = $this->db->query("SHOW COLUMNS FROM `stock_in` LIKE 'category_id'")->getNumRows() > 0;

        if (!$categoryColumnExists) {
            $this->forge->addColumn('stock_in', [
                'category_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'barcode',
                ],
            ]);
        }
    }

    public function down()
    {
        $categoryColumnExists = $this->db->query("SHOW COLUMNS FROM `stock_in` LIKE 'category_id'")->getNumRows() > 0;

        if ($categoryColumnExists) {
            $this->forge->dropColumn('stock_in', 'category_id');
        }
    }
}
