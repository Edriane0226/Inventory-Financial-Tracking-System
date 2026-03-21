<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UnitTypeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['unit_type_name' => 'kg'],
            ['unit_type_name' => 'pc'],
            ['unit_type_name' => 'pcs'],
            ['unit_type_name' => 'box'],
        ];

        $this->db->table('unit_types')->insertBatch($data);
    }
}
