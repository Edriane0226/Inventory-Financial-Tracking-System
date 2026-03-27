<?php

namespace App\Controllers;

use App\Models\StockIn;
use App\Models\Capital;
use App\Models\Categories;
use App\Models\Products;
use App\Models\UnitTypes;
use App\Models\ProductBatch;
use App\Models\SalesPrice;
use App\Models\StockOut;

class StockOutController extends BaseController
{

    public function StockOut()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $role = session()->get('role');
        if (!in_array($role, ['Owner', 'Employee'], true)) {
            return redirect()->to('/dashboard')->with('error', 'You are not authorized to access Stock Out.');
        }

        $stockOutModel = new StockOut();
        $capitalModel = new Capital();
        $categoriesModel = new Categories();
        $productsModel = new Products();
        $productBatchModel = new ProductBatch();
        $unitTypeModel = new UnitTypes();
        $salesPriceModel = new SalesPrice();

        $data['stockOuts'] = $stockOutModel->findAll();
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
                'capital' => 'required|decimal',
                'stock_out_date' => 'required|date',
                'reason' => 'required',
            ]);

            if (!$validate) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            /* Process the stock out logic of inventory here 
            like updating the stock if the reason is not Sold 
            and insert recorded_by and stock_out_date in the database
            this will connect to InventoryView
            */
        } 

        return view('stock_out', $data);
    }

    public function StockOutSold()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        if($this->request->getMethod() === 'POST') {
            /* Process the stock out logic of inventory here for Sold items, 
               and making a receipt for the sold items and updating the stock 
               accordingly and insert recorded_by and stock_out_date in the database
               this will connect to CashierView
            */
        }
    }
}