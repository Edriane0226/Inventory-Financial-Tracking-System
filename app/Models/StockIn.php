<?php

namespace App\Models;

use CodeIgniter\Model;


class StockIn extends Model
{
    protected $table      = 'stock_in';
    protected $primaryKey = 'id';
    protected $allowedFields = [
                                'product_name',
                                'product_batch_id', 
                                'quantity', 
                                'stock_in_date', 
                                'barcode', 
                                'unit_type_id', 
                                'category_id', 
                                'recorded_by'];
    protected $returnType = 'array';


    public function getStockInIDWithProductName($product_name)
    {
        return $this->where('product_name', $product_name)->first();
    }
    
}