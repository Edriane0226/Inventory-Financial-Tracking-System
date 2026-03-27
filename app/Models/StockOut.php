<?php

namespace App\Models;

use CodeIgniter\Model;
class StockOut extends Model
{
    protected $table      = 'stock_out';
    protected $primaryKey = 'id';
    protected $allowedFields = [
                                'product_id',
                                'batch_id',
                                'quantity',
                                'unit_type_id',
                                'reason_id',
                                'receipt_id',
                                'capital_id',
                                'category_id',
                                'recorded_by',
                                'stock_out_date'
                            ];
    protected $returnType = 'array';

}