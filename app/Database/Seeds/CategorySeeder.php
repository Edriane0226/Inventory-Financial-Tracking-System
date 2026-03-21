<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['category_name' => 'Electronics'],
            ['category_name' => 'Apparel & Accessories'],
            ['category_name' => 'Home & Garden'],
            ['category_name' => 'Health & Beauty'],
            ['category_name' => 'Food & Beverages'],
            ['category_name' => 'Frozen Goods'],
            ['category_name' => 'Etc'],
        ];

        $this->db->table('categories')->insertBatch($data);
    }
}
