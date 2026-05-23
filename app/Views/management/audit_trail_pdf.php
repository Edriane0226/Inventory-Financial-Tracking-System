<?php
$rows = $rows ?? [];
$filters = $filters ?? [];
$generatedAt = $generatedAt ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin: 0 0 8px; }
        p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cfd4da; padding: 6px; vertical-align: top; }
        th { background-color: #f2f4f6; text-align: left; }
    </style>
</head>
<body>
    <h2>Audit Trail Report</h2>
    <p><strong>Generated:</strong> <?= esc((string) $generatedAt) ?></p>

    <table>
        <thead>
            <tr>
                <th>When</th>
                <th>Module</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Summary</th>
                <th>Actor</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
                $actorName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                if ($actorName === '') {
                    $actorName = trim((string) ($row['actor_role'] ?? '') . ' #' . (string) ($row['actor_user_id'] ?? ''));
                }
            ?>
            <tr>
                <td><?= esc((string) ($row['created_at'] ?? '')) ?></td>
                <td><?= esc((string) ($row['module'] ?? '')) ?></td>
                <td><?= esc((string) ($row['action'] ?? '')) ?></td>
                <td><?= esc((string) (($row['entity_type'] ?? '') . ':' . ($row['entity_id'] ?? ''))) ?></td>
                <td><?= esc((string) ($row['summary'] ?? '')) ?></td>
                <td><?= esc($actorName) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
