<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\SyncRunController;
use Illuminate\Support\Facades\Route;

Route::post('/sync', [SyncController::class, 'store'])->name('api.sync');

Route::get('/sync-runs', [SyncRunController::class, 'index'])->name('api.sync-runs.index');
Route::get('/sync-runs/{syncRun}', [SyncRunController::class, 'show'])->name('api.sync-runs.show');

Route::get('/assets', [AssetController::class, 'index'])->name('api.assets.index');
Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('api.assets.show');
Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('api.assets.destroy');

Route::get('/employees', [EmployeeController::class, 'index'])->name('api.employees.index');
Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('api.employees.show');
Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('api.employees.destroy');
