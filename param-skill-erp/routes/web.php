<?php

use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->prefix('admin/exports')->group(function () {
    Route::get('centres.csv', [ExportController::class, 'centresCsv'])->name('exports.centres.csv');
    Route::get('centres.pdf', [ExportController::class, 'centresPdf'])->name('exports.centres.pdf');
    Route::get('employees.csv', [ExportController::class, 'employeesCsv'])->name('exports.employees.csv');
    Route::get('employees.pdf', [ExportController::class, 'employeesPdf'])->name('exports.employees.pdf');
});
