<?php

use App\Http\Controllers\Dashboard\Admin\FundRequestController as AdminFundRequestController;
use App\Http\Controllers\Dashboard\User\FundRequestController;
use App\Http\Controllers\Dashboard\User\UserController;
use App\Http\Controllers\Services\AePS\EkoController;
use App\Http\Controllers\Services\BBPS\EkoController as BBPSEkoController;
use App\Http\Controllers\Services\BBPS\PaysprintController;
use App\Http\Controllers\Services\DMT\EkoController as DMTEkoController;
use App\Http\Controllers\Services\DMT\PaysprintController as DMTPaysprintController;
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
Route::post('bbps', [PaysprintController::class, 'payBill']);
Route::post('dmt', [DMTPaysprintController::class, 'addRecipient']);

/**************** User Routes ****************/
Route::group(['prefix' => 'user', 'middleware' => ['auth:api']], function () {
    Route::apiResource('fund-requests', FundRequestController::class);
    Route::get('wallet', [UserController::class, 'wallet']);
});

/**************** Admin Routes ****************/
Route::group(['prefix' => 'admin'], function () {
    Route::apiResource('fund-requests', AdminFundRequestController::class);
    Route::post('funds/assign-request', [AdminFundRequestController::class, 'assignRequest']);
});
