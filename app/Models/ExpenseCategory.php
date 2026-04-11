<?php

namespace App\Models;

use CodeIgniter\Model;

class ExpenseCategory extends Model
{
    protected $table      = 'expense_categories';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'is_active'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
}

