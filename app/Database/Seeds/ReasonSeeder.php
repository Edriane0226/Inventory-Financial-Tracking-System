<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReasonSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['reason_text' => 'Damaged'],
            ['reason_text' => 'Expired'],
            ['reason_text' => 'Theft'],
            ['reason_text' => 'Loss'],
            ['reason_text' => 'Other'],
            ['reason_text' => 'Sold'],
        ];

        foreach ($data as $reason) {
            $this->db->table('reasons')->insert($reason);
        }
    }
}
