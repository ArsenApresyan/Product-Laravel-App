<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [ProductController::class, 'index']);

Route::get('/products/{product_id}', [ProductController::class, 'show'])->name('products.show');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/products', [AdminController::class, 'products'])->name('admin.products');
    Route::get('/admin/products/add', [AdminController::class, 'addProductForm'])->name('admin.add.product');
    Route::post('/admin/products/add', [AdminController::class, 'addProduct'])->name('admin.add.product.submit');
    Route::get('/admin/products/edit/{id}', [AdminController::class, 'editProduct'])->name('admin.edit.product');
    Route::post('/admin/products/edit/{id}', [AdminController::class, 'updateProduct'])->name('admin.update.product');
    Route::get('/admin/products/delete/{id}', [AdminController::class, 'deleteProduct'])->name('admin.delete.product');
});
