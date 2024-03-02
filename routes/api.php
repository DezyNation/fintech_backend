<?php

use App\Http\Controllers\Dashboard\Admin\AdminController;
use App\Http\Controllers\Dashboard\Admin\BankController;
use App\Http\Controllers\Dashboard\Admin\CommissionController;
use App\Http\Controllers\Dashboard\Admin\FundRequestController as AdminFundRequestController;
use App\Http\Controllers\Dashboard\Admin\PlanController;
use App\Http\Controllers\Dashboard\Admin\ReportController;
use App\Http\Controllers\Dashboard\Admin\UserController as AdminUserController;
use App\Http\Controllers\Dashboard\Admin\WebsiteController;
use App\Http\Controllers\Dashboard\User\FundRequestController;
use App\Http\Controllers\Dashboard\User\ReportController as UserReportController;
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
Route::get('services', [WebsiteController::class, 'services']);
Route::get('banks', [BankController::class, 'activeBanks']);

/**************** User Routes ****************/
Route::group(['prefix' => 'user', 'middleware' => ['auth:api', 'role:retailer|distributor|super_distributor']], function () {
    Route::apiResource('fund-requests', FundRequestController::class);
    Route::get('wallet', [UserController::class, 'wallet']);
    Route::post('update', [UserController::class, 'updateProfile']);

    Route::group(['prefix' => 'report'], function () {
        Route::apiResource('ledger', UserReportController::class);
        Route::get('payout', [UserReportController::class, 'payouts']);
    });
});

/**************** Admin Routes ****************/
Route::group(['prefix' => 'admin', 'role:admin'], function () {
    Route::apiResource('fund-requests', AdminFundRequestController::class);
    Route::post('funds/assign-request', [AdminFundRequestController::class, 'assignRequest']);

    Route::group(['prefix' => 'roles'], function () {
        Route::put('update-role', [AdminController::class, 'updateRole']);
        Route::put('sync-user-permissions', [AdminController::class, 'updateUserPermission']);
        Route::put('sync-role-permissions', [AdminController::class, 'updateRolePermission']);
        Route::get('permissions', [AdminController::class, 'permissions']);
        Route::get('roles', [AdminController::class, 'roles']);
    });

    Route::group(['prefix' => 'controls'], function () {
        Route::put('services/{service}', [WebsiteController::class, 'updateService']);
        Route::post('services', [WebsiteController::class, 'storeService']);
        Route::apiResource('bank', BankController::class);
    });

    Route::group(['prefix' => 'report'], function () {
        Route::apiResource('ledgers', ReportController::class);
        Route::get('daily-sales', [ReportController::class, 'dailySales']);
        Route::get('payout', [ReportController::class, 'payoutReports']);
    });

    Route::group(['prefix' => 'manage-user'], function () {
        Route::apiResource('users', AdminUserController::class);
        Route::post('update-user/{user}', [AdminUserController::class, 'update']);
        Route::put('send-credentials/{user}', [AdminUserController::class, 'sendCredential']);
        Route::put('restore/{id}', [AdminUserController::class, 'restore']);
        Route::post('document/{user}', [AdminUserController::class, 'uploadDocument']);
    });

    Route::get('document/{path}', [AdminUserController::class, 'downloadDocument']);

    Route::apiResource('plans', PlanController::class);

    Route::group(['prefix' => 'commissions'], function () {
        Route::get('get-commission/{id}', [CommissionController::class, 'getCommission']);
        Route::post('create-commission', [CommissionController::class, 'createCommission']);
        Route::put('update-commission/{id}', [CommissionController::class, 'updateCommission']);
    });
});
