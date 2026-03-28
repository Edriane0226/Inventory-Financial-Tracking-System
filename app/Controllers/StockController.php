<?php

namespace App\Controllers;

use App\Models\ProductModel;

class StockController extends BaseController
{
    public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $role = session()->get('role');
        if (!in_array($role, ['Owner', 'Employee'], true)) {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Stock Levels.');
        }

        $search = trim((string) $this->request->getGet('search'));
        $status = strtolower(trim((string) $this->request->getGet('status')));

        $productModel = new ProductModel();
        $allProducts = $productModel->getAllProducts();
        $products = $productModel->getAllProducts($search, $status);

        $decorate = static function (array $rows) use ($productModel): array {
            foreach ($rows as &$row) {
                $stockQuantity = (int) ($row['stock_quantity'] ?? 0);
                $minimumStock = (int) ($row['minimum_stock'] ?? 0);
                $price = (float) ($row['price'] ?? 0);

                $row['stock_status'] = $productModel->getStockStatus($stockQuantity, $minimumStock);
                $row['total_value'] = $stockQuantity * $price;
            }
            unset($row);

            return $rows;
        };

        $allProducts = $decorate($allProducts);
        $products = $decorate($products);

        $summary = [
            'total_products' => count($allProducts),
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
        ];

        foreach ($allProducts as $product) {
            $statusLabel = $product['stock_status'] ?? 'Out of Stock';

            if ($statusLabel === 'In Stock') {
                $summary['in_stock']++;
                continue;
            }

            if ($statusLabel === 'Low Stock') {
                $summary['low_stock']++;
                continue;
            }

            $summary['out_of_stock']++;
        }

        return view('Reusables/menu') . view('inventory/stock_levels', [
            'products' => $products,
            'summary' => $summary,
            'search' => $search,
            'status' => $status,
        ]);
    }
}
