<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductBarcode extends Model
{
    protected $table = 'product_barcodes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'normalized_product_name',
        'category_id',
        'unit_type_id',
        'barcode',
    ];
    protected $useTimestamps = true;
}

