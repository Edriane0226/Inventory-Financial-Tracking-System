<?php

namespace App\Models;

use CodeIgniter\Model;

class Categories extends Model
{
    protected $table      = 'categories';
    protected $primaryKey = 'id';
    protected $allowedFields = ['category_name'];
    protected $returnType = 'array';
    
}