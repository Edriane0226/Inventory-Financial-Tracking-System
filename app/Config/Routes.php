<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::login');

$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::register');

$routes->get('dashboard', 'Auth::dashboard'); 

$routes->get('logout', 'Auth::logout');

$routes->get('products', 'InventoryController::index');
$routes->get('stock-levels', 'StockController::index');

$routes->get('stockin', 'StockInController::StockIn');
$routes->post('stockin', 'StockInController::StockIn');

$routes->get('stock-out', 'StockOutController::StockOut');
$routes->get('stock-out/inventory', 'StockOutController::StockOut');
$routes->post('stock-out/inventory', 'StockOutController::StockOut');

$routes->get('stock-out/cashier', 'StockOutController::StockOutSold');
$routes->post('stock-out/cashier', 'StockOutController::StockOutSold');
$routes->get('stock-out/receipt/(:num)', 'StockOutController::printReceipt/$1');

$routes->get('stock-out/barcode/(:any)', 'StockOutController::findByBarcode/$1');

$routes->get('financial', 'FinancialAnalyticsController::index');
$routes->get('financial/export-csv', 'FinancialAnalyticsController::exportMonthlyCsv');
$routes->get('financial/expenses', 'FinancialAnalyticsController::expenses');
$routes->post('financial/expenses/create', 'FinancialAnalyticsController::createExpense');
$routes->post('financial/expenses/update/(:num)', 'FinancialAnalyticsController::updateExpense/$1');
$routes->post('financial/expenses/delete/(:num)', 'FinancialAnalyticsController::deleteExpense/$1');
$routes->post('financial/categories/create', 'FinancialAnalyticsController::createCategory');
$routes->post('financial/categories/update/(:num)', 'FinancialAnalyticsController::updateCategory/$1');
$routes->post('financial/categories/delete/(:num)', 'FinancialAnalyticsController::deleteCategory/$1');
