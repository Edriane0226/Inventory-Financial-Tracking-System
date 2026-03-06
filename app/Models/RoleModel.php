<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\EnrollmentModel;

class RoleModel extends Model
{
    protected $table      = 'roles';
    protected $primaryKey = 'id';
    protected $allowedFields = ['role_name'];
    protected $returnType = 'array';
}