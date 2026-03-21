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

            $db = \Config\Database::connect();
            $db->transBegin();

            $stockInInserted = $stockInModel->insert([
                'product_name' => $this->request->getPost('product_name'),
                'quantity' => (int) $this->request->getPost('quantity'),
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
                'batch_number' => $this->request->getPost('batch_number'),
                'expiration_date' => $expirationDate,
                'stock_in_id' => $stockInID,
            ]);

            if ($batchInserted === false) {
                $db->transRollback();
                $batchErrors = implode(' ', $productBatchModel->errors());
                return redirect()->back()->withInput()->with('error', 'Could not save product batch. ' . ($batchErrors ?: 'Please check database schema.'));
            }

            $capitalInserted = $capitalModel->insert([
                'capital' => $this->request->getPost('capital'),
                'date' => $stockInDate,
                'stock_in_id' => $stockInID,
            ]);

            if ($capitalInserted === false) {
                $db->transRollback();
                $capitalErrors = implode(' ', $capitalModel->errors());
                return redirect()->back()->withInput()->with('error', 'Could not save capital row. ' . ($capitalErrors ?: 'Please check database schema.'));
            }

            $productInserted = $productsModel->insert([
                'stock_in_id' => $stockInID,
            ]);

            if ($productInserted === false) {
                $db->transRollback();
                $productErrors = implode(' ', $productsModel->errors());
                return redirect()->back()->withInput()->with('error', 'Could not save product row. ' . ($productErrors ?: 'Please check database schema.'));
            }

            $productID = (int) $productInserted;

            $salePriceInserted = $salesPriceModel->insert([
                'sale_price' => $this->request->getPost('sales_price'),
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