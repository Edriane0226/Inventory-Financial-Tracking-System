<?php

namespace App\Controllers;

use App\Models\AuditTrailModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class AuditTrailController extends BaseController
{
    public function index()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $filters = $this->readFilters();
        $invalid = $this->assertValidFilters($filters);
        if ($invalid !== null) {
            return $invalid;
        }
        $rows = $this->buildFilteredQuery($filters)
            ->orderBy('audit_trails.id', 'DESC')
            ->limit(200)
            ->findAll();

        return view('Reusables/menu') . view('management/audit_trail', [
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    public function exportCsv()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $filters = $this->readFilters();
        $invalid = $this->assertValidFilters($filters);
        if ($invalid !== null) {
            return $invalid;
        }
        $rows = $this->buildFilteredQuery($filters)
            ->orderBy('audit_trails.id', 'DESC')
            ->findAll();

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, [
            'id',
            'actor_user_id',
            'actor_name',
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
            'created_at',
        ]);

        foreach ($rows as $row) {
            $actorName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            if ($actorName === '') {
                $actorName = trim((string) ($row['actor_role'] ?? '') . ' #' . (string) ($row['actor_user_id'] ?? ''));
            }
            fputcsv($stream, [
                $row['id'] ?? '',
                $row['actor_user_id'] ?? '',
                $actorName,
                $row['actor_role'] ?? '',
                $row['module'] ?? '',
                $row['action'] ?? '',
                $row['entity_type'] ?? '',
                $row['entity_id'] ?? '',
                $row['summary'] ?? '',
                $row['before_data'] ?? '',
                $row['after_data'] ?? '',
                $row['request_method'] ?? '',
                $row['request_path'] ?? '',
                $row['created_at'] ?? '',
            ]);
        }

        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="audit-trail-' . date('YmdHis') . '.csv"')
            ->setBody($csv);
    }

    public function exportPdf()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $filters = $this->readFilters();
        $invalid = $this->assertValidFilters($filters);
        if ($invalid !== null) {
            return $invalid;
        }
        $rows = $this->buildFilteredQuery($filters)
            ->orderBy('audit_trails.id', 'DESC')
            ->findAll();

        $html = view('management/audit_trail_pdf', [
            'rows' => $rows,
            'filters' => $filters,
            'generatedAt' => date('Y-m-d H:i:s'),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $pdf = $dompdf->output();

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="audit-trail-' . date('YmdHis') . '.pdf"')
            ->setBody($pdf);
    }

    private function requireOwner()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        if ((string) session()->get('role') !== 'Owner') {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Audit Trail.');
        }

        return null;
    }

    private function readFilters(): array
    {
        return [
            'module' => trim((string) ($this->request->getGet('module') ?? '')),
            'action' => trim((string) ($this->request->getGet('action') ?? '')),
            'actor_user_id' => trim((string) ($this->request->getGet('actor_user_id') ?? '')),
            'date_from' => trim((string) ($this->request->getGet('date_from') ?? '')),
            'date_to' => trim((string) ($this->request->getGet('date_to') ?? '')),
        ];
    }

    private function buildFilteredQuery(array $filters): AuditTrailModel
    {
        $model = new AuditTrailModel();
        $model->select('audit_trails.*, users.first_name, users.last_name')
            ->join('users', 'users.id = audit_trails.actor_user_id', 'left');

        if ($filters['module'] !== '') {
            $model->where('audit_trails.module', $filters['module']);
        }

        if ($filters['action'] !== '') {
            $model->where('audit_trails.action', $filters['action']);
        }

        if ($filters['actor_user_id'] !== '') {
            $model->where('audit_trails.actor_user_id', (int) $filters['actor_user_id']);
        }

        if ($filters['date_from'] !== '') {
            $model->where('audit_trails.created_at >=', $filters['date_from'] . ' 00:00:00');
        }

        if ($filters['date_to'] !== '') {
            $model->where('audit_trails.created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        return $model;
    }

    private function assertValidFilters(array $filters)
    {
        if ($filters['date_from'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) {
            return redirect()->to('/management/audit-trail')->with('error', 'Invalid from-date filter.');
        }

        if ($filters['date_to'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) {
            return redirect()->to('/management/audit-trail')->with('error', 'Invalid to-date filter.');
        }

        return null;
    }
}
