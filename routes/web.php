<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\ModuleManagerController;
use App\Http\Controllers\System\SystemHealthController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PosSessionController;
use App\Http\Controllers\PosTicketController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseHistoryController;
use App\Http\Controllers\PurchaseReceiptController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\PurchaseStatsController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockAlertController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockInventoryController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockValuationController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Models\AuditEvent;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLevel;
use App\Support\Workspace;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/logged-out', fn () => redirect()->route('login'))->name('logged-out');

Route::get('/login', [SessionController::class, 'showLogin'])->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/login', [SessionController::class, 'login'])->name('login.attempt');
    Route::post('/login/continue', [SessionController::class, 'continueAccount'])->name('login.continue');
});

Route::post('/login/switch-account', [SessionController::class, 'switchAccount'])->name('login.switch');
Route::post('/logout', [SessionController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/lock', [SessionController::class, 'showLock'])->name('session.lock');
    Route::post('/lock', [SessionController::class, 'lock'])->name('session.lock.store');
    Route::post('/unlock', [SessionController::class, 'unlock'])->name('session.unlock');
});

// Public customer-facing documents (token-based, no workspace auth)
Route::get('/f/{token}', [InvoiceController::class, 'publicView'])->name('invoices.public');
Route::get('/d/{token}', [QuoteController::class, 'publicView'])->name('quotes.public');

