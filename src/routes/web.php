<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceHistoryController;
use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminRequestController;




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
// 会員登録画面表示・登録処理
Route::get('/register', [RegisterController::class, 'registerView'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])
    ->name('register.store');

// メール認証関連
Route::get('/email/verify', [RegisterController::class, 'showVerifyNotice'])
    ->name('verification.notice'); // ← ★auth外に出す

Route::get('/email/verify/{id}/{hash}', [RegisterController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [RegisterController::class, 'resendVerification'])
    ->name('verification.send');

Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('email');


// -------------------------------------
// 一般ユーザー用ページ（メール認証必須）
// -------------------------------------
Route::middleware(['auth', 'email'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::get('/attendance/list', [AttendanceHistoryController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceHistoryController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceHistoryController::class, 'update'])->name('attendance.update');

    Route::get('/stamp_correction_request/list', [AttendanceRequestController::class, 'requestList'])
        ->name('stamp.request.list');
});

// -------------------------------------
// 管理者用ログイン（メール認証不要）
// -------------------------------------
Route::get('/admin/login', [AdminLoginController::class, 'loginView'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'store']);

// -------------------------------------
// 管理者用ページ（authのみ）
// -------------------------------------
Route::middleware(['auth', 'admin.auth'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [AdminController::class, 'index'])->name('admin.attendance.list');
    Route::get('/attendance/{id}', [AdminController::class, 'show'])->name('admin.attendance.detail');
    Route::post('/attendance/{id}', [AdminController::class, 'update'])->name('admin.attendance.update');

    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('admin.staff.list');
    Route::get('/attendance/staff/{id}', [AdminStaffController::class, 'attendanceIndex'])->name('admin.attendance.staff');

    // 申請一覧（管理者）
    Route::get(
        '/stamp_correction_request/list',
        [AdminRequestController::class, 'requestIndex']
    )->name('admin.request.list');

    // 申請承認画面（GET）
    Route::get(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminRequestController::class, 'approveShow']
    )->name('admin.stamp.approve.show');

    // 申請承認更新（POST）
    Route::post(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminRequestController::class, 'approveUpdate']
    )->name('admin.stamp.approve.update');
});
