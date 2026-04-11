<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\ProductModel;

class Auth extends BaseController
{
    public function register()
    {
        helper('form');

        // Only Allow Owner to Register New Users
        $roleModel = new RoleModel();
        $userModel = new UserModel();
        $currentUser = $userModel->getUserWithRole(session()->get('user_id'));
        if ($currentUser['role_name'] !== 'Owner') {
            return redirect()->to('/')->with('error', 'You are not authorized to access this page.');
        }
        
        $data = [
            'roles' => $roleModel->findAll(),
            'currentUser' => $currentUser,
            'users' => $userModel->getUsersfirstNameLastNameEmailRole()
        ];
        

        if($this->request->getMethod() === 'POST') {
            $validationRules = [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|valid_email|is_unique[users.email]',
                'password' => 'required|min_length[6]',
                'role_id' => 'required|integer'
            ];

            if (!$this->validate($validationRules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $userModel = new UserModel();
            $userModel->save([
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'email' => $this->request->getPost('email'),
                'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                'role_id' => $this->request->getPost('role_id')
            ]);

            return redirect()->to('/register')->with('success', 'User registered successfully.');
        }

        return view('Reusables/menu') . view('auth/register', $data);
    }

    public function login()
    {
        helper('form');

        if($this->request->getMethod() === 'POST') {
            $validationRules = [
                'email' => 'required|valid_email',
                'password' => 'required'
            ];

            if (!$this->validate($validationRules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $userModel = new UserModel();
            $user = $userModel->where('email', $this->request->getPost('email'))->first();
            
            if ($user && password_verify($this->request->getPost('password'), $user['password_hash'])) {
                session()->set([
                    'user_id' => $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role' => $userModel->getRoleNameByRoleId($user['role_id']),
                    'logged_in' => true
                ]);
                return redirect()->to('/dashboard')->with('success', 'Logged in successfully.');
            } else {
                return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
            }
        }

        return view('auth/login');
    }

    public function dashboard()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Please log in to access the dashboard.');
        }

        $productModel = new ProductModel();
        $allProducts = $productModel->getAllProducts();

        $summary = [
            'total_products' => count($allProducts),
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
        ];
        $inventoryValue = 0.0;
        $lowStockProducts = [];

        foreach ($allProducts as $product) {
            $stockQuantity = (int) ($product['stock_quantity'] ?? 0);
            $minimumStock = (int) ($product['minimum_stock'] ?? 0);
            $price = (float) ($product['price'] ?? 0);

            $status = $productModel->getStockStatus($stockQuantity, $minimumStock);
            $inventoryValue += $stockQuantity * $price;

            if ($status === 'In Stock') {
                $summary['in_stock']++;
                continue;
            }

            if ($status === 'Low Stock') {
                $summary['low_stock']++;
                $product['stock_status'] = $status;
                $lowStockProducts[] = $product;
                continue;
            }

            $summary['out_of_stock']++;
        }

        $db = db_connect();
        $salesSummary = [
            'today' => 0.0,
            'week' => 0.0,
            'month' => 0.0,
            'receipts_today' => 0,
            'receipts_week' => 0,
            'receipts_month' => 0,
        ];

        $salesTrend = [
            'labels' => [],
            'values' => [],
        ];

        $recentReceipts = [];
        $hasReceiptsTable = $db->tableExists('receipts');
        $hasReceiptDates = $hasReceiptsTable
            && $db->fieldExists('created_at', 'receipts')
            && $db->fieldExists('total_amount', 'receipts');

        $now = new \DateTimeImmutable('now');
        $trendMap = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->modify('-' . $i . ' days');
            $dateKey = $day->format('Y-m-d');
            $trendMap[$dateKey] = [
                'label' => $day->format('M j'),
                'total' => 0.0,
            ];
        }

        if ($hasReceiptDates) {
            $todayStart = $now->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $todayEnd = $now->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            $weekStart = $now->modify('monday this week')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $monthStart = $now->modify('first day of this month')->setTime(0, 0, 0)->format('Y-m-d H:i:s');

            $sumReceipts = static function (string $from, string $to) use ($db): float {
                $row = $db->table('receipts')
                    ->select('COALESCE(SUM(total_amount), 0) AS total')
                    ->where('created_at >=', $from)
                    ->where('created_at <=', $to)
                    ->get()
                    ->getRowArray();

                return (float) ($row['total'] ?? 0);
            };

            $countReceipts = static function (string $from, string $to) use ($db): int {
                $row = $db->table('receipts')
                    ->select('COUNT(*) AS total')
                    ->where('created_at >=', $from)
                    ->where('created_at <=', $to)
                    ->get()
                    ->getRowArray();

                return (int) ($row['total'] ?? 0);
            };

            $salesSummary['today'] = $sumReceipts($todayStart, $todayEnd);
            $salesSummary['week'] = $sumReceipts($weekStart, $todayEnd);
            $salesSummary['month'] = $sumReceipts($monthStart, $todayEnd);
            $salesSummary['receipts_today'] = $countReceipts($todayStart, $todayEnd);
            $salesSummary['receipts_week'] = $countReceipts($weekStart, $todayEnd);
            $salesSummary['receipts_month'] = $countReceipts($monthStart, $todayEnd);

            $trendStart = $now->modify('-6 days')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $trendEnd = $todayEnd;

            $rows = $db->table('receipts')
                ->select('DATE(created_at) AS day, COALESCE(SUM(total_amount), 0) AS total')
                ->where('created_at >=', $trendStart)
                ->where('created_at <=', $trendEnd)
                ->groupBy('DATE(created_at)')
                ->orderBy('day', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $day = (string) ($row['day'] ?? '');
                if (isset($trendMap[$day])) {
                    $trendMap[$day]['total'] = (float) ($row['total'] ?? 0);
                }
            }

            $recentReceipts = $db->table('receipts')
                ->select('receipt_number, total_amount, created_at')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();
        }

        $salesTrend = [
            'labels' => array_values(array_column($trendMap, 'label')),
            'values' => array_values(array_column($trendMap, 'total')),
        ];

        $data = [
            'lowStockCount' => count($lowStockProducts),
            'summary' => $summary,
            'inventoryValue' => round($inventoryValue, 2),
            'salesSummary' => $salesSummary,
            'salesTrend' => $salesTrend,
            'recentReceipts' => $recentReceipts,
            'lowStockItems' => array_slice($lowStockProducts, 0, 5),
        ];

        return view('Reusables/menu') . view('auth/dashboard', $data);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Logged out successfully.');
    }
}
