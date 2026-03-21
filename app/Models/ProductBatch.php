<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductBatch extends Model
{
    protected $table      = 'product_batch';
    protected $primaryKey = 'id';
    protected $allowedFields = ['batch_number', 'expiration_date'];
}