<?php

namespace App\Models;

use CodeIgniter\Model;

class Products extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = ['product_name', 'sales_price_id', 'stockin_id'];
    protected $returnType = 'array';
}