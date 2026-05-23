<?php

namespace App\Models;

use CodeIgniter\Model;

class Receipt extends Model
{
    protected $table      = 'receipts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['receipt_number', 'total_amount', 'created_at'];
    protected $returnType = 'array';
}
