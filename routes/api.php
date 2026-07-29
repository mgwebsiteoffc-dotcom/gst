<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1')->group(function () {
    Route::get('documents/{type}', [DocumentController::class, 'index']);
    Route::post('documents', [DocumentController::class, 'store']);
    Route::get('document/{document}', [DocumentController::class, 'show']);
    Route::get('reports/gstr1', [ReportController::class, 'gstr1']);
    Route::get('reports/gstr3b', [ReportController::class, 'gstr3b']);
});
