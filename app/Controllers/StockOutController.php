<?php

namespace App\Controllers;

use App\Models\StockIn;
use App\Models\Capital;
use App\Models\Categories;
use App\Models\Products;
use App\Models\UnitTypes;
use App\Models\ProductBatch;
use App\Models\Reason;
use App\Models\Receipt;
use App\Models\SalesPrice;
use App\Models\StockOut;
use App\Models\UserModel;

class StockOutController extends BaseController
{

    public function StockOut()
    {
        $authRedirect = $this->requireStockOutAccess();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        if ($this->request->getMethod() === 'POST') {
            $validate = $this->validate([
                'barcode' => 'required',
                'quantity' => 'required|integer|greater_than[0]',
                'stock_out_date' => 'required',
                'reason' => 'required',
            ]);

            if (!$validate) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $result = $this->processStockOut(
                trim((string) $this->request->getPost('barcode')),
                (int) $this->request->getPost('quantity'),
                trim((string) $this->request->getPost('reason')),
                (string) $this->request->getPost('stock_out_date'),
                false
            );

            if (!$result['ok']) {
                return redirect()->back()->withInput()->with('error', $result['message']);
            }

            return redirect()->to('/stock-out/inventory')->with('success', $result['message']);
        }

        $reasonModel = new Reason();

        $data = [
            'reasons' => $reasonModel->where('reason_text !=', 'Sold')->orderBy('reason_text', 'ASC')->findAll(),
            'stockOuts' => $this->getRecentStockOutRows(),
        ];

        return view('Reusables/menu') . view('Stock_out/inventory', $data);
    }

    public function StockOutSold()
    {
        $authRedirect = $this->requireStockOutAccess();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        if ($this->request->getMethod() === 'POST') {
            $action = (string) ($this->request->getPost('action') ?? 'add');
            $cart = session()->get('cashier_stock_out_cart') ?? [];

            if ($action === 'add') {
                $validate = $this->validate([
                    'barcode' => 'required',
                    'quantity' => 'required|integer|greater_than[0]',
                    'stock_out_date' => 'required',
                ]);

                if (!$validate) {
                    return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
                }

                $barcode = trim((string) $this->request->getPost('barcode'));
                $qty = (int) $this->request->getPost('quantity');
                $stockOutDate = (string) $this->request->getPost('stock_out_date');
                $lookup = $this->getBarcodeLookup($barcode);

                if ($lookup === null) {
                    return redirect()->back()->withInput()->with('error', 'No available stock for this barcode.');
                }

                if (!empty($lookup['prioritize_required'])) {
                    return redirect()->back()->withInput()->with(
                        'error',
                        'Prioritize batch ' . ($lookup['prioritize_batch_number'] ?? '1') . ' first, please try again.'
                    );
                }

                $alreadyQueued = 0;
                foreach ($cart as $item) {
                    if (($item['product_name'] ?? '') === ($lookup['product_name'] ?? '')) {
                        $alreadyQueued += (int) ($item['quantity'] ?? 0);
                    }
                }

                if (($alreadyQueued + $qty) > (int) $lookup['total_available_qty']) {
                    return redirect()->back()->withInput()->with('error', 'Queued quantity exceeds available stock for this product.');
                }

                $cart[] = [
                    'barcode' => $barcode,
                    'quantity' => $qty,
                    'stock_out_date' => $stockOutDate,
                    'product_name' => $lookup['product_name'],
                    'category' => $lookup['category'],
                    'unit_type' => $lookup['unit_type'],
                    'product_batch_id' => $lookup['product_batch_id'],
                    'sales_price' => (float) ($lookup['sales_price'] ?? 0),
                    'line_total' => (float) $qty * (float) ($lookup['sales_price'] ?? 0),
                ];

                session()->set('cashier_stock_out_cart', $cart);
                return redirect()->to('/stock-out/cashier')->with('success', 'Added to cashier list. Confirm when ready.');
            }

            if ($action === 'remove') {
                $removeIndex = (int) $this->request->getPost('remove_index');
                if (isset($cart[$removeIndex])) {
                    unset($cart[$removeIndex]);
                    session()->set('cashier_stock_out_cart', array_values($cart));
                    return redirect()->to('/stock-out/cashier')->with('success', 'Item removed from cashier list.');
                }

                return redirect()->to('/stock-out/cashier')->with('error', 'Selected item was not found in cashier list.');
            }

            if ($action === 'clear') {
                session()->remove('cashier_stock_out_cart');
                return redirect()->to('/stock-out/cashier')->with('success', 'Cashier list cleared.');
            }

            if ($action === 'confirm') {
                if (empty($cart)) {
                    return redirect()->to('/stock-out/cashier')->with('error', 'Add products first before confirming.');
                }

                $db = \Config\Database::connect();
                $receiptModel = new Receipt();
                $receiptNumber = 'CASH-' . date('YmdHis') . '-' . random_int(1000, 9999);

                $receiptInserted = $receiptModel->insert([
                    'receipt_number' => $receiptNumber,
                    'total_amount' => 0,
                ]);

                if ($receiptInserted === false) {
                    return redirect()->to('/stock-out/cashier')->with('error', 'Could not create receipt for cashier transaction.');
                }

                $db->transBegin();
                $totalDeducted = 0;
                $totalSalesAmount = 0.0;

                foreach ($cart as $item) {
                    $line = $this->processStockOut(
                        (string) ($item['barcode'] ?? ''),
                        (int) ($item['quantity'] ?? 0),
                        'Sold',
                        (string) ($item['stock_out_date'] ?? date('Y-m-d')),
                        true,
                        (int) $receiptInserted,
                        false
                    );

                    if (!$line['ok']) {
                        $db->transRollback();
                        return redirect()->to('/stock-out/cashier')->with('error', $line['message']);
                    }

                    $totalDeducted += (int) ($line['deducted'] ?? 0);
                    $totalSalesAmount += (float) ($line['sales_total'] ?? 0);
                }

                $receiptUpdated = $receiptModel->update((int) $receiptInserted, [
                    'total_amount' => round($totalSalesAmount, 2),
                ]);

                if ($receiptUpdated === false) {
                    $db->transRollback();
                    return redirect()->to('/stock-out/cashier')->with('error', 'Could not update receipt total amount.');
                }

                if ($db->transStatus() === false) {
                    $db->transRollback();
                    return redirect()->to('/stock-out/cashier')->with('error', 'Cashier confirmation failed.');
                }

                $db->transCommit();
                session()->remove('cashier_stock_out_cart');

                return redirect()->to('/stock-out/receipt/' . (int) $receiptInserted)->with('success', 'Cashier stock out confirmed. Receipt ' . $receiptNumber . ' created for ' . $totalDeducted . ' item(s), total amount: ' . number_format($totalSalesAmount, 2) . '.');
            }

            return redirect()->to('/stock-out/cashier')->with('error', 'Unsupported cashier action.');
        }

        $data = [
            'cashierCart' => session()->get('cashier_stock_out_cart') ?? [],
            'stockOuts' => $this->getRecentStockOutRows(),
        ];

        return view('Reusables/menu') . view('Stock_out/cashier', $data);
    }

