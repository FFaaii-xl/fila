<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Custom Login Page (standalone blade, not Livewire)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Custom Login Handler
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'name' => ['required'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/admin');
    }

    return back()->withErrors([
        'login' => 'Username atau password yang diberikan tidak cocok dengan data kami.',
    ]);
})->name('login.post');

// Admin / Operations Routes (Protected)
Route::middleware(['web', 'auth'])->group(function () {
    // Excel Upload & Matrix
    Route::post('/admin/penjualan/upload', [\App\Http\Controllers\UploadPenjualanController::class, 'upload'])->name('admin.penjualan.upload');
    Route::get('/admin/penjualan/template', [\App\Http\Controllers\UploadPenjualanController::class, 'downloadTemplate'])->name('admin.penjualan.template');
    Route::post('/admin/penjualan/pull-template', [\App\Http\Controllers\UploadPenjualanController::class, 'pullLastTemplate'])->name('admin.penjualan.pull-template');
    Route::post('/admin/penjualan/save-draft', [\App\Http\Controllers\UploadPenjualanController::class, 'saveDraft'])->name('admin.penjualan.save-draft');
    Route::post('/admin/penjualan/lock-draft', [\App\Http\Controllers\UploadPenjualanController::class, 'lockDraft'])->name('admin.penjualan.lock-draft');
    Route::post('/admin/penjualan/save-sort', [\App\Http\Controllers\UploadPenjualanController::class, 'saveSortOrder'])->name('admin.penjualan.save-sort');
    Route::get('/admin/penjualan/sync', [\App\Http\Controllers\UploadPenjualanController::class, 'sync'])->name('admin.penjualan.sync');

    // Nota Printing
    Route::get('/admin/print-nota', [\App\Http\Controllers\NotaController::class, 'print'])->name('admin.nota.print');
    Route::get('/admin/nota/backup/download', [\App\Http\Controllers\NotaController::class, 'downloadBackup'])->name('admin.nota.backup.download');

    // Legacy Converter
    Route::post('/admin/legacy/convert', [\App\Http\Controllers\LegacyConverterController::class, 'convert'])->name('admin.legacy.convert');
    Route::post('/admin/legacy/merge', [\App\Http\Controllers\LegacyConverterController::class, 'merge'])->name('admin.legacy.merge');

    // Tabungan Finalize
    Route::post('/admin/tabungan/finalize', [\App\Http\Controllers\Admin\TabunganController::class, 'finalize'])->name('admin.tabungan.finalize');
    Route::post('/admin/tabungan/preview-finalize', [\App\Http\Controllers\Admin\TabunganController::class, 'previewFinalize'])->name('admin.tabungan.preview-finalize');
    Route::get('/admin/tabungan/export', [\App\Http\Controllers\Admin\TabunganController::class, 'exportExcel'])->name('admin.tabungan.export');
});
