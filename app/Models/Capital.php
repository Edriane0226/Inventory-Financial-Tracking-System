<?php

namespace App\Models;

use CodeIgniter\Model;

class Capital extends Model
{
    protected $table      = 'capital';
    protected $primaryKey = 'id';
    protected $allowedFields = ['capital', 'effective_date', 'stock_in_id'];
    protected $returnType = 'array';
    
}