<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerReturnController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HeldInvoiceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockReturnController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

// Serves uploaded files (logos, product photos) directly from storage.
// Does not depend on the storage:link symlink, so images always load
// even after the project is copied/zipped to another machine.
Route::get('/media/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cash Register Routes
    Route::get('/cash-registers/open', [CashRegisterController::class, 'open'])->name('cash-registers.open');
    Route::post('/cash-registers/open', [CashRegisterController::class, 'store'])->name('cash-registers.store');
    Route::get('/cash-registers/status', [CashRegisterController::class, 'status'])->name('cash-registers.status');
    Route::post('/cash-registers/close', [CashRegisterController::class, 'close'])->name('cash-registers.close');

    // Invoices (split by check middleware)
    Route::get('/invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::get('/invoice-products/search', [InvoiceController::class, 'searchProducts'])->name('invoice-products.search');
    
    Route::middleware(['cash-register.check'])->group(function () {
        Route::resource('invoices', InvoiceController::class)->only(['create', 'store']);
    });
    Route::resource('invoices', InvoiceController::class)->except(['create', 'store', 'edit', 'update', 'destroy']);

    // Hold / Resume Bill
    Route::get('/held-invoices', [HeldInvoiceController::class, 'index'])->name('held-invoices.index');
    Route::post('/held-invoices', [HeldInvoiceController::class, 'store'])->name('held-invoices.store');
    Route::get('/held-invoices/{heldInvoice}', [HeldInvoiceController::class, 'show'])->name('held-invoices.show');
    Route::delete('/held-invoices/{heldInvoice}', [HeldInvoiceController::class, 'destroy'])->name('held-invoices.destroy');

    // Sales Returns (Cashier interface)
    Route::get('/sales-returns', [CustomerReturnController::class, 'index'])->name('sales-returns.index');
    Route::post('/sales-returns', [CustomerReturnController::class, 'store'])->name('sales-returns.store');
    Route::delete('/sales-returns/{customerReturn}', [CustomerReturnController::class, 'destroy'])->name('sales-returns.destroy');
    Route::get('/sales-returns/load-invoice', [CustomerReturnController::class, 'loadInvoice'])->name('sales-returns.load-invoice');

    // Cashier EOD (available to all authenticated users)
    Route::get('/reports/my-eod', [ReportController::class, 'cashierEod'])->name('reports.cashier-eod');

    // Barcode lookup
    Route::get('/products/barcode/{barcode}', function ($barcode) {
        $product = \App\Models\Product::with('category:id,name')
            ->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)->orWhere('sku', $barcode);
            })
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->notExpired()
            ->first();

        if (!$product) {
            $expired = \App\Models\Product::where(function ($q) use ($barcode) {
                    $q->where('barcode', $barcode)->orWhere('sku', $barcode);
                })
                ->where('status', 1)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', now())
                ->first();

            if ($expired) {
                return response()->json(['found' => false, 'reason' => 'expired', 'name' => $expired->name]);
            }
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'   => true,
            'product' => [
                'id'            => $product->id,
                'name'          => $product->name,
                'generic_name'  => $product->generic_name,
                'sku'           => $product->sku,
                'barcode'       => $product->barcode,
                'batch_number'  => $product->batch_number,
                'expiry_date'   => $product->expiry_date?->format('d M Y'),
                'expiry_status' => $product->expiryStatus(),
                'price'         => $product->price,
                'stock'         => $product->stock,
                'category_id'   => $product->category_id,
                'category_name' => $product->category->name ?? 'Uncategorized',
                'almari'          => $product->almari,
                'khana'           => $product->khana,
                'row'             => $product->row,
            ],
        ]);
    })->name('products.barcode');

    // Cashier and Admin routes for payments
    Route::middleware(['cash-register.check'])->group(function () {
        Route::post('/supplier-payments', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
        Route::post('/customer-payments', [CustomerPaymentController::class, 'store'])->name('customer-payments.store');
    });
    
    Route::get('/customer-payments', [CustomerPaymentController::class, 'index'])->name('customer-payments.index');

    // Cashier and admin access for day-to-day cash/khata entries
    Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store']);
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/suppliers/{supplier}/ledger', function (App\Models\Supplier $supplier) {
        if (!auth()->user()->isAdmin() && ($supplier->supplier_type ?? 'distributor') !== 'distributor') {
            abort(403);
        }

        $supplier->load('purchases', 'payments');
        $openingBalance = $supplier->opening_balance ?? 0;
        $totalPurchased = $supplier->purchases()->sum('total_amount');
        $totalPaid = $supplier->payments()->sum('amount');
        $balance = $openingBalance + $totalPurchased - $totalPaid;
        return view('suppliers.ledger', compact('supplier', 'openingBalance', 'totalPurchased', 'totalPaid', 'balance'));
    })->name('suppliers.ledger');
    
    // Customer routes accessible to both cashier and admin
    Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/customers/{customer}/ledger', function (App\Models\Customer $customer) {
        $customer->load('invoices', 'payments');
        $openingBalance = $customer->opening_balance ?? 0;
        $totalSales = $customer->invoices()->sum('total');
        $balance = $customer->invoices()->sum('due_amount');
        $totalPaid = $totalSales - $balance;
        return view('customers.ledger', compact('customer', 'openingBalance', 'totalSales', 'totalPaid', 'balance'));
    })->name('customers.ledger');

    // Admin-only routes
    Route::middleware('admin.only')->group(function () {
        Route::delete('/products/bulk-delete', [ProductController::class, 'bulkDestroy'])->name('products.bulkDestroy');
        Route::resource('products', ProductController::class);
        Route::get('/products-import', [ProductImportController::class, 'showForm'])->name('products.import');
        Route::post('/products-import', [ProductImportController::class, 'import'])->name('products.import.store');
        Route::get('/products-import/template', [ProductImportController::class, 'downloadTemplate'])->name('products.import.template');

        Route::delete('/categories/bulk-delete', [CategoryController::class, 'bulkDestroy'])->name('categories.bulkDestroy');
        Route::resource('categories', CategoryController::class);
        Route::resource('customers', CustomerController::class)->only(['edit', 'update', 'destroy']);
        Route::resource('suppliers', SupplierController::class);
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::resource('expenses', ExpenseController::class)->only(['edit', 'update', 'destroy']);
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/business-settings', [BusinessSettingController::class, 'edit'])->name('business-settings.edit');
        Route::put('/business-settings', [BusinessSettingController::class, 'update'])->name('business-settings.update');
        Route::post('/business-settings/cleanup-selected-data', [BusinessSettingController::class, 'cleanupSelectedData'])->name('business-settings.cleanup-selected-data');
        Route::get('/backup/download', [BackupController::class, 'download'])->name('backup.download');

        Route::resource('stock-returns', StockReturnController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('purchases', PurchaseController::class)->only(['destroy']);
        Route::delete('/supplier-payments/{supplierPayment}', [SupplierPaymentController::class, 'destroy'])->name('supplier-payments.destroy');
        Route::delete('/customer-payments/{customerPayment}', [CustomerPaymentController::class, 'destroy'])->name('customer-payments.destroy');
        Route::get('/cash-registers', [CashRegisterController::class, 'index'])->name('cash-registers.index');
        Route::get('/cash-registers/{cashRegister}', [CashRegisterController::class, 'show'])->name('cash-registers.show');

        Route::get('/reports/eod',         [ReportController::class, 'eod'])->name('reports.eod');
        Route::get('/reports/tax',         [ReportController::class, 'tax'])->name('reports.tax');
        Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');

        // Reports menu (full list)
        Route::get('/reports/customer-register',        [ReportController::class, 'customerRegister'])->name('reports.customer-register');
        Route::get('/reports/customer-sale-products',    [ReportController::class, 'customerSaleProducts'])->name('reports.customer-sale-products');
        Route::get('/reports/purchase-history',          [ReportController::class, 'purchaseHistory'])->name('reports.purchase-history');
        Route::get('/reports/purchase-return-history',   [ReportController::class, 'purchaseReturnHistory'])->name('reports.purchase-return-history');
        Route::get('/reports/product-purchase-history',  [ReportController::class, 'productPurchaseHistory'])->name('reports.product-purchase-history');
        Route::get('/reports/sale-history',               [ReportController::class, 'saleHistory'])->name('reports.sale-history');
        Route::get('/reports/user-sale-history',          [ReportController::class, 'userSaleHistory'])->name('reports.user-sale-history');
        Route::get('/reports/shift-sale-history',         [ReportController::class, 'shiftSaleHistory'])->name('reports.shift-sale-history');
        Route::get('/reports/category-sale-history',      [ReportController::class, 'categorySaleHistory'])->name('reports.category-sale-history');
        Route::get('/reports/product-sale-history',       [ReportController::class, 'productSaleHistory'])->name('reports.product-sale-history');
        Route::get('/reports/sale-history-category-pro',  [ReportController::class, 'saleHistoryCategoryPro'])->name('reports.sale-history-category-pro');
        Route::get('/reports/customer-sale-history',      [ReportController::class, 'customerSaleHistory'])->name('reports.customer-sale-history');
        Route::get('/reports/sale-summary',                [ReportController::class, 'saleSummary'])->name('reports.sale-summary');
        Route::get('/reports/sale-return-history',         [ReportController::class, 'saleReturnHistory'])->name('reports.sale-return-history');
        Route::get('/reports/cash-states',                 [ReportController::class, 'cashStates'])->name('reports.cash-states');
        Route::get('/reports/products-low-stock',          [ReportController::class, 'productsLowStock'])->name('reports.products-low-stock');
    });
});

require __DIR__ . '/auth.php';
