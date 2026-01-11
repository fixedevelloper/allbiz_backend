<?php

use App\Http\Controllers\web\AuthController;
use App\Http\Controllers\web\CategoryController;
use App\Http\Controllers\web\HookController;
use App\Http\Controllers\web\OrderController;
use App\Http\Controllers\web\ProductController;
use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::middleware('auth')->group(function() {
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');


Route::get('/dashboard', [HookController::class, 'dashboard'])->name('dashboard');
Route::get('/countries', [HookController::class, 'index'])->name('countries.index');
Route::get('/countries/create', [HookController::class, 'createCountry'])->name('countries.create');
Route::post('/countries', [HookController::class, 'storeCountry'])->name('countries.store');

Route::get('/operators/create', [HookController::class, 'createOperator'])->name('operators.create');
Route::post('/operators', [HookController::class, 'storeOperator'])->name('operators.store');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
Route::post('/products', [ProductController::class, 'store'])->name('products.store');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::resource('categories', CategoryController::class);
Route::resource('products', ProductController::class);
Route::get('/orders', [OrderController::class, 'index'])
    ->name('orders.index');

Route::get('/orders/{order}', [OrderController::class, 'show'])
    ->name('orders.show');
Route::delete('/orders/{order}', [OrderController::class, 'show'])
    ->name('orders.destroy');
Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])
    ->name('orders.updateStatus');
});
