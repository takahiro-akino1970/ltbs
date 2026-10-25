<?php

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

$page = static fn (string $component, array $props = []): View => view('app', [
    'page' => $component,
    'props' => $props,
]);

Route::get('/login', fn () => $page('order/auth/Login'))->name('login');

Route::get('/', fn () => $page('order/Dashboard'))->name('dashboard');

Route::prefix('orders')->name('orders.')->group(function () use ($page): void {
    Route::get('/', fn () => $page('order/orders/Index'))->name('index');

    Route::get('/create', fn () => $page('order/orders/Create'))->name('create');

    Route::get('/{order}', fn (string $order) => $page('order/orders/Show', [
        'orderNumber' => $order,
    ]))->name('show');

    Route::get('/{order}/reorder', fn (string $order) => $page('order/orders/Reorder', [
        'orderNumber' => $order,
    ]))->name('reorder');
});
