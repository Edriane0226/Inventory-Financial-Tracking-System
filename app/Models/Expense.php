<?php

namespace App\Models;

use CodeIgniter\Model;

class Expense extends Model
{
    protected $table      = 'expenses';
    protected $primaryKey = 'id';
    protected $allowedFields = ['category_id', 'amount', 'note', 'expense_date', 'recorded_by'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
}

