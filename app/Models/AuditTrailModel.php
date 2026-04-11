<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditTrailModel extends Model
{
    protected $table = 'audit_trails';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'actor_user_id',
        'actor_role',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'summary',
        'before_data',
        'after_data',
        'request_method',
        'request_path',
        'ip_address',
        'created_at',
    ];
    protected $useTimestamps = false;
}
