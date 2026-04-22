<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductOrderController;
use App\Http\Controllers\ProductMasukController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\SalaryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('dashboard', function () {
    return view('layouts.master');
})->middleware('auth');

Route::middleware(['auth'])->group(function () {

    // ── Categories ────────────────────────────────────────────────────
    Route::get('/apiCategories', [CategoryController::class, 'apiCategories'])->name('api.categories');
    Route::get('/exportCategoriesAll', [CategoryController::class, 'exportCategoriesAll'])->name('exportPDF.categoriesAll');
    Route::get('/exportCategoriesAllExcel', [CategoryController::class, 'exportExcel'])->name('exportExcel.categoriesAll');
    Route::get('categories/deleted-api', [CategoryController::class, 'apiDeletedCategories'])->name('api.categories.deleted');
    Route::patch('categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
    Route::resource('categories', CategoryController::class);

    // ── Customers ─────────────────────────────────────────────────────
    Route::resource('customers', CustomerController::class);
    Route::get('/apiCustomers', [CustomerController::class, 'apiCustomers'])->name('api.customers');
    Route::post('/importCustomers', [CustomerController::class, 'ImportExcel'])->name('import.customers');
    Route::get('/exportCustomersAll', [CustomerController::class, 'exportCustomersAll'])->name('exportPDF.customersAll');
    Route::get('/exportCustomersAllExcel', [CustomerController::class, 'exportExcel'])->name('exportExcel.customersAll');

   
    // ── Finance ───────────────────────────────────────────────────────
    Route::get('/finance',           [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance',          [FinanceController::class, 'store'])->name('finance.store');
    Route::delete('/finance/{id}',   [FinanceController::class, 'destroy'])->name('finance.destroy');
    Route::get('/finance/api-stats', [FinanceController::class, 'apiStats'])->name('finance.apiStats');

    // ── Salary ────────────────────────────────────────────────────────
    Route::get('/salary/calculate',  [SalaryController::class, 'calculate'])->name('salary.calculate');
    Route::post('/salary/record',    [SalaryController::class, 'record'])->name('salary.record');
    Route::get('/salary/history',    [SalaryController::class, 'history'])->name('salary.history');

    // ── Salary Policy ─────────────────────────────────────────────────
    Route::get('/salary-policy',         [\App\Http\Controllers\SalaryPolicyController::class, 'index'])->name('salary-policy.index');
    Route::post('/salary-policy',        [\App\Http\Controllers\SalaryPolicyController::class, 'store'])->name('salary-policy.store');
    Route::patch('/salary-policy/{id}',  [\App\Http\Controllers\SalaryPolicyController::class, 'update'])->name('salary-policy.update');
    Route::delete('/salary-policy/{id}', [\App\Http\Controllers\SalaryPolicyController::class, 'destroy'])->name('salary-policy.destroy');


    // ── Products ──────────────────────────────────────────────────────
    Route::resource('products', ProductController::class);
    Route::get('/apiProducts', [ProductController::class, 'apiProducts'])->name('api.products');
    Route::get('api/deleted-products', [ProductController::class, 'apiDeletedProducts'])->name('api.deleted-products');
    Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::patch('products/{id}/status', [ProductController::class, 'updateStatus'])->name('products.updateStatus');
    Route::get('/get-sizes/{category_id}', [ProductController::class, 'getSizes']);

    // ── Products Out (Sale Orders) ────────────────────────────────────
    Route::post('productsOut/change', [ProductOrderController::class, 'storeChange'])->name('productsOut.change');
    Route::post('productsOut/merge', [ProductOrderController::class, 'mergeOrders'])->name('productsOut.merge');
    Route::post('productsOut/mergeStatus', [ProductOrderController::class, 'mergeUpdateStatus'])->name('productsOut.mergeStatus');
    Route::post('productsOut/{id}/unmerge', [ProductOrderController::class, 'unmergeOrder'])->name('productsOut.unmerge');
    Route::post('productsOut/{id}/split',   [ProductOrderController::class, 'splitOrder'])->name('productsOut.split');
    Route::post('productsOut/{id}/send-to-courier', [ProductOrderController::class, 'singleUpdateStatus'])->name('productsOut.sendToCourier');
    Route::post('productsOut/{id}/revert-from-courier', [ProductOrderController::class, 'revertFromCourier'])->name('productsOut.revertFromCourier');
    Route::post('exportPDF/productOrder/filtered', [ProductOrderController::class, 'exportFilteredOrders'])->name('exportPDF.productOrderFiltered');
    Route::get('/apiProductsOut', [ProductOrderController::class, 'apiProductsOut'])->name('api.productsOut');
    Route::get('/productsOut/stats', [ProductOrderController::class, 'stats'])->name('productsOut.stats');
    Route::resource('productsOut', ProductOrderController::class);
    Route::get('/exportProductOrderAll', [ProductOrderController::class, 'exportProductOrderAll'])->name('exportPDF.productOrderAll');
    Route::get('/exportProductOrderAllExcel', [ProductOrderController::class, 'exportExcel'])->name('exportExcel.productOrderAll');
    Route::get('/exportCourierOrders', [ProductOrderController::class, 'exportCourierOrders'])->name('exportExcel.courierOrders');
    Route::get('/exportProductOrder/{id}', [ProductOrderController::class, 'exportProductOrder'])->name('exportPDF.productOrder');
    Route::get('productsOut/{id}/export-change-pdf', [ProductOrderController::class, 'exportChangePDF'])->name('exportPDF.changeOrder');
    Route::post('productsOut/{id}/restore', [ProductOrderController::class, 'restore'])->name('productsOut.restore');
    Route::post('productsOut/{id}/sendMail', [ProductOrderController::class, 'sendMail']);
    Route::get('product-order/{id}/status-log', [ProductOrderController::class, 'statusLog'])->name('productOrder.statusLog');
    Route::patch('productsOut/{id}/status',  [ProductOrderController::class, 'updateStatus'])->name('productsOut.updateStatus');
    Route::patch('productsOut/{id}/payment', [ProductOrderController::class, 'updatePayment'])->name('productsOut.updatePayment');

    // ── Warehouse (ნაშთი) ─────────────────────────────────────────────
    Route::get('warehouse',             [WarehouseController::class, 'index'])->name('warehouse.index');
    Route::get('warehouse/api-stock',   [WarehouseController::class, 'apiStock'])->name('warehouse.apiStock');
    Route::get('warehouse/stock-info',  [WarehouseController::class, 'stockInfo'])->name('warehouse.stockInfo');
    Route::get('api/fifo-prices',       [WarehouseController::class, 'fifoPrices'])->name('warehouse.fifoPrices');
Route::get('warehouse',            [WarehouseController::class, 'index'])     ->name('warehouse.index');
Route::get('warehouse/logs',       [WarehouseController::class, 'logsPage'])  ->name('warehouse.logs');
Route::get('warehouse/api-stock',  [WarehouseController::class, 'apiStock'])  ->name('warehouse.apiStock');
Route::get('warehouse/api-logs',   [WarehouseController::class, 'apiLogs'])   ->name('warehouse.apiLogs');
Route::get('warehouse/stock-info', [WarehouseController::class, 'stockInfo']) ->name('warehouse.stockInfo');
Route::get('warehouse/fifo-prices',[WarehouseController::class, 'fifoPrices'])->name('warehouse.fifoPrices');
Route::get('warehouse/available-stock', [WarehouseController::class, 'availableStock'])->name('warehouse.availableStock');
Route::post('warehouse/write-off',      [WarehouseController::class, 'writeOff'])      ->name('warehouse.writeOff');

    // ── Purchase Orders (შესყიდვები) ──────────────────────────────────
    Route::get('purchases',                       [PurchaseOrderController::class, 'index'])->name('purchases.index');
    Route::get('purchases/api',                   [PurchaseOrderController::class, 'apiPurchases'])->name('purchases.api');
    Route::post('purchases',                      [PurchaseOrderController::class, 'store'])->name('purchases.store');
    Route::get('purchases/{id}/edit',             [PurchaseOrderController::class, 'edit'])->name('purchases.edit');
    Route::patch('purchases/{id}',                [PurchaseOrderController::class, 'update'])->name('purchases.update');
    Route::delete('purchases/{id}',               [PurchaseOrderController::class, 'destroy'])->name('purchases.destroy');
    Route::get('purchases/group/{groupId}/items',           [PurchaseOrderController::class, 'getGroupItems'])->name('purchases.groupItems');
    Route::post('purchases/group/{groupId}/partial-receive',[PurchaseOrderController::class, 'groupPartialReceive'])->name('purchases.groupPartialReceive');

    // ── Users ─────────────────────────────────────────────────────────
    Route::get('/user/change-password', [UserController::class, 'changePasswordForm'])->name('user.change-password');
    Route::post('/user/change-password', [UserController::class, 'changePassword'])->name('user.change-password');
    Route::resource('user', UserController::class);
    Route::get('/apiUser', [UserController::class, 'apiUsers'])->name('api.users');
    Route::post('/user/{id}/role', [UserController::class, 'updateRole'])->name('user.updateRole');

});