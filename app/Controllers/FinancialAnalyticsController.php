<?php

namespace App\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Bill;
use App\Models\ProductExpense;
use App\Services\AuditTrailService;

class FinancialAnalyticsController extends BaseController
{
    public function index()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $windows = $this->dateWindows();
        $metrics = [
            'daily' => $this->buildMetrics($windows['daily_start'], $windows['daily_end']),
            'weekly' => $this->buildMetrics($windows['weekly_start'], $windows['weekly_end']),
            'monthly' => $this->buildMetrics($windows['monthly_start'], $windows['monthly_end']),
        ];

        return view('Reusables/menu') . view('financial/dashboard', [
            'metrics' => $metrics,
            'windows' => $windows,
        ]);
    }

    public function exportMonthlyCsv()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $year = (int) $this->request->getGet('year');
        $month = (int) $this->request->getGet('month');
        if ($year < 2000 || $month < 1 || $month > 12) {
            return redirect()->to('/financial')->with('error', 'Invalid export period.');
        }

        [$start, $end] = $this->monthRange($year, $month);
        $metrics = $this->buildMetrics($start, $end);

        $period = \DateTimeImmutable::createFromFormat('!Y-n', $year . '-' . $month);
        $periodLabel = $period ? $period->format('F Y') : sprintf('%04d-%02d', $year, $month);

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Financial Statement']);
        fputcsv($stream, ['Period', $periodLabel]);
        fputcsv($stream, []);
        fputcsv($stream, ['Year', 'Month', 'Revenue', 'Expenses', 'Net Income', 'Profit Margin (%)']);
        fputcsv($stream, [
            $year,
            $month,
            number_format($metrics['revenue'], 2, '.', ''),
            number_format($metrics['expenses'], 2, '.', ''),
            number_format($metrics['net_income'], 2, '.', ''),
            number_format($metrics['profit_margin'], 2, '.', ''),
        ]);
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader(
                'Content-Disposition',
                'attachment; filename="financial-statement-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.csv"'
            )
            ->setBody($csv);
    }

    public function breakdown()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $period = trim((string) $this->request->getGet('period'));
        if ($period === '') {
            $period = 'today';
        }
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'today', 'date', 'month', 'all', 'batch'], true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Invalid period.',
            ]);
        }

        $range = $this->resolveBreakdownRange($period);
        if ($range === null) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Invalid period filter.',
            ]);
        }

        [$from, $to, $label] = $range;
        $receiptRows = $this->receiptRows($from, $to);
        $paidBillRows = $this->paidBillRows($from, $to);
        $productExpenseRows = $this->productExpenseRows($from, $to);

        if ($period === 'batch') {
            $receiptRows = [];
            $paidBillRows = [];
            $productExpenseRows = $this->filterBatchExpenseRows($productExpenseRows);
        }

        $receiptSubtotal = array_sum(array_map(static fn(array $row): float => (float) ($row['total_amount'] ?? 0), $receiptRows));
        $paidBillSubtotal = array_sum(array_map(static fn(array $row): float => (float) ($row['amount'] ?? 0), $paidBillRows));
        $productExpenseSubtotal = array_sum(array_map(static fn(array $row): float => (float) ($row['amount'] ?? 0), $productExpenseRows));

        $revenue = round($receiptSubtotal, 2);
        $expenses = round($paidBillSubtotal + $productExpenseSubtotal, 2);
        $netIncome = round($revenue - $expenses, 2);
        $profitMargin = $revenue > 0 ? round(($netIncome / $revenue) * 100, 2) : 0.0;
        $rows = $this->mergeBreakdownRows($receiptRows, $paidBillRows, $productExpenseRows);

        return $this->response->setJSON([
            'period' => $period,
            'label' => $label,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'totals' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_income' => $netIncome,
                'profit_margin' => $profitMargin,
                'row_count' => count($rows),
            ],
        ]);
    }

    public function expenses()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $activeTab = trim((string) ($this->request->getGet('tab') ?? 'bills'));
        if (!in_array($activeTab, ['bills', 'product-expenses'], true)) {
            $activeTab = 'bills';
        }

        $billFilters = [
            'status' => trim((string) ($this->request->getGet('status') ?? '')),
            'from_date' => trim((string) ($this->request->getGet('from_date') ?? '')),
            'to_date' => trim((string) ($this->request->getGet('to_date') ?? '')),
            'keyword' => trim((string) ($this->request->getGet('keyword') ?? '')),
        ];

        $db = db_connect();

        $bills = [];
        if ($db->tableExists('bills')) {
            $billQuery = (new Bill())->orderBy('bill_date', 'DESC');
            if ($billFilters['status'] !== '') {
                $billQuery->where('status', $billFilters['status']);
            }
            if ($billFilters['from_date'] !== '') {
                $billQuery->where('bill_date >=', $billFilters['from_date']);
            }
            if ($billFilters['to_date'] !== '') {
                $billQuery->where('bill_date <=', $billFilters['to_date']);
            }
            if ($billFilters['keyword'] !== '') {
                $billQuery->groupStart()
                    ->like('bill_name', $billFilters['keyword'])
                    ->orLike('notes', $billFilters['keyword'])
                    ->groupEnd();
            }

            $bills = $billQuery->findAll();
        }

        $productExpenses = [];
        if ($db->tableExists('product_expenses')) {
            $productExpenses = (new ProductExpense())->orderBy('expense_date', 'DESC')->findAll();
        }

        return view('Reusables/menu') . view('financial/expenses', [
            'bills' => $bills,
            'productExpenses' => $productExpenses,
            'billFilters' => $billFilters,
            'activeTab' => $activeTab,
        ]);
    }

    public function createExpense()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $isValid = $this->validate([
            'category_id' => 'required|integer|greater_than[0]',
            'amount' => 'required|decimal|greater_than[0]',
            'expense_date' => 'required',
            'note' => 'permit_empty|max_length[255]',
        ]);

        if (!$isValid) {
            return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
        }

        $expenseModel = new Expense();
        $inserted = $expenseModel->insert([
            'category_id' => (int) $this->request->getPost('category_id'),
            'amount' => (float) $this->request->getPost('amount'),
            'note' => trim((string) $this->request->getPost('note')),
            'expense_date' => date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('expense_date'))),
            'recorded_by' => (int) (session()->get('user_id') ?? 0),
        ]);

        if ($inserted === false) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not save expense record.');
        }

        $created = $expenseModel->find((int) $inserted);
        try {
            $this->writeAudit(
                'create',
                'expense',
                (string) $inserted,
                'Created expense record',
                null,
                $created
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Expense recorded successfully.');
    }

    public function createBill()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $isValid = $this->validate([
            'bill_name' => 'required|max_length[150]',
            'amount' => 'required|decimal|greater_than[0]',
            'bill_date' => 'required|valid_date[Y-m-d]',
            'due_date' => 'required|valid_date[Y-m-d]',
            'status' => 'required|in_list[paid,unpaid]',
            'notes' => 'permit_empty|max_length[255]',
        ]);
        if (!$isValid) {
            return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
        }

        $inserted = (new Bill())->insert([
            'bill_name' => trim((string) $this->request->getPost('bill_name')),
            'amount' => (float) $this->request->getPost('amount'),
            'bill_date' => (string) $this->request->getPost('bill_date'),
            'due_date' => (string) $this->request->getPost('due_date'),
            'status' => (string) $this->request->getPost('status'),
            'notes' => trim((string) $this->request->getPost('notes')),
            'recorded_by' => (int) (session()->get('user_id') ?? 0),
        ]);

        if ($inserted === false) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not save bill.');
        }

        $created = (new Bill())->find((int) $inserted);
        try {
            $this->writeAudit(
                'create',
                'bill',
                (string) $inserted,
                'Created bill ' . (string) ($created['bill_name'] ?? ''),
                null,
                $created
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Bill saved successfully.');
    }

    public function updateBill(int $id)
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $isValid = $this->validate([
            'bill_name' => 'required|max_length[150]',
            'amount' => 'required|decimal|greater_than[0]',
            'bill_date' => 'required|valid_date[Y-m-d]',
            'due_date' => 'required|valid_date[Y-m-d]',
            'status' => 'required|in_list[paid,unpaid]',
            'notes' => 'permit_empty|max_length[255]',
        ]);
        if (!$isValid) {
            return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
        }

        $billModel = new Bill();
        $before = $billModel->find($id);
        $updated = $billModel->update($id, [
            'bill_name' => trim((string) $this->request->getPost('bill_name')),
            'amount' => (float) $this->request->getPost('amount'),
            'bill_date' => (string) $this->request->getPost('bill_date'),
            'due_date' => (string) $this->request->getPost('due_date'),
            'status' => (string) $this->request->getPost('status'),
            'notes' => trim((string) $this->request->getPost('notes')),
        ]);

        if ($updated === false) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not update bill.');
        }

        $after = $billModel->find($id);
        try {
            $this->writeAudit(
                'update',
                'bill',
                (string) $id,
                'Updated bill ' . (string) ($after['bill_name'] ?? ''),
                $before,
                $after
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Bill updated successfully.');
    }

    public function deleteBill(int $id)
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        (new Bill())->delete($id);
        $billModel = new Bill();
        $before = $billModel->find($id);
        $billModel->delete($id);

        try {
            $this->writeAudit(
                'delete',
                'bill',
                (string) $id,
                'Deleted bill ' . (string) ($before['bill_name'] ?? ''),
                $before,
                null
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Bill deleted successfully.');
    }

    public function updateExpense(int $id)
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $isValid = $this->validate([
            'category_id' => 'required|integer|greater_than[0]',
            'amount' => 'required|decimal|greater_than[0]',
            'expense_date' => 'required',
            'note' => 'permit_empty|max_length[255]',
        ]);

        if (!$isValid) {
            return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
        }

        $expenseModel = new Expense();
        $before = $expenseModel->find($id);
        $updated = $expenseModel->update($id, [
            'category_id' => (int) $this->request->getPost('category_id'),
            'amount' => (float) $this->request->getPost('amount'),
            'note' => trim((string) $this->request->getPost('note')),
            'expense_date' => date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('expense_date'))),
        ]);

        if ($updated === false) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not update expense record.');
        }

        $after = $expenseModel->find($id);
        try {
            $this->writeAudit(
                'update',
                'expense',
                (string) $id,
                'Updated expense record',
                $before,
                $after
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Expense updated successfully.');
    }

    public function deleteExpense(int $id)
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $expenseModel = new Expense();
        $before = $expenseModel->find($id);
        $expenseModel->delete($id);

        try {
            $this->writeAudit(
                'delete',
                'expense',
                (string) $id,
                'Deleted expense record',
                $before,
                null
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Expense deleted successfully.');
    }

    public function createCategory()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $isValid = $this->validate([
            'name' => 'required|max_length[120]|is_unique[expense_categories.name]',
        ]);

        if (!$isValid) {
            return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
        }

        $inserted = (new ExpenseCategory())->insert([
            'name' => trim((string) $this->request->getPost('name')),
            'is_active' => 1,
        ]);

        if ($inserted === false) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not create expense category.');
        }

        $created = (new ExpenseCategory())->find((int) $inserted);
        try {
            $this->writeAudit(
                'create',
                'expense_category',
                (string) $inserted,
                'Created expense category ' . (string) ($created['name'] ?? ''),
                null,
                $created
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Expense category created successfully.');
    }

    public function updateCategory(int $id)
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $isValid = $this->validate([
            'name' => 'required|max_length[120]',
            'is_active' => 'required|in_list[0,1]',
        ]);

        if (!$isValid) {
            return redirect()->to('/financial/expenses')->withInput()->with('errors', $this->validator->getErrors());
        }

        $categoryModel = new ExpenseCategory();
        $before = $categoryModel->find($id);
        $updated = $categoryModel->update($id, [
            'name' => trim((string) $this->request->getPost('name')),
            'is_active' => (int) $this->request->getPost('is_active'),
        ]);

        if ($updated === false) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not update expense category.');
        }

        $after = $categoryModel->find($id);
        try {
            $this->writeAudit(
                'update',
                'expense_category',
                (string) $id,
                'Updated expense category ' . (string) ($after['name'] ?? ''),
                $before,
                $after
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Expense category updated successfully.');
    }

    public function deleteCategory(int $id)
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $inUse = (new Expense())->where('category_id', $id)->countAllResults() > 0;
        if ($inUse) {
            return redirect()->to('/financial/expenses')->with('error', 'Category cannot be deleted because it is used by existing expenses.');
        }

        $categoryModel = new ExpenseCategory();
        $before = $categoryModel->find($id);
        $categoryModel->delete($id);

        try {
            $this->writeAudit(
                'delete',
                'expense_category',
                (string) $id,
                'Deleted expense category ' . (string) ($before['name'] ?? ''),
                $before,
                null
            );
        } catch (\RuntimeException $exception) {
            return redirect()->to('/financial/expenses')->with('error', $exception->getMessage());
        }

        return redirect()->to('/financial/expenses')->with('success', 'Expense category deleted successfully.');
    }

    private function writeAudit(
        string $action,
        string $entityType,
        ?string $entityId,
        string $summary,
        ?array $beforeData,
        ?array $afterData
    ): void {
        (new AuditTrailService())->log([
            'actor_user_id' => (int) (session()->get('user_id') ?? 0) ?: null,
            'actor_role' => (string) (session()->get('role') ?? ''),
            'module' => 'financial',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'summary' => $summary,
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'request_method' => $this->request->getMethod(),
            'request_path' => $this->request->getPath(),
            'ip_address' => $this->request->getIPAddress(),
        ]);
    }

    private function dateWindows(): array
    {
        $now = new \DateTimeImmutable('now');
        $dailyStart = $now->setTime(0, 0, 0);
        $dailyEnd = $now->setTime(23, 59, 59);
        $weekStart = $now->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd = $weekStart->modify('+6 days')->setTime(23, 59, 59);
        $monthStart = $now->modify('first day of this month')->setTime(0, 0, 0);
        $monthEnd = $now->modify('last day of this month')->setTime(23, 59, 59);

        return [
            'daily_start' => $dailyStart->format('Y-m-d H:i:s'),
            'daily_end' => $dailyEnd->format('Y-m-d H:i:s'),
            'weekly_start' => $weekStart->format('Y-m-d H:i:s'),
            'weekly_end' => $weekEnd->format('Y-m-d H:i:s'),
            'monthly_start' => $monthStart->format('Y-m-d H:i:s'),
            'monthly_end' => $monthEnd->format('Y-m-d H:i:s'),
        ];
    }

    private function monthRange(int $year, int $month): array
    {
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $end = $start->modify('last day of this month')->setTime(23, 59, 59);

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }

    private function periodRange(string $period): array
    {
        $windows = $this->dateWindows();
        if ($period === 'daily') {
            return [$windows['daily_start'], $windows['daily_end']];
        }

        if ($period === 'weekly') {
            return [$windows['weekly_start'], $windows['weekly_end']];
        }

        return [$windows['monthly_start'], $windows['monthly_end']];
    }

    private function resolveBreakdownRange(string $period): ?array
    {
        if (in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            [$start, $end] = $this->periodRange($period);
            return [$start, $end, ucfirst($period)];
        }

        if (in_array($period, ['all', 'batch'], true)) {
            $now = new \DateTimeImmutable('now');
            $label = $period === 'batch' ? 'Batch expenses (all time)' : 'All time';
            return ['1970-01-01 00:00:00', $now->format('Y-m-d H:i:s'), $label];
        }

        if ($period === 'today') {
            $windows = $this->dateWindows();
            return [$windows['daily_start'], $windows['daily_end'], 'Today'];
        }

        if ($period === 'date') {
            $dateValue = trim((string) $this->request->getGet('date'));
            if ($dateValue === '') {
                return null;
            }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
            if ($date === false) {
                return null;
            }

            $start = $date->setTime(0, 0, 0);
            $end = $date->setTime(23, 59, 59);
            return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $date->format('F j, Y')];
        }

        if ($period === 'month') {
            $monthNumber = (int) $this->request->getGet('month');
            $year = (int) $this->request->getGet('year');
            if ($year < 2000 || $monthNumber < 1 || $monthNumber > 12) {
                return null;
            }

            [$start, $end] = $this->monthRange($year, $monthNumber);
            $labelDate = \DateTimeImmutable::createFromFormat('!Y-n', $year . '-' . $monthNumber);
            $label = $labelDate ? $labelDate->format('F Y') : sprintf('%04d-%02d', $year, $monthNumber);
            return [$start, $end, $label];
        }

        return null;
    }

    private function receiptRows(string $from, string $to): array
    {
        $db = db_connect();
        if (!$db->tableExists('receipts') || !$db->fieldExists('created_at', 'receipts')) {
            return [];
        }

        return $db->table('receipts')
            ->select('id, receipt_number, total_amount, created_at')
            ->where('created_at >=', $from)
            ->where('created_at <=', $to)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    private function paidBillRows(string $from, string $to): array
    {
        $db = db_connect();
        if (!$db->tableExists('bills')) {
            return [];
        }

        return $db->table('bills')
            ->select('id, bill_name, amount, bill_date, due_date, status, notes')
            ->where('status', 'paid')
            ->where('bill_date >=', substr($from, 0, 10))
            ->where('bill_date <=', substr($to, 0, 10))
            ->orderBy('bill_date', 'DESC')
            ->get()
            ->getResultArray();
    }

    private function productExpenseRows(string $from, string $to): array
    {
        $db = db_connect();
        if (!$db->tableExists('product_expenses')) {
            return [];
        }

        $builder = $db->table('product_expenses');
        $select = 'product_expenses.id, product_expenses.stock_in_id, product_expenses.amount, product_expenses.expense_date, product_expenses.source_label';

        if ($db->tableExists('product_batch')) {
            $select .= ', product_batch.batch_number';
            $builder->join('product_batch', 'product_batch.stock_in_id = product_expenses.stock_in_id', 'left');
        }

        return $builder
            ->select($select)
            ->where('product_expenses.expense_date >=', $from)
            ->where('product_expenses.expense_date <=', $to)
            ->orderBy('product_expenses.expense_date', 'DESC')
            ->get()
            ->getResultArray();
    }

    private function filterBatchExpenseRows(array $rows): array
    {
        $filtered = array_filter($rows, static fn(array $row): bool => (string) ($row['batch_number'] ?? '') !== '');

        return array_values($filtered);
    }

    private function mergeBreakdownRows(array $receiptRows, array $paidBillRows, array $productExpenseRows): array
    {
        $rows = [];

        foreach ($receiptRows as $row) {
            $rows[] = [
                'type' => 'Revenue',
                'source' => 'Receipt',
                'reference' => (string) ($row['receipt_number'] ?? $row['id'] ?? ''),
                'date' => (string) ($row['created_at'] ?? ''),
                'amount' => (float) ($row['total_amount'] ?? 0),
                'notes' => '',
            ];
        }

        foreach ($paidBillRows as $row) {
            $rows[] = [
                'type' => 'Expense',
                'source' => 'Bill',
                'reference' => (string) ($row['bill_name'] ?? ''),
                'date' => (string) ($row['bill_date'] ?? ''),
                'amount' => (float) ($row['amount'] ?? 0),
                'notes' => (string) ($row['notes'] ?? ''),
            ];
        }

        foreach ($productExpenseRows as $row) {
            $reference = $row['stock_in_id'] ?? '';
            $batchNumber = (string) ($row['batch_number'] ?? '');
            if ($batchNumber !== '') {
                $referenceLabel = 'Batch ' . $batchNumber;
            } else {
                $referenceLabel = $reference !== '' ? 'Stock-In #' . $reference : '';
            }
            $rows[] = [
                'type' => 'Expense',
                'source' => (string) ($row['source_label'] ?? 'Product Expense'),
                'reference' => $referenceLabel,
                'date' => (string) ($row['expense_date'] ?? ''),
                'amount' => (float) ($row['amount'] ?? 0),
                'notes' => '',
            ];
        }

        usort($rows, static fn(array $a, array $b): int => strtotime((string) ($b['date'] ?? '')) <=> strtotime((string) ($a['date'] ?? '')));

        return $rows;
    }

    private function buildMetrics(string $from, string $to): array
    {
        $revenue = $this->sumRevenue($from, $to);
        $expenses = $this->sumPaidBills($from, $to) + $this->sumProductExpenses($from, $to);
        $netIncome = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? round(($netIncome / $revenue) * 100, 2) : 0.0;

        return [
            'revenue' => round($revenue, 2),
            'expenses' => round($expenses, 2),
            'net_income' => round($netIncome, 2),
            'profit_margin' => $profitMargin,
        ];
    }

    private function sumRevenue(string $from, string $to): float
    {
        $db = db_connect();
        if (!$db->tableExists('receipts') || !$db->fieldExists('created_at', 'receipts')) {
            return 0.0;
        }

        $row = $db->table('receipts')
            ->select('COALESCE(SUM(total_amount), 0) AS total_revenue')
            ->where('created_at >=', $from)
            ->where('created_at <=', $to)
            ->get()
            ->getRowArray();

        return (float) ($row['total_revenue'] ?? 0);
    }

    private function sumPaidBills(string $from, string $to): float
    {
        $db = db_connect();
        if (!$db->tableExists('bills')) {
            return 0.0;
        }

        $row = $db->table('bills')
            ->select('COALESCE(SUM(amount), 0) AS total_paid_bills')
            ->where('status', 'paid')
            ->where('bill_date >=', substr($from, 0, 10))
            ->where('bill_date <=', substr($to, 0, 10))
            ->get()
            ->getRowArray();

        return (float) ($row['total_paid_bills'] ?? 0);
    }

    private function sumProductExpenses(string $from, string $to): float
    {
        $db = db_connect();
        if (!$db->tableExists('product_expenses')) {
            return 0.0;
        }

        $row = $db->table('product_expenses')
            ->select('COALESCE(SUM(amount), 0) AS total_product_expenses')
            ->where('expense_date >=', $from)
            ->where('expense_date <=', $to)
            ->get()
            ->getRowArray();

        return (float) ($row['total_product_expenses'] ?? 0);
    }

    private function requireOwner()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        if ((string) session()->get('role') !== 'Owner') {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Financial Analytics.');
        }

        return null;
    }
}

