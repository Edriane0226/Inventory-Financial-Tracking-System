<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTotalAmountToReceiptsTable extends Migration
{
    public function up()
    {
        $columnExists = $this->db->query("SHOW COLUMNS FROM `receipts` LIKE 'total_amount'")->getNumRows() > 0;

        if (!$columnExists) {
            $this->forge->addColumn('receipts', [
                'total_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                    'after' => 'receipt_number',
                ],
            ]);
        }
    }

    public function down()
    {
        $columnExists = $this->db->query("SHOW COLUMNS FROM `receipts` LIKE 'total_amount'")->getNumRows() > 0;

        if ($columnExists) {
            $this->forge->dropColumn('receipts', 'total_amount');
        }
    }
}
