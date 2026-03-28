<?php

namespace App\Controllers;

class InventoryController extends BaseController
{
    public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $role = session()->get('role');
        if (!in_array($role, ['Owner', 'Employee'], true)) {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Products.');
        }

        $db = \Config\Database::connect();

        $productSummary = $db->table('stock_in si')
            ->select("si.product_name, SUM(si.quantity) AS total_quantity, MAX(si.stock_in_date) AS last_stock_in_date, GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ') AS categories, GROUP_CONCAT(DISTINCT ut.unit_type_name ORDER BY ut.unit_type_name SEPARATOR ', ') AS unit_types", false)
            ->join('categories c', 'c.id = si.category_id', 'left')
            ->join('unit_types ut', 'ut.id = si.unit_type_id', 'left')
            ->groupBy('si.product_name')
            ->orderBy('si.product_name', 'ASC')
            ->get()
            ->getResultArray();

        $totalProducts = count($productSummary);
        $totalQuantity = 0;

        foreach ($productSummary as $row) {
            $totalQuantity += (int) ($row['total_quantity'] ?? 0);
        }

        $data = [
            'productSummary' => $productSummary,
            'totalProducts' => $totalProducts,
            'totalQuantity' => $totalQuantity,
        ];

        return view('Reusables/menu') . view('inventory/dashboard', $data);
    }
}
