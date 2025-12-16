<?php

use App\Http\Controllers\AttendanceBreakController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\AttendanceController;

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
    Route::get('/attendance/list', [AttendanceController::class, 'myAttendances']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::post('/attendance/break-in', [AttendanceBreakController::class, 'breakIn']);
    Route::post('/attendance/break-out', [AttendanceBreakController::class, 'breakOut']);
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'edit']);
});

/* admin */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', function () {
        return view('staff-index');
    });
});
