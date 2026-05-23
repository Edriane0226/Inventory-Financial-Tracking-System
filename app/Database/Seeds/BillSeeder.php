<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BillSeeder extends Seeder
{
    public function run()
    {
        $userIds = $this->pluckIds('users');
        if ($userIds === []) {
            return;
        }

        $userId = (int) $userIds[0];
        $now = new \DateTimeImmutable('now');

        $billDate = $now->modify('-10 days');
        $waterDate = $now->modify('-18 days');
        $rentDate = $now->modify('-25 days');
        $internetDate = $now->modify('-3 days');
        $maintenanceDate = $now->modify('-15 days');

        $rows = [
            [
                'bill_name' => 'Electricity Bill',
                'amount' => 3540.75,
                'bill_date' => $billDate->format('Y-m-d'),
                'due_date' => $billDate->modify('+20 days')->format('Y-m-d'),
                'status' => 'paid',
                'notes' => 'March usage',
                'recorded_by' => $userId,
                'created_at' => $billDate->setTime(10, 0, 0)->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                'bill_name' => 'Water Bill',
                'amount' => 1280.25,
                'bill_date' => $waterDate->format('Y-m-d'),
                'due_date' => $waterDate->modify('+15 days')->format('Y-m-d'),
                'status' => 'paid',
                'notes' => 'Main branch meter',
                'recorded_by' => $userId,
                'created_at' => $waterDate->setTime(9, 10, 0)->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                'bill_name' => 'Store Rent',
                'amount' => 18000.00,
                'bill_date' => $rentDate->format('Y-m-d'),
                'due_date' => $rentDate->modify('+5 days')->format('Y-m-d'),
                'status' => 'paid',
                'notes' => 'Monthly rental',
                'recorded_by' => $userId,
                'created_at' => $rentDate->setTime(8, 0, 0)->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                'bill_name' => 'Internet Service',
                'amount' => 2499.00,
                'bill_date' => $internetDate->format('Y-m-d'),
                'due_date' => $internetDate->modify('+10 days')->format('Y-m-d'),
                'status' => 'unpaid',
                'notes' => 'Fiber 300 Mbps',
                'recorded_by' => $userId,
                'created_at' => $internetDate->setTime(13, 30, 0)->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                'bill_name' => 'Equipment Maintenance',
                'amount' => 3200.00,
                'bill_date' => $maintenanceDate->format('Y-m-d'),
                'due_date' => $maintenanceDate->modify('+7 days')->format('Y-m-d'),
                'status' => 'unpaid',
                'notes' => 'Quarterly service',
                'recorded_by' => $userId,
                'created_at' => $maintenanceDate->setTime(15, 45, 0)->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('bills')->insertBatch($rows);
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
