<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductExpense extends Model
{
    protected $table = 'product_expenses';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['stock_in_id', 'amount', 'expense_date', 'source_label', 'created_at'];
    protected $useTimestamps = false;
}
