<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', fn () => Inertia::render('order/auth/Login'))->name('login');

Route::get('/', fn () => Inertia::render('order/Dashboard'))->name('dashboard');

Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', fn () => Inertia::render('order/orders/Index'))->name('index');

    Route::get('/create', fn () => Inertia::render('order/orders/Create'))->name('create');

    Route::get('/{order}', fn (string $order) => Inertia::render('order/orders/Show', [
        'orderNumber' => $order,
    ]))->name('show');

    Route::get('/{order}/reorder', fn (string $order) => Inertia::render('order/orders/Reorder', [
        'orderNumber' => $order,
    ]))->name('reorder');
});
