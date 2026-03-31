<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// კონტროლერების იმპორტი
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
    
    // Categories
    //Route::resource('categories', CategoryController::class);
    Route::get('/apiCategories', [CategoryController::class, 'apiCategories'])->name('api.categories');
    Route::get('/exportCategoriesAll', [CategoryController::class, 'exportCategoriesAll'])->name('exportPDF.categoriesAll');
    Route::get('/exportCategoriesAllExcel', [CategoryController::class, 'exportExcel'])->name('exportExcel.categoriesAll');
// ეს ზემოთ უნდა იყოს
Route::get('categories/deleted-api', [CategoryController::class, 'apiDeletedCategories'])->name('api.categories.deleted');
Route::patch('categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');

// შემდეგ resource
Route::resource('categories', CategoryController::class);



    // Customers
    Route::resource('customers', CustomerController::class);
    Route::get('/apiCustomers', [CustomerController::class, 'apiCustomers'])->name('api.customers');
    Route::post('/importCustomers', [CustomerController::class, 'ImportExcel'])->name('import.customers');
    Route::get('/exportCustomersAll', [CustomerController::class, 'exportCustomersAll'])->name('exportPDF.customersAll');
    Route::get('/exportCustomersAllExcel', [CustomerController::class, 'exportExcel'])->name('exportExcel.customersAll');

    // Sales
    Route::resource('sales', SaleController::class);
    Route::get('/apiSales', [SaleController::class, 'apiSales'])->name('api.sales');
    Route::post('/importSales', [SaleController::class, 'ImportExcel'])->name('import.sales');
    Route::get('/exportSalesAll', [SaleController::class, 'exportSalesAll'])->name('exportPDF.salesAll');
    Route::get('/exportSalesAllExcel', [SaleController::class, 'exportExcel'])->name('exportExcel.salesAll');

    // Suppliers
    Route::resource('suppliers', SupplierController::class);
    Route::get('/apiSuppliers', [SupplierController::class, 'apiSuppliers'])->name('api.suppliers');
    Route::post('/importSuppliers', [SupplierController::class, 'ImportExcel'])->name('import.suppliers');
    Route::get('/exportSupplierssAll', [SupplierController::class, 'exportSuppliersAll'])->name('exportPDF.suppliersAll');
    Route::get('/exportSuppliersAllExcel', [SupplierController::class, 'exportExcel'])->name('exportExcel.suppliersAll');


Route::post('productsOut/merge', [ProductOrderController::class, 'mergeOrders'])->name('productsOut.merge');
Route::post('productsOut/mergeStatus', [ProductOrderController::class, 'mergeUpdateStatus'])->name('productsOut.mergeStatus');
Route::post('productsOut/{id}/unmerge', [ProductOrderController::class, 'unmergeOrder'])->name('productsOut.unmerge');
Route::post('exportPDF/productOrder/filtered', [ProductOrderController::class, 'exportFilteredOrders'])
    ->name('exportPDF.productOrderFiltered');
    Route::post('productsOut/{id}/send-to-courier', [ProductOrderController::class, 'singleUpdateStatus'])
    ->name('productsOut.sendToCourier');

    // Products
    Route::resource('products', ProductController::class);
    Route::get('/apiProducts', [ProductController::class, 'apiProducts'])->name('api.products');

    // Products Out (Order)
    Route::resource('productsOut', ProductOrderController::class);
    Route::get('/apiProductsOut', [ProductOrderController::class, 'apiProductsOut'])->name('api.productsOut');
    Route::get('/exportProductOrderAll', [ProductOrderController::class, 'exportProductOrderAll'])->name('exportPDF.productOrderAll');
    Route::get('/exportProductOrderAllExcel', [ProductOrderController::class, 'exportExcel'])->name('exportExcel.productOrderAll');
    Route::get('/exportProductOrder/{id}', [ProductOrderController::class, 'exportProductOrder'])->name('exportPDF.productOrder');

Route::get('api/deleted-products', [ProductController::class, 'apiDeletedProducts'])->name('api.deleted-products');
Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
Route::post('productsOut/{id}/restore', [ProductOrderController::class, 'restore'])->name('productsOut.restore');
Route::post('productsOut/{id}/sendMail', [ProductOrderController::class, 'sendMail']);
 Route::get('product-order/{id}/status-log', [ProductOrderController::class, 'statusLog'])->name('productOrder.statusLog');


// ════════════════════════════════════════════════════════
 
// Warehouse (შეცვლის productsIn-ს)
Route::get('/warehouse/stock-info', [WarehouseController::class, 'stockInfo'])->name('warehouse.stockInfo');
Route::get('/warehouse/api-stock', [WarehouseController::class, 'apiStock'])->name('warehouse.apiStock');
Route::get('/warehouse/api-purchases', [WarehouseController::class, 'apiPurchases'])->name('warehouse.apiPurchases');
Route::post('/warehouse/{id}/receive', [WarehouseController::class, 'receiveStock'])->name('warehouse.receive');
Route::resource('warehouse', WarehouseController::class);
Route::post('warehouse/update-status/{id}', [WarehouseController::class, 'updateStatus']);
    // Users
    Route::get('/user/change-password', [UserController::class, 'changePasswordForm'])->name('user.change-password');
Route::post('/user/change-password', [UserController::class, 'changePassword'])->name('user.change-password');
    Route::resource('user', UserController::class);
    Route::get('/apiUser', [UserController::class, 'apiUsers'])->name('api.users');
Route::post('/user/{id}/role', [UserController::class, 'updateRole'])->name('user.updateRole');


Route::get('/get-sizes/{category_id}', [ProductController::class, 'getSizes']);
   

});

    Route::patch('productsOut/{id}/status', [ProductOrderController::class, 'updateStatus'])->name('productsOut.updateStatus');