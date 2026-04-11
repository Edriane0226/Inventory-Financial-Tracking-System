<?php

namespace App\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;

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

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['year', 'month', 'revenue', 'expenses', 'net_income', 'profit_margin_percent']);
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
                'attachment; filename=\"financial-statement-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.csv\"'
            )
            ->setBody($csv);
    }

    public function expenses()
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        $filters = [
            'category_id' => (int) ($this->request->getGet('category_id') ?? 0),
            'keyword' => trim((string) ($this->request->getGet('keyword') ?? '')),
            'from_date' => trim((string) ($this->request->getGet('from_date') ?? '')),
            'to_date' => trim((string) ($this->request->getGet('to_date') ?? '')),
        ];

        $expenseModel = new Expense();
        $query = $expenseModel->select('expenses.*, expense_categories.name AS category_name')
            ->join('expense_categories', 'expense_categories.id = expenses.category_id', 'left');

        if ($filters['category_id'] > 0) {
            $query->where('expenses.category_id', $filters['category_id']);
        }
        if ($filters['keyword'] !== '') {
            $query->like('expenses.note', $filters['keyword']);
        }
        if ($filters['from_date'] !== '') {
            $query->where('expenses.expense_date >=', $filters['from_date'] . ' 00:00:00');
        }
        if ($filters['to_date'] !== '') {
            $query->where('expenses.expense_date <=', $filters['to_date'] . ' 23:59:59');
        }

        $expenses = $query->orderBy('expenses.expense_date', 'DESC')->findAll();
        $categories = (new ExpenseCategory())->orderBy('name', 'ASC')->findAll();

        return view('Reusables/menu') . view('financial/expenses', [
            'expenses' => $expenses,
            'categories' => $categories,
            'filters' => $filters,
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

        return redirect()->to('/financial/expenses')->with('success', 'Expense recorded successfully.');
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

        $updated = (new Expense())->update($id, [
            'category_id' => (int) $this->request->getPost('category_id'),
            'amount' => (float) $this->request->getPost('amount'),
            'note' => trim((string) $this->request->getPost('note')),
            'expense_date' => date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('expense_date'))),
        ]);

        if ($updated === false) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not update expense record.');
        }

        return redirect()->to('/financial/expenses')->with('success', 'Expense updated successfully.');
    }

    public function deleteExpense(int $id)
    {
        $guard = $this->requireOwner();
        if ($guard !== null) {
            return $guard;
        }

        (new Expense())->delete($id);
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

        $updated = (new ExpenseCategory())->update($id, [
            'name' => trim((string) $this->request->getPost('name')),
            'is_active' => (int) $this->request->getPost('is_active'),
        ]);

        if ($updated === false) {
            return redirect()->to('/financial/expenses')->withInput()->with('error', 'Could not update expense category.');
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

        (new ExpenseCategory())->delete($id);
        return redirect()->to('/financial/expenses')->with('success', 'Expense category deleted successfully.');
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

    private function buildMetrics(string $from, string $to): array
    {
        $revenue = $this->sumRevenue($from, $to);
        $expenses = $this->sumExpenses($from, $to);
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

    private function sumExpenses(string $from, string $to): float
    {
        $db = db_connect();
        if (!$db->tableExists('expenses')) {
            return 0.0;
        }

        $row = $db->table('expenses')
            ->select('COALESCE(SUM(amount), 0) AS total_expenses')
            ->where('expense_date >=', $from)
            ->where('expense_date <=', $to)
            ->get()
            ->getRowArray();

        return (float) ($row['total_expenses'] ?? 0);
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

