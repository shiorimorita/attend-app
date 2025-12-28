<?php

use App\Http\Controllers\AttendanceBreakController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

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
//     return view('attendance-daily-index');
// });

Route::middleware('auth')->group(function () {

    Route::get('/email/verify', fn() => view('auth.verify-email'))
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect('/admin/attendance/list');
        }

        return redirect('/attendance');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');
});

/* 未ログイン */

Route::get('/login', fn() => view('auth.login'))->name('login')->middleware('redirect.role');
Route::get('/admin/login', fn() => view('auth.login'))->name('admin.login')->middleware('redirect.role');
Route::post('/logout', LogoutController::class)->name('logout');

/* staff & admin 共通 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionController::class, 'index']);
});

/* staff */
Route::middleware(['auth', 'verified', 'role:staff'])->group(function () {
    Route::get('/attendance/list', [AttendanceController::class, 'myAttendances'])->name('attendance.list');
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::post('/attendance/break-in', [AttendanceBreakController::class, 'breakIn']);
    Route::post('/attendance/break-out', [AttendanceBreakController::class, 'breakOut']);
    Route::post('/attendance/detail/{id}', [AttendanceCorrectionController::class, 'store'])->name('attendance.correction.request');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'edit'])->name('attendance.detail');
});

/* admin */
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/attendance/list', [AttendanceController::class, 'dailyAttendance']);
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceCorrectionController::class, 'showApprove'])->name('stamp_correction_request.approve');
    Route::get('/admin/attendance/detail/{id}', [AttendanceController::class, 'edit']);
    Route::post('/admin/attendance/detail/{id}', [AttendanceController::class, 'store']);
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceCorrectionController::class, 'approve'])->name('stamp_correction_request.approve.post');
    Route::get('/admin/staff/list', [UserController::class, 'index']);
    Route::get('/admin/attendance/staff/{id}', [AttendanceController::class, 'staff'])->name('admin.attendance.staff');
    Route::get('/attendance/export-csv', [AttendanceController::class, 'exportCsv'])->name('attendance.export.csv');
});