    public function printReceipt(int $receiptId)
    {
        $authRedirect = $this->requireStockOutAccess();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        $db = \Config\Database::connect();

        $receipt = $db->table('receipts')
            ->where('id', $receiptId)
            ->get()
            ->getRowArray();

        if (!$receipt) {
            return redirect()->to('/stock-out/cashier')->with('error', 'Receipt not found.');
        }

        $latestSalesPriceSubquery = '(SELECT sp1.product_id, sp1.sale_price
            FROM sales_price sp1
            INNER JOIN (
                SELECT product_id, MAX(id) AS max_id
                FROM sales_price
                GROUP BY product_id
            ) sp2 ON sp1.id = sp2.max_id
        ) sp_latest';

        $lines = $db->table('stock_out so')
            ->select('si.product_name, SUM(so.quantity) AS quantity, COALESCE(sp_latest.sale_price, 0) AS sales_price')
            ->join('products p', 'p.id = so.product_id', 'left')
            ->join('stock_in si', 'si.id = p.stock_in_id', 'left')
            ->join($latestSalesPriceSubquery, 'sp_latest.product_id = so.product_id', 'left', false)
            ->where('so.receipt_id', $receiptId)
            ->groupBy('so.product_id, si.product_name, sp_latest.sale_price')
            ->orderBy('si.product_name', 'ASC')
            ->get()
            ->getResultArray();

        $computedTotal = 0.0;
        foreach ($lines as &$line) {
            $line['quantity'] = (int) ($line['quantity'] ?? 0);
            $line['sales_price'] = (float) ($line['sales_price'] ?? 0);
            $line['line_total'] = $line['quantity'] * $line['sales_price'];
            $computedTotal += $line['line_total'];
        }
        unset($line);

