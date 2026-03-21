<?php

namespace App\Models;

use CodeIgniter\Model;

class UnitTypes extends Model
{
    protected $table      = 'unit_types';
    protected $primaryKey = 'id';
    protected $allowedFields = ['unit_type_name'];
    protected $returnType = 'array';
    
}