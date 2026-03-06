<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\EnrollmentModel;
use App\Models\RoleModel;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['first_name', 'last_name', 'email', 'password_hash', 'role_id'];
    protected $returnType = 'array';

    public function getUsersfirstNameLastNameEmailRole()
    {
        return $this->select('users.first_name, users.last_name, users.email, roles.role_name')
                    ->join('roles', 'users.role_id = roles.id')
                    ->findAll();
    }

    public function getUserWithRole($user_id)
    {
        return $this->select('users.*, roles.role_name')
                    ->join('roles', 'users.role_id = roles.id')
                    ->where('users.id', $user_id)
                    ->first();
    }

    public function getRoleNameByRoleId($role_id)
    {
        $roleModel = new RoleModel();
        $role = $roleModel->find($role_id);
        return $role ? $role['role_name'] : null;
    }
}