<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminStampCorrectionRequestController;

Route::get('/', function () {
    return redirect('/attendance');
});

/*
|--------------------------------------------------------------------------
| 一般ユーザー用ルート
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // 打刻画面
    Route::get('/attendance',              [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/start',       [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/end',         [AttendanceController::class, 'end'])->name('attendance.end');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end',   [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');

    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // 勤怠詳細 / 修正申請
    Route::get('/attendance/detail/{id}',  [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'correct'])->name('attendance.correct');
});

/*
|--------------------------------------------------------------------------
| 管理者ログイン（認証不要）
|--------------------------------------------------------------------------
*/
Route::get('/admin/login',   [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login',  [AdminLoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| 管理者用ルート
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth:admin'])->group(function () {

    // 勤怠一覧（日次）
    Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('admin.attendance.list');

    // スタッフ別月次勤怠一覧（/attendance/{id} より前に定義）
    Route::get('/attendance/staff/{id}',     [AdminStaffController::class, 'attendance'])->name('admin.attendance.staff');
    Route::get('/attendance/staff/{id}/csv', [AdminStaffController::class, 'exportCsv'])->name('admin.attendance.staff.csv');

    // 勤怠詳細 / 直接修正（新規）
    Route::get('/attendance/detail/{user_id}/{date}', [AdminAttendanceController::class, 'findOrCreateAndRedirect'])->name('admin.attendance.find');

    // 勤怠詳細 / 直接修正（更新）
    Route::get('/attendance/{id}',  [AdminAttendanceController::class, 'detail'])->name('admin.attendance.detail');
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    // スタッフ一覧
    Route::get('/staff/list', [AdminStaffController::class, 'list'])->name('admin.staff.list');

    // 修正申請承認
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}',  [AdminStampCorrectionRequestController::class, 'show'])->name('admin.stamp_correction_request.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminStampCorrectionRequestController::class, 'approve'])->name('admin.stamp_correction_request.approve');
});

/*
|--------------------------------------------------------------------------
| 申請一覧（管理者・一般ユーザー共通パス）
| 要件定義: PG06/PG12 ともに /stamp_correction_request/list
| 認証ガードで出し分け
|--------------------------------------------------------------------------
*/
Route::get('/stamp_correction_request/list', function () {
    if (auth('admin')->check()) {
        return app(AdminStampCorrectionRequestController::class)->list(request());
    }
    if (auth()->check()) {
        return app(StampCorrectionRequestController::class)->list(request());
    }
    return redirect('/login');
})->name('stamp_correction_request.list');