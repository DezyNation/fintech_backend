<?php

use App\Http\Controllers\Services\AePS\EkoController;
use App\Http\Controllers\Services\BBPS\EkoController as BBPSEkoController;
use App\Http\Controllers\Services\DMT\EkoController as DMTEkoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('aeps', [EkoController::class, 'aepsTransaction']);
Route::post('bbps', [BBPSEkoController::class, 'payBill']);
Route::post('dmt', [DMTEkoController::class, 'initiateTransaction']);