        $receiptTotal = (float) ($receipt['total_amount'] ?? 0);
        if ($receiptTotal <= 0) {
            $receiptTotal = $computedTotal;
        }

        return view('Stock_out/receipt_print', [
            'receipt' => $receipt,
            'lines' => $lines,
            'receiptTotal' => $receiptTotal,
            'computedTotal' => $computedTotal,
        ]);
    }

    public function findByBarcode(string $barcode)
    {
        $authRedirect = $this->requireStockOutAccess();
        if ($authRedirect !== null) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => false,
                'message' => 'Unauthorized.',
            ]);
        }

        $barcode = trim($barcode);
        if ($barcode === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false,
                'message' => 'Barcode is required.',
            ]);
        }

        $lookup = $this->getBarcodeLookup($barcode);

        if ($lookup === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'No available stock for this barcode.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'product_name' => $lookup['product_name'],
            'barcode' => $lookup['barcode'],
            'unit_type' => $lookup['unit_type'],
            'category' => $lookup['category'],
            'product_batch_id' => $lookup['product_batch_id'],
            'sales_price' => $lookup['sales_price'],
            'next_batch_number' => $lookup['next_batch_number'],
            'next_batch_expiration' => $lookup['next_batch_expiration'],
            'next_batch_available_qty' => (int) $lookup['next_batch_available_qty'],
            'total_available_qty' => (int) $lookup['total_available_qty'],
        ]);
    }

    private function getBarcodeLookup(string $barcode): ?array
    {
        $db = \Config\Database::connect();

        $scannedBatch = $db->table('stock_in si')
            ->select('si.id as stock_in_id, si.product_name, si.quantity, si.barcode, si.unit_type_id, si.category_id, pb.id as product_batch_id, pb.batch_number, pb.expiration_date, ut.unit_type_name, c.category_name')
            ->join('product_batch pb', 'pb.stock_in_id = si.id', 'inner')
            ->join('unit_types ut', 'ut.id = si.unit_type_id', 'left')
            ->join('categories c', 'c.id = si.category_id', 'left')
            ->where('si.barcode', $barcode)
            ->where('si.quantity >', 0)
            // FIFO: oldest stock-in record is always consumed first.
            ->orderBy('si.stock_in_date', 'ASC')
            ->orderBy('si.id', 'ASC')
            ->orderBy('pb.id', 'ASC')
            ->get()
            ->getRowArray();

        if (!$scannedBatch) {
            return null;
        }

        $fifoBatch = $db->table('stock_in si')
            ->select('si.id as stock_in_id, si.product_name, si.quantity, si.barcode, si.unit_type_id, si.category_id, pb.id as product_batch_id, pb.batch_number, pb.expiration_date, ut.unit_type_name, c.category_name')
            ->join('product_batch pb', 'pb.stock_in_id = si.id', 'inner')
            ->join('unit_types ut', 'ut.id = si.unit_type_id', 'left')
            ->join('categories c', 'c.id = si.category_id', 'left')
            ->where('si.product_name', $scannedBatch['product_name'])
            ->where('si.quantity >', 0)
            ->orderBy('si.stock_in_date', 'ASC')
            ->orderBy('si.id', 'ASC')
            ->orderBy('pb.id', 'ASC')
            ->get()
            ->getRowArray();

        $totalAvailable = $db->table('stock_in si')
            ->selectSum('quantity')
            ->where('si.product_name', $scannedBatch['product_name'])
            ->where('quantity >', 0)
            ->get()
            ->getRowArray();

        $product = $db->table('products')
            ->select('id')
            ->where('stock_in_id', (int) $scannedBatch['stock_in_id'])
            ->get()
            ->getRowArray();

        $salesPriceValue = 0.0;
        if ($product) {
            $salesPrice = $db->table('sales_price')
                ->select('sale_price')
                ->where('product_id', (int) $product['id'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if ($salesPrice) {
                $salesPriceValue = (float) $salesPrice['sale_price'];
            }
        }

        $prioritizeRequired = $fifoBatch
            && (int) $fifoBatch['stock_in_id'] !== (int) $scannedBatch['stock_in_id'];

        return [
            'product_name' => $scannedBatch['product_name'],
            'barcode' => $scannedBatch['barcode'],
            'unit_type' => $scannedBatch['unit_type_name'] ?? 'N/A',
            'category' => $scannedBatch['category_name'] ?? 'N/A',
            'product_batch_id' => (int) $scannedBatch['product_batch_id'],
            'sales_price' => $salesPriceValue,
            'next_batch_number' => $fifoBatch['batch_number'] ?? $scannedBatch['batch_number'],
            'next_batch_expiration' => $fifoBatch['expiration_date'] ?? $scannedBatch['expiration_date'],
            'next_batch_available_qty' => (int) ($fifoBatch['quantity'] ?? $scannedBatch['quantity']),
            'total_available_qty' => (int) ($totalAvailable['quantity'] ?? 0),
            'prioritize_required' => $prioritizeRequired,
            'prioritize_batch_number' => $fifoBatch['batch_number'] ?? null,
        ];
    }

    private function requireStockOutAccess()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        if ($this->getRecordedByUserId() === null) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Your session is no longer valid. Please log in again.');
        }

        $role = session()->get('role');
        if (!in_array($role, ['Owner', 'Employee'], true)) {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Stock Out.');
        }

        return null;
    }

    private function getRecentStockOutRows(): array
    {
        $db = \Config\Database::connect();

        return $db->table('stock_out so')
            ->select('so.id, so.quantity, so.stock_out_date, r.reason_text, si.product_name, si.barcode, pb.batch_number, ut.unit_type_name, c.category_name')
            ->join('products p', 'p.id = so.product_id', 'left')
            ->join('stock_in si', 'si.id = p.stock_in_id', 'left')
            ->join('product_batch pb', 'pb.id = so.batch_id', 'left')
            ->join('reasons r', 'r.id = so.reason_id', 'left')
            ->join('unit_types ut', 'ut.id = so.unit_type_id', 'left')
            ->join('categories c', 'c.id = so.category_id', 'left')
            ->orderBy('so.id', 'DESC')
            ->limit(25)
            ->get()
            ->getResultArray();
    }

    private function processStockOut(string $barcode, int $requestedQty, string $reasonText, string $stockOutDate, bool $isCashier, ?int $receiptId = null, bool $manageTransaction = true): array
    {
        $db = \Config\Database::connect();
        $recordedBy = $this->getRecordedByUserId();

        if ($recordedBy === null) {
            return [
                'ok' => false,
                'message' => 'Your session user is invalid. Please log in again and retry.',
            ];
        }

        $reasonModel = new Reason();
        $receiptModel = new Receipt();
        $salesPriceModel = new SalesPrice();
        $stockOutModel = new StockOut();
        $capitalModel = new Capital();
        $productsModel = new Products();

        $scannedBatch = $db->table('stock_in si')
            ->select('si.id as stock_in_id, si.product_name, si.quantity, pb.id as batch_id, pb.batch_number')
            ->join('product_batch pb', 'pb.stock_in_id = si.id', 'inner')
            ->where('si.barcode', $barcode)
            ->where('si.quantity >', 0)
            ->orderBy('si.stock_in_date', 'ASC')
            ->orderBy('si.id', 'ASC')
            ->orderBy('pb.id', 'ASC')
            ->get()
            ->getRowArray();

        if (!$scannedBatch) {
            return [
                'ok' => false,
                'message' => 'No available stock for this barcode.',
            ];
        }

        $stockRows = $db->table('stock_in si')
            ->select('si.id as stock_in_id, si.product_name, si.quantity, si.unit_type_id, si.category_id, pb.id as batch_id, pb.batch_number, pb.expiration_date')
            ->join('product_batch pb', 'pb.stock_in_id = si.id', 'inner')
            ->where('si.product_name', $scannedBatch['product_name'])
            ->where('si.quantity >', 0)
            // FIFO: deduct from the oldest stock-in batch first, then next batch.
            ->orderBy('si.stock_in_date', 'ASC')
            ->orderBy('si.id', 'ASC')
            ->orderBy('pb.id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($stockRows)) {
            return [
                'ok' => false,
                'message' => 'No available stock for this barcode.',
            ];
        }

        $totalAvailable = 0;
        foreach ($stockRows as $row) {
            $totalAvailable += (int) $row['quantity'];
        }

        if ($requestedQty > $totalAvailable) {
            return [
                'ok' => false,
                'message' => 'Requested quantity exceeds available stock. Available: ' . $totalAvailable,
            ];
        }

        $firstRow = $stockRows[0] ?? null;
        if ($firstRow && (int) $firstRow['stock_in_id'] !== (int) $scannedBatch['stock_in_id']) {
            return [
                'ok' => false,
                'message' => 'Prioritize batch ' . ($firstRow['batch_number'] ?? '1') . ' first, please try again.',
            ];
        }

        $reason = $reasonModel->where('reason_text', $reasonText)->first();
        if (!$reason) {
            $reasonInserted = $reasonModel->insert(['reason_text' => $reasonText]);
            if ($reasonInserted === false) {
                return [
                    'ok' => false,
                    'message' => 'Could not prepare reason record.',
                ];
            }

            $reason = ['id' => $reasonInserted];
        }

        if ($receiptId === null) {
            $prefix = $isCashier ? 'CASH' : 'INV';
            $receiptInserted = $receiptModel->insert([
                'receipt_number' => $prefix . '-' . date('YmdHis') . '-' . random_int(1000, 9999),
            ]);

            if ($receiptInserted === false) {
                return [
                    'ok' => false,
                    'message' => 'Could not create receipt number for stock out.',
                ];
            }

            $receiptId = (int) $receiptInserted;
        }

        $stockOutDateTime = date('Y-m-d H:i:s', strtotime($stockOutDate . ' ' . date('H:i:s')));
        $remaining = $requestedQty;
        $deducted = 0;
        $salesTotal = 0.0;

        if ($manageTransaction) {
            $db->transBegin();
        }

        foreach ($stockRows as $row) {
            if ($remaining <= 0) {
                break;
            }

            $availableFromBatch = (int) $row['quantity'];
            if ($availableFromBatch <= 0) {
                continue;
            }

            $take = min($remaining, $availableFromBatch);

            $updated = $db->table('stock_in')
                ->set('quantity', 'quantity - ' . $take, false)
                ->where('id', $row['stock_in_id'])
                ->where('quantity >=', $take)
                ->update();

            if (!$updated) {
                if ($manageTransaction) {
                    $db->transRollback();
                }
                return [
                    'ok' => false,
                    'message' => 'Could not update stock quantity. Please retry.',
                ];
            }

            $product = $productsModel->where('stock_in_id', $row['stock_in_id'])->first();
            if (!$product) {
                $productInserted = $productsModel->insert(['stock_in_id' => $row['stock_in_id']]);
                if ($productInserted === false) {
                    if ($manageTransaction) {
                        $db->transRollback();
                    }
                    return [
                        'ok' => false,
                        'message' => 'Could not prepare product record for stock out.',
                    ];
                }

                $product = ['id' => $productInserted];
            }

            $capital = $capitalModel->where('stock_in_id', $row['stock_in_id'])->first();
            if (!$capital) {
                if ($manageTransaction) {
                    $db->transRollback();
                }
                return [
                    'ok' => false,
                    'message' => 'Missing capital record for one stock batch.',
                ];
            }

            $stockOutInserted = $stockOutModel->insert([
                'product_id' => $product['id'],
                'batch_id' => $row['batch_id'],
                'quantity' => $take,
                'unit_type_id' => $row['unit_type_id'],
                'reason_id' => $reason['id'],
                'receipt_id' => $receiptId,
                'capital_id' => $capital['id'],
                'category_id' => $row['category_id'],
                'recorded_by' => $recordedBy,
                'stock_out_date' => $stockOutDateTime,
            ]);

            if ($stockOutInserted === false) {
                if ($manageTransaction) {
                    $db->transRollback();
                }
                $errors = implode(' ', $stockOutModel->errors());
                return [
                    'ok' => false,
                    'message' => 'Could not save stock out entry. ' . ($errors ?: ''),
                ];
            }

            $salesPriceRow = $salesPriceModel
                ->where('product_id', (int) $product['id'])
                ->orderBy('id', 'DESC')
                ->first();

            $salePriceValue = (float) ($salesPriceRow['sale_price'] ?? 0);
            $salesTotal += $take * $salePriceValue;

            $remaining -= $take;
            $deducted += $take;
        }

        if ($remaining > 0 || ($manageTransaction && $db->transStatus() === false)) {
            if ($manageTransaction) {
                $db->transRollback();
            }
            return [
                'ok' => false,
                'message' => 'Stock out transaction failed. No changes were saved.',
            ];
        }

        if ($manageTransaction) {
            $db->transCommit();
        }

        return [
            'ok' => true,
            'message' => 'Stock out saved. Deducted ' . $deducted . ' item(s) using lowest-batch-first.',
            'deducted' => $deducted,
            'sales_total' => round($salesTotal, 2),
        ];
    }

    private function getRecordedByUserId(): ?int
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $userModel = new UserModel();
        $user = $userModel->select('id')->find($userId);

        if (!$user) {
            return null;
        }

        return (int) $user['id'];
    }
}