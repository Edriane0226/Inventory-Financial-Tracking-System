<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'first_name' => 'Edriane',
                'last_name' => 'Bangonon',
                'email' => 'edriane@gmail.com',
                'password_hash' => password_hash('12345678', PASSWORD_DEFAULT),
                'role_id' => 1, // Owner
            ],
            [
                'first_name' => 'Adrienne',
                'last_name' => 'Marinay',
                'email' => 'adriennemarinay@gmail.com',
                'password_hash' => password_hash('12345678', PASSWORD_DEFAULT),
                'role_id' => 2, // Employee
            ]
        ];

        // Insert data into the database
        $this->db->table('users')->insertBatch($data);
    }
}
