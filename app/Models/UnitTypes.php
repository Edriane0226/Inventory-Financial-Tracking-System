<?php

namespace App\Models;

use CodeIgniter\Model;

class UnitTypes extends Model
{
    protected $table      = 'unit_types';
    protected $primaryKey = 'id';
    protected $allowedFields = ['unit_type_name'];
    protected $returnType = 'array';


    public function getIdbyUnitTypeName($unit_type_name)
    {
        return $this->where('unit_type_name', $unit_type_name)->first();
    }
    
}