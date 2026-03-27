<?php

namespace App\Models;

use CodeIgniter\Model;

class Reason extends Model
{
    protected $table      = 'reasons';
    protected $primaryKey = 'id';
    protected $allowedFields = ['reason_text'];
    protected $returnType = 'array';

    public function getIdbyReasonText($reason_text)
    {
        return $this->where('reason_text', $reason_text)->first();
    }
}