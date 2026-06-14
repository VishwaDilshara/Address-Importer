<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressImportController;

Route::get('/', function () {
    return redirect('/address-import');
});

Route::prefix('address-import')->name('address-import.')->group(function () {
    Route::get('/', [AddressImportController::class, 'index'])->name('index');
    Route::post('/import', [AddressImportController::class, 'import'])->name('import');
    Route::post('/insert', [AddressImportController::class, 'insertToDatabase'])->name('insert');
});
