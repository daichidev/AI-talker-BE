<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\AdminUserController;
use App\Http\Controllers\admin\AdminReportController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains theweb middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Admin routes
Route::get('/admin/login', [AdminUserController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminUserController::class, 'login']);
Route::post('/admin/logout', [AdminUserController::class, 'logout'])->name('admin.logout');

// Protected admin routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/admin/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
});

// アカウント削除リクエスト用のルート
Route::get('/myai/delete-account', [UserController::class, 'getDeleteAccount'])->name('myai.get-delete-account');

Route::post('/myai/delete-account', [UserController::class, 'postDeleteAccount'])->name('myai.post-delete-account');