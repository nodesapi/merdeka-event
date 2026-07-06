<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\PublicController;
use App\Models\Competition;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::controller(PublicController::class)->group(function () {
    Route::get('/', 'home')->name('public.home');
    Route::get('/panitia', 'committee')->name('public.committee');
    Route::get('/lomba', 'competitions')->name('public.competitions');
    Route::get('/lomba/{competition:slug}', 'competitionShow')->name('public.competition.show');
    Route::get('/transparansi', 'finance')->name('public.finance');
    Route::get('/form-warga', 'familyForm')->name('public.family-form');
    Route::post('/form-warga', 'storeFamilyForm')->name('public.family-form.store');
    Route::get('/syarat-ketentuan', 'terms')->name('public.terms');
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin panel (auth + panitia/admin only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|panitia'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'dashboard')->name('dashboard');
        Route::view('/acara', 'admin.event')->name('event');
        Route::view('/panitia', 'admin.committee')->name('committee');
        Route::view('/lomba', 'admin.competitions')->name('competitions');
        Route::view('/warga', 'admin.residents')->name('residents');
        Route::view('/pendaftaran-warga', 'admin.family-submissions')->name('family-submissions');
        Route::get('/pendaftaran-warga/export', [ReportController::class, 'familySubmissions'])->name('family-submissions.export');
        Route::view('/transaksi', 'admin.transactions')->name('transactions');
        Route::get('/transaksi/export', [ReportController::class, 'transactions'])->name('transactions.export');
        Route::view('/pengaturan', 'admin.settings')->name('settings');
        Route::get('/lomba/{competition:slug}/peserta', function (Competition $competition) {
            return view('admin.participants', ['competition' => $competition]);
        })->name('participants');
    });
