<?php

namespace App\Models;

use CodeIgniter\Model;

class Bill extends Model
{
    protected $table = 'bills';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['bill_name', 'amount', 'bill_date', 'due_date', 'status', 'notes', 'recorded_by'];
    protected $useTimestamps = true;
}
