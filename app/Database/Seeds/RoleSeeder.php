<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
            'role_name' => 'Owner',
            ],
            [
            'role_name' => 'Employee',
            ],
        ];
        $this->db->table('roles')->insertBatch($data);
    }
}
