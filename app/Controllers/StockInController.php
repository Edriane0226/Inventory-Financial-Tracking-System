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
        // Check if the user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $stockInModel = new StockIn();
        $capitalModel = new Capital();
        $categoriesModel = new Categories();
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
                'barcode' => 'required|alpha_numeric',
                'sales_price' => 'required|decimal',
                'unit_type' => 'required',
            ]);

            if (!$validate) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            if ($validate) {
                // Process the form data and insert into the database
                $stockInModel = new StockIn();
                $capitalModel = new Capital();
                $productsModel = new Products();
                $productBatchModel = new ProductBatch();
                $categoriesModel = new Categories();
                $unitTypeModel = new UnitTypes();

                

                $categoryID = $categoriesModel->getIdbyCategoryName($this->request->getPost('category'))['id'];
                $unitTypeID = $unitTypeModel->getIdbyUnitTypeName($this->request->getPost('unit_type'))['id'];

                $stockInModel->insert([
                    'product_name' => $this->request->getPost('product_name'),
                    'quantity' => $this->request->getPost('quantity'),
                    'category_id' => $categoryID,
                    'unit_type_id' => $unitTypeID,
                    'stock_in_date' => $this->request->getPost('stockin_date'),
                    'barcode' => $this->request->getPost('barcode'),
                    'recorded_by' => session()->get('userId'),
                ]);

                $stockInID = $stockInModel->getStockInIDWithProductName($this->request->getPost('product_name'))['id'];

                $productBatchModel->insert([
                    'batch_number' => $this->request->getPost('batch_number'),
                    'expiration_date' => $this->request->getPost('expiration_date'),
                    'stock_in_id' => $stockInID,
                ]);

                $capitalModel->insert([
                    'capital' => $this->request->getPost('capital'),
                    'date' => $this->request->getPost('stockin_date'),
                    'stock_in_id' => $stockInID,
                ]);

                $productsModel->insert([
                    'stock_in_id' => $stockInID,
                ]);

                $salesPriceModel->insert([
                    'sales_price' => $this->request->getPost('sales_price'),
                    'effective_date' => $this->request->getPost('stockin_date'),
                    'product_id' => $productsModel->getIdbyStockInID($stockInID)['id'],
                ]);

                return redirect()->to('/stockin')->with('success', 'Stock In created successfully');
            }
        }

        $data = [
            'stockIns' => $stockInModel->findAll(),
            'capitals' => $capitalModel->findAll(),
            'categories' => $categoriesModel->findAll(),
            'productBatches' => $productBatchModel->findAll(),
            'unitTypes' => $unitTypeModel->findAll(),
        ];

        return view('stockin/index', $data);
    }
}