<?php

namespace App\Services;

use App\Models\AuditTrailModel;
use RuntimeException;

class AuditTrailService
{
    private const MODULES = ['inventory', 'stock_in', 'stock_out', 'sales', 'financial', 'user_management'];
    private const ACTIONS = ['create', 'update', 'delete'];

    public function log(array $payload): int
    {
        $module = (string) ($payload['module'] ?? '');
        $action = (string) ($payload['action'] ?? '');
        if (!in_array($module, self::MODULES, true) || !in_array($action, self::ACTIONS, true)) {
            throw new RuntimeException('Invalid audit module or action.');
        }

        $beforeJson = $payload['before_data'] === null
            ? null
            : json_encode($payload['before_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $afterJson = $payload['after_data'] === null
            ? null
            : json_encode($payload['after_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (($payload['before_data'] !== null && $beforeJson === false) || ($payload['after_data'] !== null && $afterJson === false)) {
            throw new RuntimeException('Could not encode audit snapshot data.');
        }

        $inserted = (new AuditTrailModel())->insert([
            'actor_user_id' => $payload['actor_user_id'] ?? null,
            'actor_role' => (string) ($payload['actor_role'] ?? ''),
            'module' => $module,
            'action' => $action,
            'entity_type' => (string) ($payload['entity_type'] ?? ''),
            'entity_id' => isset($payload['entity_id']) ? (string) $payload['entity_id'] : null,
            'summary' => (string) ($payload['summary'] ?? ''),
            'before_data' => $beforeJson,
            'after_data' => $afterJson,
            'request_method' => $payload['request_method'] ?? null,
            'request_path' => $payload['request_path'] ?? null,
            'ip_address' => $payload['ip_address'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        if ($inserted === false) {
            throw new RuntimeException('Could not write audit trail entry.');
        }

        return (int) $inserted;
    }
}
