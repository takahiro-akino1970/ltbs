<?php

use App\Http\Controllers\Admin\CustomerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', fn () => Inertia::render('admin/auth/Login'))->name('login');

Route::get('/', fn () => Inertia::render('admin/Dashboard'))->name('dashboard');

Route::get('/orders', fn () => Inertia::render('admin/orders/Index'))->name('orders.index');

Route::get('/orders/{order}', fn (string $order) => Inertia::render('admin/orders/Show', [
    'orderNumber' => $order,
]))->name('orders.show');

Route::get('/operations', fn () => Inertia::render('admin/operations/Index'))->name('operations.index');

Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
