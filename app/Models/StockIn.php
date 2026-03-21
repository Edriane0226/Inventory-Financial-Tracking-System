<?php

namespace App\Models;

use CodeIgniter\Model;


class StockIn extends Model
{
    protected $table      = 'stock_in';
    protected $primaryKey = 'id';
    protected $allowedFields = ['product_batch_id', 
                                'quantity', 
                                'stock_in_date', 
                                'capital_id', 
                                'barcode', 
                                'unit_type_id', 
                                'category_id', 
                                'recorded_by'];
    protected $returnType = 'array';
    
}