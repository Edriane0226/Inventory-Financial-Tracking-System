<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedAtToReceiptsTable extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('created_at', 'receipts')) {
            $this->forge->addColumn('receipts', [
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }

        $this->db->table('receipts')
            ->set('created_at', date('Y-m-d H:i:s'))
            ->where('created_at IS NULL', null, false)
            ->update();
    }

    public function down()
    {
        if ($this->db->fieldExists('created_at', 'receipts')) {
            $this->forge->dropColumn('receipts', 'created_at');
        }
    }
}

