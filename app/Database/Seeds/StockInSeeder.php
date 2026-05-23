<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StockInSeeder extends Seeder
{
    public function run()
    {
        if (!$this->db->tableExists('stock_in')) {
            return;
        }

        $categoryIds = $this->pluckIds('categories');
        $unitTypeIds = $this->pluckIds('unit_types');
        $userIds = $this->pluckIds('users');

        if ($categoryIds === [] || $unitTypeIds === [] || $userIds === []) {
            return;
        }

        $hasCapital = $this->db->tableExists('capital');
        $hasProducts = $this->db->tableExists('products');
        $hasSalesPrice = $this->db->tableExists('sales_price');

        $pick = static fn(array $ids, int $index): int => (int) ($ids[$index] ?? $ids[0]);
        $now = new \DateTimeImmutable('now');

        $rows = [
            [
                'product_name' => 'Whole Milk 1L',
                'quantity' => 120,
                'stock_in_date' => $now->modify('-2 days')->setTime(9, 15, 0)->format('Y-m-d H:i:s'),
                'barcode' => 'MILK1L-0001',
                'category_id' => $pick($categoryIds, 4),
                'unit_type_id' => $pick($unitTypeIds, 2),
                'recorded_by' => $pick($userIds, 0),
                'capital' => 7200.00,
                'sale_price' => 85.00,
            ],
            [
                'product_name' => 'Instant Noodles 60g',
                'quantity' => 300,
                'stock_in_date' => $now->modify('-5 days')->setTime(14, 30, 0)->format('Y-m-d H:i:s'),
                'barcode' => 'NOODLE60-0007',
                'category_id' => $pick($categoryIds, 4),
                'unit_type_id' => $pick($unitTypeIds, 1),
                'recorded_by' => $pick($userIds, 0),
                'capital' => 5400.00,
                'sale_price' => 25.00,
            ],
            [
                'product_name' => 'LED Bulb 9W',
                'quantity' => 80,
                'stock_in_date' => $now->modify('-7 days')->setTime(11, 5, 0)->format('Y-m-d H:i:s'),
                'barcode' => 'LEDBULB9W-0021',
                'category_id' => $pick($categoryIds, 0),
                'unit_type_id' => $pick($unitTypeIds, 1),
                'recorded_by' => $pick($userIds, 1),
                'capital' => 6400.00,
                'sale_price' => 120.00,
            ],
            [
                'product_name' => 'Laundry Detergent 1kg',
                'quantity' => 60,
                'stock_in_date' => $now->modify('-9 days')->setTime(8, 40, 0)->format('Y-m-d H:i:s'),
                'barcode' => 'DETERGENT1K-0004',
                'category_id' => $pick($categoryIds, 3),
                'unit_type_id' => $pick($unitTypeIds, 0),
                'recorded_by' => $pick($userIds, 0),
                'capital' => 4800.00,
                'sale_price' => 110.00,
            ],
            [
                'product_name' => 'Garden Hose 10m',
                'quantity' => 25,
                'stock_in_date' => $now->modify('-12 days')->setTime(16, 20, 0)->format('Y-m-d H:i:s'),
                'barcode' => 'HOSE10M-0010',
                'category_id' => $pick($categoryIds, 2),
                'unit_type_id' => $pick($unitTypeIds, 3),
                'recorded_by' => $pick($userIds, 1),
                'capital' => 3750.00,
                'sale_price' => 250.00,
            ],
        ];

        $this->db->transStart();

        foreach ($rows as $row) {
            $stockInData = $row;
            unset($stockInData['capital'], $stockInData['sale_price']);

            $this->db->table('stock_in')->insert($stockInData);
            $stockInId = (int) $this->db->insertID();

            if ($stockInId <= 0) {
                continue;
            }

            if ($hasCapital) {
                $this->db->table('capital')->insert([
                    'capital' => (float) $row['capital'],
                    'date' => $stockInData['stock_in_date'],
                    'stock_in_id' => $stockInId,
                ]);
            }

            if ($hasProducts) {
                $this->db->table('products')->insert([
                    'stock_in_id' => $stockInId,
                ]);

                $productId = (int) $this->db->insertID();
                if ($hasSalesPrice && $productId > 0) {
                    $this->db->table('sales_price')->insert([
                        'sale_price' => (float) $row['sale_price'],
                        'effective_date' => $stockInData['stock_in_date'],
                        'product_id' => $productId,
                    ]);
                }
            }
        }

        $this->db->transComplete();
    }

    private function pluckIds(string $table): array
    {
        if (!$this->db->tableExists($table)) {
            return [];
        }

        $rows = $this->db->table($table)->select('id')->orderBy('id', 'ASC')->get()->getResultArray();

        return array_map(static fn(array $row): int => (int) $row['id'], $rows);
    }
}
