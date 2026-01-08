<?php

use App\Http\Controllers\web\HookController;
use Illuminate\Support\Facades\Route;


Route::get('/countries', [HookController::class, 'index'])->name('countries.index');

Route::get('/countries/create', [HookController::class, 'createCountry'])->name('countries.create');
Route::post('/countries', [HookController::class, 'storeCountry'])->name('countries.store');

Route::get('/operators/create', [HookController::class, 'createOperator'])->name('operators.create');
Route::post('/operators', [HookController::class, 'storeOperator'])->name('operators.store');
