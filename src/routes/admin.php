<?php

use App\Http\Controllers\Admin\CustomerController;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

$page = static fn (string $component, array $props = []): View => view('app', [
    'page' => $component,
    'props' => $props,
]);

Route::get('/login', fn () => $page('admin/auth/Login'))->name('login');

Route::get('/', fn () => $page('admin/Dashboard'))->name('dashboard');

Route::get('/orders', fn () => $page('admin/orders/Index'))->name('orders.index');

Route::get('/orders/{order}', fn (string $order) => $page('admin/orders/Show', [
    'orderNumber' => $order,
]))->name('orders.show');

Route::get('/operations', fn () => $page('admin/operations/Index'))->name('operations.index');

Route::get('/customers', function () use ($page): View {
    $status = request()->query('status', 'active');

    return $page('admin/customers/Index', [
        'filters' => [
            'keyword' => trim((string) request()->query('keyword', '')),
            'status' => in_array($status, ['active', 'inactive', 'all'], true) ? $status : 'active',
        ],
    ]);
})->name('customers.index');

Route::prefix('api/admin')->name('api.admin.')->group(function (): void {
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customer-options', [CustomerController::class, 'options'])->name('customer-options.index');
});
