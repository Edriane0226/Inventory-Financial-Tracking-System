<?php

namespace App\Controllers;

use App\Models\StockIn;
use App\Models\Capital;
use App\Models\Categories;
use App\Models\Products;
use App\Models\UnitTypes;
use App\Models\ProductBatch;
use App\Models\SalesPrice;

class StockInController extends BaseController
{

    public function StockIn()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $role = session()->get('role');
        if (!in_array($role, ['Owner', 'Employee'], true)) {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Stock In.');
        }

        $stockInModel = new StockIn();
        $capitalModel = new Capital();
        $categoriesModel = new Categories();
        $productsModel = new Products();
        $productBatchModel = new ProductBatch();
        $unitTypeModel = new UnitTypes();
        $salesPriceModel = new SalesPrice();

        $data['stockIns'] = $stockInModel->findAll();
        $data['capitals'] = $capitalModel->findAll();
        $data['categories'] = $categoriesModel->findAll();
        $data['productBatches'] = $productBatchModel->findAll();
        $data['unitTypes'] = $unitTypeModel->findAll();
        $data['salesPrices'] = $salesPriceModel->findAll();

        if ($this->request->getMethod() === 'POST') {

            $validate = $this->validate([
                'product_name' => 'required',
                'quantity' => 'required|integer',
                'category' => 'required',
                'batch_number' => 'required',
                'expiration_date' => 'required|date',
                'capital' => 'required|decimal',
                'stockin_date' => 'required|date',
                'barcode' => 'required|alpha_numeric_space',
                'sales_price' => 'required|decimal',
                'unit_type' => 'required',
            ]);

            if (!$validate) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $category = $categoriesModel->getIdbyCategoryName($this->request->getPost('category'));
            $unitType = $unitTypeModel->getIdbyUnitTypeName($this->request->getPost('unit_type'));

            if (!$category || !$unitType) {
                return redirect()->back()->withInput()->with('error', 'Selected category or unit type does not exist.');
            }

            $stockInDate = date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('stockin_date')));
            $expirationDate = date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('expiration_date')));
            $productName = trim((string) $this->request->getPost('product_name'));
            $batchNumber = trim((string) $this->request->getPost('batch_number'));
            $incomingQty = (int) $this->request->getPost('quantity');
            $incomingCapital = (float) $this->request->getPost('capital');
            $incomingSalesPrice = (float) $this->request->getPost('sales_price');

            $db = \Config\Database::connect();
            $db->transBegin();

            $existingStockRow = $db->table('stock_in si')
                ->select('si.id, si.quantity')
                ->join('product_batch pb', 'pb.stock_in_id = si.id', 'inner')
                ->where('si.product_name', $productName)
                ->where('si.category_id', (int) $category['id'])
                ->where('si.unit_type_id', (int) $unitType['id'])
                ->where('si.stock_in_date', $stockInDate)
                ->where('pb.batch_number', $batchNumber)
                ->where('pb.expiration_date', $expirationDate)
                ->limit(1)
                ->get()
                ->getRowArray();

            $mergedToExisting = false;

            if ($existingStockRow) {
                $stockInID = (int) $existingStockRow['id'];
                $mergedToExisting = true;

                $quantityUpdated = $db->table('stock_in')
                    ->set('quantity', 'quantity + ' . $incomingQty, false)
                    ->where('id', $stockInID)
                    ->update();

                if (!$quantityUpdated) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', 'Could not update existing stock quantity. Please try again.');
                }
            } else {
                $stockInInserted = $stockInModel->insert([
                    'product_name' => $productName,
                    'quantity' => $incomingQty,
                    'category_id' => $category['id'],
                    'unit_type_id' => $unitType['id'],
                    'stock_in_date' => $stockInDate,
                    'barcode' => $this->request->getPost('barcode'),
                    'recorded_by' => session()->get('user_id'),
                ]);

                if ($stockInInserted === false) {
                    $db->transRollback();
                    $stockInErrors = implode(' ', $stockInModel->errors());
                    return redirect()->back()->withInput()->with('error', 'Could not save Stock In row. ' . ($stockInErrors ?: 'Please check database schema.'));
                }

                $stockInID = (int) $stockInInserted;

                $batchInserted = $productBatchModel->insert([
                    'batch_number' => $batchNumber,
                    'expiration_date' => $expirationDate,
                    'stock_in_id' => $stockInID,
                ]);

                if ($batchInserted === false) {
                    $db->transRollback();
                    $batchErrors = implode(' ', $productBatchModel->errors());
                    return redirect()->back()->withInput()->with('error', 'Could not save product batch. ' . ($batchErrors ?: 'Please check database schema.'));
                }
            }

            $capitalRow = $capitalModel->where('stock_in_id', $stockInID)->first();
            if ($capitalRow) {
                $capitalUpdated = $capitalModel->update((int) $capitalRow['id'], [
                    'capital' => (float) ($capitalRow['capital'] ?? 0) + $incomingCapital,
                    'date' => $stockInDate,
                ]);

                if ($capitalUpdated === false) {
                    $db->transRollback();
                    $capitalErrors = implode(' ', $capitalModel->errors());
                    return redirect()->back()->withInput()->with('error', 'Could not update capital row. ' . ($capitalErrors ?: 'Please check database schema.'));
                }
            } else {
                $capitalInserted = $capitalModel->insert([
                    'capital' => $incomingCapital,
                    'date' => $stockInDate,
                    'stock_in_id' => $stockInID,
                ]);

                if ($capitalInserted === false) {
                    $db->transRollback();
                    $capitalErrors = implode(' ', $capitalModel->errors());
                    return redirect()->back()->withInput()->with('error', 'Could not save capital row. ' . ($capitalErrors ?: 'Please check database schema.'));
                }
            }

            $productRow = $productsModel->where('stock_in_id', $stockInID)->first();
            if (!$productRow) {
                $productInserted = $productsModel->insert([
                    'stock_in_id' => $stockInID,
                ]);

                if ($productInserted === false) {
                    $db->transRollback();
                    $productErrors = implode(' ', $productsModel->errors());
                    return redirect()->back()->withInput()->with('error', 'Could not save product row. ' . ($productErrors ?: 'Please check database schema.'));
                }

                $productID = (int) $productInserted;
            } else {
                $productID = (int) $productRow['id'];
            }

            $salePriceInserted = $salesPriceModel->insert([
                'sale_price' => $incomingSalesPrice,
                'effective_date' => $stockInDate,
                'product_id' => $productID,
            ]);

            if ($salePriceInserted === false) {
                $db->transRollback();
                $salePriceErrors = implode(' ', $salesPriceModel->errors());
                return redirect()->back()->withInput()->with('error', 'Could not save sales price row. ' . ($salePriceErrors ?: 'Please check database schema.'));
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                $dbError = $db->error();
                $dbMessage = $dbError['message'] ?? 'Please try again.';
                return redirect()->back()->withInput()->with('error', 'Could not save Stock In record. ' . $dbMessage);
            }

            $db->transCommit();

            if ($mergedToExisting) {
                return redirect()->to('/stockin')->with('success', 'Matching stock entry found. Quantity was added to the existing batch.');
            }

            return redirect()->to('/stockin')->with('success', 'Stock In created successfully');
        }

        $data = [
            'stockIns' => $stockInModel->findAll(),
            'capitals' => $capitalModel->findAll(),
            'categories' => $categoriesModel->findAll(),
            'productBatches' => $productBatchModel->findAll(),
            'unitTypes' => $unitTypeModel->findAll(),
            'salesPrices' => $salesPriceModel->findAll(),
        ];

        return view('Reusables/menu') . view('Stock_in/index', $data);
    }
}