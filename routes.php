<?php

use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Http\Controllers\MonitorController;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Names are set before the routes are added: with a cached route table the collection
// indexes a dynamically added route by the name it has at that moment.
Route::name('index')->get('/', [MonitorController::class, 'index']);
Route::name('create')->get('/create', [MonitorController::class, 'create']);
Route::name('store')->post('/', [MonitorController::class, 'store']);

Route::name('webhooks.index')->get('/webhooks', [WebhookController::class, 'index']);
Route::name('webhooks.store')->post('/webhooks', [WebhookController::class, 'store']);
Route::name('webhooks.rotate')->post('/webhooks/{webhook}/rotate', [WebhookController::class, 'rotate']);
Route::name('webhooks.destroy')->delete('/webhooks/{webhook}', [WebhookController::class, 'destroy']);

Route::name('show')->get('/{monitor}', [MonitorController::class, 'show']);
Route::name('edit')->get('/{monitor}/edit', [MonitorController::class, 'edit']);
Route::name('update')->put('/{monitor}', [MonitorController::class, 'update']);
Route::name('destroy')->delete('/{monitor}', [MonitorController::class, 'destroy']);
Route::name('toggle')->post('/{monitor}/toggle', [MonitorController::class, 'toggle']);
Route::name('check')->post('/{monitor}/check', [MonitorController::class, 'check']);