Route::middleware(['workspace', 'audit'])->group(function () {
    Route::get('/search', GlobalSearchController::class)->name('search');

    Route::get('/account', [SessionController::class, 'account'])->name('account.index');
    Route::get('/account/preferences', [SessionController::class, 'preferences'])->name('account.preferences');

    Route::get('/app', function () {
        $company = Workspace::company();
        $companyId = $company?->id;
        $storeId = Workspace::storeFilterId() ?? Workspace::store()?->id;

        $salesQuery = Sale::query()->where('company_id', $companyId)->whereDate('created_at', today());
        if ($storeId) {
            $salesQuery->where('store_id', $storeId);
        }

        $stockAlerts = 0;
        if (Schema::hasTable('stock_levels')) {
            $stockAlerts = StockLevel::query()
                ->where('company_id', $companyId)
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->whereColumn('quantity', '<=', 'min_quantity')
                ->count();
        }

        $posOpen = false;
        if (Schema::hasTable('pos_sessions')) {
            $posOpen = PosSession::query()
                ->where('company_id', $companyId)
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->where('status', 'open')
                ->exists();
        }

        $activity = collect();
        if (Schema::hasTable('audit_events') && Workspace::can('audit.view')) {
            $activity = AuditEvent::query()
                ->forCompany($companyId)
                ->with('user')
                ->latest('occurred_at')
                ->limit(6)
                ->get();
        }

        $stats = [
            'revenue_today' => (float) (clone $salesQuery)->sum('total_ttc'),
            'sales_today' => (clone $salesQuery)->count(),
            'stock_alerts' => $stockAlerts,
            'products' => Product::query()->where('company_id', $companyId)->count(),
            'pos_open' => $posOpen,
            'currency' => $company?->currency ?? 'MAD',
        ];

        return view('home', compact('stats', 'activity'));
    })->name('home');

    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import.form');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::post('/products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');
    Route::post('/products/{product}/activate', [ProductController::class, 'activate'])->name('products.activate');
    Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::resource('products', ProductController::class);

    // Stock module
    Route::get('/stock', [StockController::class, 'dashboard'])->name('stock.dashboard');
    Route::get('/stock/levels', [StockController::class, 'levels'])->name('stock.levels');
    Route::get('/stock/levels/export', [StockController::class, 'exportLevels'])->name('stock.levels.export');
    Route::patch('/stock/levels/{level}', [StockController::class, 'updateLevel'])->name('stock.levels.update');

    Route::get('/stock/movements', [StockMovementController::class, 'index'])->name('stock.movements.index');
    Route::get('/stock/movements/create', [StockMovementController::class, 'create'])->name('stock.movements.create');
    Route::post('/stock/movements', [StockMovementController::class, 'store'])->name('stock.movements.store');
    Route::get('/stock/movements/{movement}', [StockMovementController::class, 'show'])->name('stock.movements.show');

    Route::get('/stock/inventories', [StockInventoryController::class, 'index'])->name('stock.inventories.index');
    Route::get('/stock/inventories/create', [StockInventoryController::class, 'create'])->name('stock.inventories.create');
    Route::post('/stock/inventories', [StockInventoryController::class, 'store'])->name('stock.inventories.store');
    Route::get('/stock/inventories/{inventory}', [StockInventoryController::class, 'show'])->name('stock.inventories.show');
    Route::post('/stock/inventories/{inventory}/count', [StockInventoryController::class, 'count'])->name('stock.inventories.count');
    Route::post('/stock/inventories/{inventory}/scan', [StockInventoryController::class, 'scan'])->name('stock.inventories.scan');
    Route::post('/stock/inventories/{inventory}/validate', [StockInventoryController::class, 'validateInventory'])->name('stock.inventories.validate');
    Route::post('/stock/inventories/{inventory}/cancel', [StockInventoryController::class, 'cancel'])->name('stock.inventories.cancel');

    Route::get('/stock/alerts', [StockAlertController::class, 'index'])->name('stock.alerts');
    Route::get('/stock/alerts/export', [StockAlertController::class, 'export'])->name('stock.alerts.export');

    Route::get('/stock/valuation', [StockValuationController::class, 'index'])->name('stock.valuation');

    // Purchases module
    Route::get('/purchases', [PurchaseController::class, 'dashboard'])->name('purchases.dashboard');
    Route::get('/purchases/orders', [PurchaseController::class, 'index'])->name('purchases.orders.index');
    Route::get('/purchases/orders/export', [PurchaseController::class, 'export'])->name('purchases.orders.export');
    Route::get('/purchases/orders/create', [PurchaseController::class, 'create'])->name('purchases.orders.create');
    Route::post('/purchases/orders', [PurchaseController::class, 'store'])->name('purchases.orders.store');
    Route::get('/purchases/orders/{order}', [PurchaseController::class, 'show'])->name('purchases.orders.show');
    Route::get('/purchases/orders/{order}/edit', [PurchaseController::class, 'edit'])->name('purchases.orders.edit');
    Route::put('/purchases/orders/{order}', [PurchaseController::class, 'update'])->name('purchases.orders.update');
    Route::get('/purchases/orders/{order}/print', [PurchaseController::class, 'print'])->name('purchases.orders.print');
    Route::post('/purchases/orders/{order}/send', [PurchaseController::class, 'send'])->name('purchases.orders.send');
    Route::post('/purchases/orders/{order}/confirm', [PurchaseController::class, 'confirm'])->name('purchases.orders.confirm');
    Route::post('/purchases/orders/{order}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.orders.cancel');

    Route::get('/purchases/receipts', [PurchaseReceiptController::class, 'index'])->name('purchases.receipts.index');
    Route::get('/purchases/orders/{order}/receive', [PurchaseReceiptController::class, 'create'])->name('purchases.receipts.create');
    Route::post('/purchases/orders/{order}/receive', [PurchaseReceiptController::class, 'store'])->name('purchases.receipts.store');
    Route::get('/purchases/receipts/{receipt}', [PurchaseReceiptController::class, 'show'])->name('purchases.receipts.show');
    Route::post('/purchases/receipts/{receipt}/validate', [PurchaseReceiptController::class, 'validateReceipt'])->name('purchases.receipts.validate');

    Route::get('/purchases/requests', [PurchaseRequestController::class, 'index'])->name('purchases.requests.index');
    Route::get('/purchases/requests/create', [PurchaseRequestController::class, 'create'])->name('purchases.requests.create');
    Route::post('/purchases/requests', [PurchaseRequestController::class, 'store'])->name('purchases.requests.store');
    Route::get('/purchases/requests/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('purchases.requests.show');
    Route::post('/purchases/requests/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])->name('purchases.requests.submit');
    Route::post('/purchases/requests/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('purchases.requests.approve');
    Route::post('/purchases/requests/{purchaseRequest}/convert', [PurchaseRequestController::class, 'convert'])->name('purchases.requests.convert');

    Route::get('/purchases/history', [PurchaseHistoryController::class, 'index'])->name('purchases.history');
    Route::get('/purchases/stats', [PurchaseStatsController::class, 'index'])->name('purchases.stats');

    // Suppliers module
    Route::get('/suppliers', [SupplierController::class, 'dashboard'])->name('suppliers.dashboard');
    Route::get('/suppliers/list', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::get('/suppliers/stats', [SupplierController::class, 'stats'])->name('suppliers.stats');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::get('/suppliers/{supplier}/print', [SupplierController::class, 'print'])->name('suppliers.print');
    Route::post('/suppliers/{supplier}/documents', [SupplierController::class, 'storeDocument'])->name('suppliers.documents.store');
    Route::delete('/suppliers/{supplier}/documents/{document}', [SupplierController::class, 'destroyDocument'])->name('suppliers.documents.destroy');

    // Customers module
    Route::get('/customers', [CustomerController::class, 'dashboard'])->name('customers.dashboard');
    Route::get('/customers/list', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('/customers/stats', [CustomerController::class, 'stats'])->name('customers.stats');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('/customers/{customer}/print', [CustomerController::class, 'print'])->name('customers.print');
    Route::post('/customers/{customer}/documents', [CustomerController::class, 'storeDocument'])->name('customers.documents.store');
    Route::delete('/customers/{customer}/documents/{document}', [CustomerController::class, 'destroyDocument'])->name('customers.documents.destroy');

    // POS module
    Route::get('/pos', [PosController::class, 'dashboard'])->name('pos.dashboard');
    Route::get('/pos/terminal', [PosController::class, 'terminal'])->name('pos.terminal');
    Route::get('/pos/catalog', [PosController::class, 'catalog'])->name('pos.catalog');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/hold', [PosController::class, 'hold'])->name('pos.hold');
    Route::get('/pos/held/{sale}', [PosController::class, 'resume'])->name('pos.resume');

    Route::get('/pos/tickets', [PosTicketController::class, 'index'])->name('pos.tickets.index');
    Route::get('/pos/tickets/{sale}', [PosTicketController::class, 'show'])->name('pos.tickets.show');
    Route::get('/pos/tickets/{sale}/print', [PosTicketController::class, 'print'])->name('pos.tickets.print');
    Route::post('/pos/tickets/{sale}/cancel', [PosTicketController::class, 'cancel'])->name('pos.tickets.cancel');

    Route::get('/pos/sessions', [PosSessionController::class, 'index'])->name('pos.sessions.index');
    Route::get('/pos/sessions/open', [PosSessionController::class, 'create'])->name('pos.sessions.create');
    Route::post('/pos/sessions/open', [PosSessionController::class, 'store'])->name('pos.sessions.store');
    Route::get('/pos/sessions/{session}', [PosSessionController::class, 'show'])->name('pos.sessions.show');
    Route::get('/pos/sessions/{session}/close', [PosSessionController::class, 'closeForm'])->name('pos.sessions.close.form');
    Route::post('/pos/sessions/{session}/close', [PosSessionController::class, 'close'])->name('pos.sessions.close');

    // Invoicing module
    Route::get('/invoices', [InvoiceController::class, 'dashboard'])->name('invoices.dashboard');
    Route::get('/invoices/list', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::get('/invoices/products', [InvoiceController::class, 'productsJson'])->name('invoices.products');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('/invoices/{invoice}/credit-note', [InvoiceController::class, 'creditNote'])->name('invoices.credit-note');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Quotes module
    Route::get('/quotes', [QuoteController::class, 'dashboard'])->name('quotes.dashboard');
    Route::get('/quotes/list', [QuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/export', [QuoteController::class, 'export'])->name('quotes.export');
    Route::get('/quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
    Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
    Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
    Route::get('/quotes/{quote}/edit', [QuoteController::class, 'edit'])->name('quotes.edit');
    Route::put('/quotes/{quote}', [QuoteController::class, 'update'])->name('quotes.update');
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
    Route::post('/quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');
    Route::post('/quotes/{quote}/accept', [QuoteController::class, 'accept'])->name('quotes.accept');
    Route::post('/quotes/{quote}/refuse', [QuoteController::class, 'refuse'])->name('quotes.refuse');
    Route::post('/quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
    Route::post('/quotes/{quote}/convert-invoice', [QuoteController::class, 'convertInvoice'])->name('quotes.convert-invoice');
    Route::post('/quotes/{quote}/convert-sale', [QuoteController::class, 'convertSale'])->name('quotes.convert-sale');
    Route::get('/quotes/{quote}/print', [QuoteController::class, 'print'])->name('quotes.print');
    Route::get('/quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');

    // Sales module
    Route::get('/sales', [SaleController::class, 'dashboard'])->name('sales.dashboard');
    Route::get('/sales/list', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/export', [SaleController::class, 'export'])->name('sales.export');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/edit', [SaleController::class, 'edit'])->name('sales.edit');
    Route::put('/sales/{sale}', [SaleController::class, 'update'])->name('sales.update');
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    Route::post('/sales/{sale}/confirm', [SaleController::class, 'confirm'])->name('sales.confirm');
    Route::post('/sales/{sale}/advance', [SaleController::class, 'advance'])->name('sales.advance');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::get('/sales/{sale}/return', [SaleController::class, 'returnForm'])->name('sales.return');
    Route::post('/sales/{sale}/return', [SaleController::class, 'processReturn'])->name('sales.return.store');
    Route::post('/sales/{sale}/payments', [SaleController::class, 'storePayment'])->name('sales.payments.store');
    Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');

    // Reports & BI module
    Route::get('/reports', [ReportController::class, 'dashboard'])->name('reports.dashboard');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/products', [ReportController::class, 'products'])->name('reports.products');
    Route::get('/reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
    Route::get('/reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('/reports/stock', [ReportController::class, 'stock'])->name('reports.stock');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');

    // Users module
    Route::get('/users', [UserController::class, 'dashboard'])->name('users.dashboard');
    Route::get('/users/list', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::post('/users/invite', [UserController::class, 'invite'])->name('users.invite');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/documents', [UserController::class, 'storeDocument'])->name('users.documents.store');
    Route::delete('/users/{user}/documents/{document}', [UserController::class, 'destroyDocument'])->name('users.documents.destroy');
    Route::get('/users/{user}/print', [UserController::class, 'print'])->name('users.print');

    // Roles & Permissions (RBAC)
    Route::get('/roles', [RoleController::class, 'dashboard'])->name('roles.dashboard');
    Route::get('/roles/list', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/matrix', [RoleController::class, 'matrix'])->name('roles.matrix');
    Route::get('/roles/export', [RoleController::class, 'export'])->name('roles.export');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/{role}/duplicate', [RoleController::class, 'duplicate'])->name('roles.duplicate');
    Route::post('/roles/{role}/assign', [RoleController::class, 'assignUsers'])->name('roles.assign');

    // Paramètres généraux
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/modules', [ModuleManagerController::class, 'index'])->name('modules.index');
    Route::get('/modules/{module}', [ModuleManagerController::class, 'show'])->name('modules.show');
    Route::post('/modules/{module}/toggle', [ModuleManagerController::class, 'toggle'])->name('modules.toggle');
    Route::get('/branding', [BrandingController::class, 'index'])->name('branding.index');
    Route::put('/branding', [BrandingController::class, 'update'])->name('branding.update');

    // Sauvegardes & Santé du système
    Route::get('/system', [SystemHealthController::class, 'dashboard'])->name('system.dashboard');
    Route::post('/system/health/refresh', [SystemHealthController::class, 'refreshHealth'])->name('system.health.refresh');
    Route::get('/system/backups', [SystemHealthController::class, 'backups'])->name('system.backups');
    Route::post('/system/backups', [SystemHealthController::class, 'storeBackup'])->name('system.backups.store');
    Route::put('/system/backups/policy', [SystemHealthController::class, 'updatePolicy'])->name('system.backups.policy');
    Route::get('/system/backups/{backup}', [SystemHealthController::class, 'showBackup'])->name('system.backups.show');
    Route::get('/system/backups/{backup}/restore', [SystemHealthController::class, 'restoreForm'])->name('system.backups.restore');
    Route::post('/system/backups/{backup}/restore', [SystemHealthController::class, 'restore'])->name('system.backups.restore.run');
    Route::delete('/system/backups/{backup}', [SystemHealthController::class, 'destroyBackup'])->name('system.backups.destroy');
    Route::get('/system/alerts', [SystemHealthController::class, 'alerts'])->name('system.alerts');
    Route::post('/system/alerts/{alert}/resolve', [SystemHealthController::class, 'resolveAlert'])->name('system.alerts.resolve');
    Route::get('/system/journal', [SystemHealthController::class, 'journal'])->name('system.journal');

    Route::get('/settings/company', [SettingController::class, 'company'])->name('settings.company');
    Route::put('/settings/company', [SettingController::class, 'updateCompany'])->name('settings.company.update');
    Route::get('/settings/stores', [SettingController::class, 'stores'])->name('settings.stores');
    Route::post('/settings/stores', [SettingController::class, 'storeStore'])->name('settings.stores.store');
    Route::put('/settings/stores/{store}', [SettingController::class, 'updateStore'])->name('settings.stores.update');
    Route::delete('/settings/stores/{store}', [SettingController::class, 'destroyStore'])->name('settings.stores.destroy');
    Route::get('/settings/{section}', [SettingController::class, 'section'])->name('settings.section');
    Route::put('/settings/{section}', [SettingController::class, 'updateSection'])->name('settings.section.update');

    // Multi-boutiques
    Route::get('/stores', [StoreController::class, 'dashboard'])->name('stores.dashboard');
    Route::get('/stores/list', [StoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/export', [StoreController::class, 'export'])->name('stores.export');
    Route::get('/stores/print', [StoreController::class, 'print'])->name('stores.print');
    Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
    Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
    Route::post('/stores/switch-all', [StoreController::class, 'switchAll'])->name('stores.switch-all');
    Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');
    Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
    Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
    Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
    Route::post('/stores/{store}/deactivate', [StoreController::class, 'deactivate'])->name('stores.deactivate');
    Route::post('/stores/{store}/activate', [StoreController::class, 'activate'])->name('stores.activate');
    Route::post('/stores/{store}/switch', [StoreController::class, 'switch'])->name('stores.switch');
    Route::get('/stores/{store}/print', [StoreController::class, 'printOne'])->name('stores.print-one');

    // Multi-entreprises
    Route::get('/companies', [CompanyController::class, 'dashboard'])->name('companies.dashboard');
    Route::get('/companies/list', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/export', [CompanyController::class, 'export'])->name('companies.export');
    Route::get('/companies/print', [CompanyController::class, 'print'])->name('companies.print');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::post('/companies/{company}/deactivate', [CompanyController::class, 'deactivate'])->name('companies.deactivate');
    Route::post('/companies/{company}/activate', [CompanyController::class, 'activate'])->name('companies.activate');
    Route::post('/companies/{company}/archive', [CompanyController::class, 'archive'])->name('companies.archive');
    Route::post('/companies/{company}/switch', [CompanyController::class, 'switch'])->name('companies.switch');
    Route::post('/companies/{company}/set-primary', [CompanyController::class, 'setPrimary'])->name('companies.set-primary');
    Route::get('/companies/{company}/print', [CompanyController::class, 'printOne'])->name('companies.print-one');

    // Centre de notifications
    Route::get('/notifications', [NotificationController::class, 'dashboard'])->name('notifications.dashboard');
    Route::get('/notifications/list', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/{notification}/archive', [NotificationController::class, 'archive'])->name('notifications.archive');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Gestion documentaire (DMS)
    Route::get('/documents', [DocumentController::class, 'dashboard'])->name('documents.dashboard');
    Route::get('/documents/browse', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/upload', [DocumentController::class, 'uploadForm'])->name('documents.upload');
    Route::post('/documents/upload', [DocumentController::class, 'upload'])->name('documents.store');
    Route::post('/documents/folders', [DocumentController::class, 'storeFolder'])->name('documents.folders.store');
    Route::put('/documents/folders/{folder}', [DocumentController::class, 'renameFolder'])->name('documents.folders.rename');
    Route::delete('/documents/folders/{folder}', [DocumentController::class, 'destroyFolder'])->name('documents.folders.destroy');
    Route::get('/documents/related/{type}/{id}', [DocumentController::class, 'related'])->name('documents.related');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::post('/documents/{document}/rename', [DocumentController::class, 'rename'])->name('documents.rename');
    Route::post('/documents/{document}/move', [DocumentController::class, 'move'])->name('documents.move');
    Route::post('/documents/{document}/favorite', [DocumentController::class, 'favorite'])->name('documents.favorite');
    Route::post('/documents/{document}/archive', [DocumentController::class, 'archive'])->name('documents.archive');
    Route::post('/documents/{document}/restore', [DocumentController::class, 'restore'])->name('documents.restore');

    // Journal d'audit & historique
    Route::get('/audit', [AuditController::class, 'dashboard'])->name('audit.dashboard');
    Route::get('/audit/events', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/export', [AuditController::class, 'export'])->name('audit.export');
    Route::get('/audit/export-pdf', [AuditController::class, 'exportPdf'])->name('audit.export-pdf');
    Route::get('/audit/print', [AuditController::class, 'print'])->name('audit.print');
    Route::get('/audit/purge', [AuditController::class, 'purgeForm'])->name('audit.purge');
    Route::post('/audit/purge', [AuditController::class, 'purge'])->name('audit.purge.run');
    Route::get('/audit/{audit}', [AuditController::class, 'show'])->name('audit.show');
    Route::get('/audit/{audit}/print', [AuditController::class, 'printOne'])->name('audit.print-one');
});
