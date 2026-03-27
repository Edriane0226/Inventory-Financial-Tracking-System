<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('ReasonSeeder');
        $this->call('UnitTypeSeeder');
        $this->call('CategorySeeder');
        $this->call('RoleSeeder');
        $this->call('UserSeeder');
    }
}
