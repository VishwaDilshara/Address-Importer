<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressImportController;

Route::get('/', function () {
    return redirect('/address-import');
});

Route::prefix('address-import')->name('address-import.')->group(function () {
    Route::get('/', [AddressImportController::class, 'index'])->name('index');
    Route::post('/import', [AddressImportController::class, 'import'])->name('import');
    Route::get('/processing/{batchId}', [AddressImportController::class, 'processing'])->name('processing');
    Route::get('/progress/{batchId}', [AddressImportController::class, 'progress'])->name('progress');
    Route::post('/insert', [AddressImportController::class, 'insertToDatabase'])->name('insert');
});
