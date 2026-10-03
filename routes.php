<?php

use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Http\Controllers\MonitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MonitorController::class, 'index'])->name('index');
Route::get('/create', [MonitorController::class, 'create'])->name('create');
Route::post('/', [MonitorController::class, 'store'])->name('store');
Route::get('/{monitor}', [MonitorController::class, 'show'])->name('show');
Route::get('/{monitor}/edit', [MonitorController::class, 'edit'])->name('edit');
Route::put('/{monitor}', [MonitorController::class, 'update'])->name('update');
Route::delete('/{monitor}', [MonitorController::class, 'destroy'])->name('destroy');
Route::post('/{monitor}/toggle', [MonitorController::class, 'toggle'])->name('toggle');
Route::post('/{monitor}/check', [MonitorController::class, 'check'])->name('check');
