<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogoutController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('staff-index');
// });

/* 未ログイン */

Route::get('/login', fn() => view('auth.login'))->name('login')->middleware('redirect.role');
Route::get('/admin/login', fn() => view('auth.login'))->name('admin.login')->middleware('redirect.role');
Route::post('/logout', LogoutController::class)->name('logout');


/* staff */
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/attendance', function () {
        return view('attendance-status');
    });
});

/* admin */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', function () {
        return view('staff-index');
    });
});
