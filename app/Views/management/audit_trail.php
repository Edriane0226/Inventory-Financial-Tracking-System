<?php
$rows = $rows ?? [];
$filters = $filters ?? [];
?>
<div class="container-fluid px-4 px-lg-5 py-4 py-lg-5">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h2 class="h4 mb-1">Audit Trail</h2>
           
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-2"><input class="form-control" name="module" value="<?= esc((string) ($filters['module'] ?? '')) ?>" placeholder="module"></div>
        <div class="col-md-2"><input class="form-control" name="action" value="<?= esc((string) ($filters['action'] ?? '')) ?>" placeholder="action"></div>
        <div class="col-md-2"><input class="form-control" name="actor_user_id" value="<?= esc((string) ($filters['actor_user_id'] ?? '')) ?>" placeholder="actor id"></div>
        <div class="col-md-2"><input class="form-control" type="date" name="date_from" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>"></div>
        <div class="col-md-2"><input class="form-control" type="date" name="date_to" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Apply Filters</button></div>
    </form>

    <div class="mb-3 d-flex gap-2">
        <a class="btn btn-outline-success btn-sm" href="<?= base_url('management/audit-trail/export-csv?' . http_build_query($filters)) ?>">Export CSV</a>
        <a class="btn btn-outline-danger btn-sm" href="<?= base_url('management/audit-trail/export-pdf?' . http_build_query($filters)) ?>">Export PDF</a>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
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
    </div>
</div>
