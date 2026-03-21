<?php

namespace App\Models;

use CodeIgniter\Model;

class SalesPrice extends Model
{
    protected $table      = 'sales_price';
    protected $primaryKey = 'id';
    protected $allowedFields = ['sale_price', 'effective_date', 'product_id'];
    protected $returnType = 'array';
    

    public function getIdbyProductID($product_id)
    {
        return $this->where('product_id', $product_id)->first();
    }
}