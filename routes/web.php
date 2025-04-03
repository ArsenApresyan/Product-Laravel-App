<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [ProductController::class, 'index']);

Route::get('/products/{product_id}', [ProductController::class, 'show'])->name('products.show');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('products', AdminController::class)->except(['show']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');