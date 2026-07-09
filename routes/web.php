<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\PaymentWebhookController;
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
    Route::get('/susunan-acara', 'schedule')->name('public.schedule');
    Route::get('/lomba', 'competitions')->name('public.competitions');
    Route::get('/lomba/{competition:slug}', 'competitionShow')->name('public.competition.show');
    Route::get('/transparansi', 'finance')->name('public.finance');
    Route::get('/form-warga', 'familyForm')->name('public.family-form');
    Route::post('/form-warga', 'storeFamilyForm')->name('public.family-form.store');
    Route::get('/form-warga/{submission:reference_code}/qris', 'qrisPayment')->name('public.qris-payment');
    Route::get('/form-warga/{submission:reference_code}/status', 'qrisStatus')->name('public.qris-status');
    Route::get('/form-warga/{submission:reference_code}/bukti', 'registrationReceipt')->name('public.registration-receipt');
    Route::get('/daftar-lomba', 'lombaForm')->name('public.lomba-register');
    Route::get('/daftar-lomba/cari', 'lombaLookup')->name('public.lomba-register.lookup');
    Route::post('/daftar-lomba', 'storeLombaForm')->name('public.lomba-register.store');
    Route::get('/syarat-ketentuan', 'terms')->name('public.terms');
    Route::get('/galeri', 'galeri')->name('public.galeri');
    Route::get('/galeri/thumb/{filename}', 'galeriThumbnail')->name('public.galeri.thumb');
});

/*
|--------------------------------------------------------------------------
| Payment webhook (PayHook / cekbayar.com) — publik, diverifikasi HMAC
|--------------------------------------------------------------------------
*/
Route::post('/webhook/payhook', [PaymentWebhookController::class, 'handle'])->name('webhook.payhook');

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
        Route::view('/susunan-acara', 'admin.schedule')->name('schedule');
        Route::view('/goody-bag', 'admin.goody-bag')->name('goody-bag');
        Route::view('/panitia', 'admin.committee')->name('committee');
        Route::view('/lomba', 'admin.competitions')->name('competitions');
        Route::get('/peserta', function () {
            $event = \App\Models\Event::where('status', 'active')->latest('start_date')->first()
                ?? \App\Models\Event::latest('start_date')->first();

            $competitions = $event
                ? $event->competitions()->withCount('participants')->orderBy('name')->get()
                : collect();

            $selected = $competitions->firstWhere('slug', request('lomba')) ?? $competitions->first();

            return view('admin.participants-index', compact('competitions', 'selected'));
        })->name('participants-index');
        Route::get('/peserta/export', [ReportController::class, 'participants'])->name('participants.export');
        Route::view('/warga', 'admin.residents')->name('residents');
        Route::get('/warga/export', [ReportController::class, 'residents'])->name('residents.export');
        Route::view('/pendaftaran-warga', 'admin.family-submissions')->name('family-submissions');
        Route::get('/pendaftaran-warga/export', [ReportController::class, 'familySubmissions'])->name('family-submissions.export');
        Route::view('/transaksi', 'admin.transactions')->name('transactions');
        Route::get('/transaksi/export', [ReportController::class, 'transactions'])->name('transactions.export');
        Route::view('/pengaturan', 'admin.settings')->name('settings');
        Route::view('/users', 'admin.users')->middleware('role:admin')->name('users');
        Route::get('/lomba/{competition:slug}/peserta', function (Competition $competition) {
            return view('admin.participants', ['competition' => $competition]);
        })->name('participants');
    });
