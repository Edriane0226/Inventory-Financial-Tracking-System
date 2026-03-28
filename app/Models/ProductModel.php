<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'sku',
        'category',
        'stock_quantity',
        'minimum_stock',
        'price',
        'updated_at',
    ];

    public function getAllProducts(string $search = '', string $statusFilter = 'all'): array
    {
        $defaultMinimumStock = 5;
        $minimumStockExpression = $this->db->fieldExists('minimum_stock', 'products')
            ? 'COALESCE(p.minimum_stock, ' . $defaultMinimumStock . ')'
            : (string) $defaultMinimumStock;

        $latestSalesPriceSubquery = '(SELECT sp1.product_id, sp1.sale_price
            FROM sales_price sp1
            INNER JOIN (
                SELECT product_id, MAX(id) AS max_id
                FROM sales_price
                GROUP BY product_id
            ) sp2 ON sp1.id = sp2.max_id
        ) sp_latest';

        $latestStockOutDateSubquery = '(SELECT product_id, MAX(stock_out_date) AS latest_stock_out_date
            FROM stock_out
            GROUP BY product_id
        ) so_latest';

        $builder = $this->db->table($this->table . ' p');
        $builder->select([
            'p.id AS id',
            'COALESCE(si.product_name, CONCAT("Product #", p.id)) AS name',
            'COALESCE(si.barcode, CONCAT("SKU-", p.id)) AS sku',
            'COALESCE(c.category_name, "Uncategorized") AS category',
            'COALESCE(si.quantity, 0) AS stock_quantity',
            $minimumStockExpression . ' AS minimum_stock',
            'COALESCE(sp_latest.sale_price, 0) AS price',
            'COALESCE(so_latest.latest_stock_out_date, si.stock_in_date) AS updated_at',
        ], false);

        $builder->join('stock_in si', 'si.id = p.stock_in_id', 'left');
        $builder->join('categories c', 'c.id = si.category_id', 'left');
        $builder->join($latestSalesPriceSubquery, 'sp_latest.product_id = p.id', 'left', false);
        $builder->join($latestStockOutDateSubquery, 'so_latest.product_id = p.id', 'left', false);

        if ($search !== '') {
            $builder->groupStart()
                ->like('si.product_name', $search)
                ->orLike('si.barcode', $search)
                ->groupEnd();
        }

        $normalizedStatus = strtolower(trim($statusFilter));
        if ($normalizedStatus === 'in_stock') {
            $builder->where('COALESCE(si.quantity, 0) > ' . $minimumStockExpression, null, false);
        } elseif ($normalizedStatus === 'low_stock') {
            $builder->where('COALESCE(si.quantity, 0) <= ' . $minimumStockExpression, null, false)
                ->where('COALESCE(si.quantity, 0) >', 0, false);
        } elseif ($normalizedStatus === 'out_of_stock') {
            $builder->where('COALESCE(si.quantity, 0)', 0);
        }

        return $builder
            ->orderBy('stock_quantity', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getStockStatus(int $stockQuantity, int $minimumStock): string
    {
        if ($stockQuantity > $minimumStock) {
            return 'In Stock';
        }

        if ($stockQuantity > 0) {
            return 'Low Stock';
        }

        return 'Out of Stock';
    }
}
