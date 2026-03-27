<?php

namespace App\Models;

use CodeIgniter\Model;

class Receipt extends Model
{
    protected $table      = 'receipts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['receipt_number'];
    protected $returnType = 'array';

}