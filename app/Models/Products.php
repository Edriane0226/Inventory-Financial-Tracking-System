<?php

namespace App\Models;

use CodeIgniter\Model;

class Products extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $allowedFields = ['stock_in_id'];
    protected $returnType = 'array';

    public function getIdbyStockInID($stockInID)
    {
        return $this->where('stock_in_id', $stockInID)->first();
    }
}