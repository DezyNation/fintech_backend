<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Services\Bbps\FlowController as BbpsFlowController;
use App\Http\Controllers\Dashboard\User\UserController;
use App\Http\Controllers\Dashboard\Admin\BankController;
use App\Http\Controllers\Dashboard\Admin\PlanController;
use App\Http\Controllers\Dashboard\Admin\AdminController;
use App\Http\Controllers\Dashboard\Admin\ReportController;
use App\Http\Controllers\Dashboard\Admin\WebsiteController;
use App\Http\Controllers\Dashboard\Admin\CommissionController;
use App\Http\Controllers\Dashboard\User\FundRequestController;
use App\Http\Controllers\Dashboard\Admin\UserController as AdminUserController;
use App\Http\Controllers\Services\Payout\FlowController as PayoutFlowController;
use App\Http\Controllers\Dashboard\User\ReportController as UserReportController;
use App\Http\Controllers\Dashboard\Admin\FundRequestController as AdminFundRequestController;
use App\Http\Controllers\Dashboard\User\AddressController;
use App\Http\Controllers\Dashboard\User\OnboardController;
use App\Http\Controllers\Services\Payout\CallbackController;

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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('services', [WebsiteController::class, 'services']);
Route::get('banks', [BankController::class, 'activeBanks']);

/**************** User Routes ****************/
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::prefix('transaction')->middleware('onboard_active')->group(function () {
        Route::post('payout', [PayoutFlowController::class, 'store']);
        Route::post('bbps', [BbpsFlowController::class, 'store']);
    });

    Route::prefix('onboard')->controller(OnboardController::class)->group(function () {
        Route::get('eko', 'ekoOnboard')->middleware('profile');
    });
    Route::apiResource('fund-requests', FundRequestController::class);
    Route::apiResource('address', AddressController::class);
    Route::get('wallet', [UserController::class, 'wallet']);
    Route::get('permissions', [UserController::class, 'permissions']);
    Route::put('update', [UserController::class, 'updateProfile']);
    Route::post('document', [UserController::class, 'uploadDocument']);
    Route::put('credential', [UserController::class, 'updateCredential']);

    Route::prefix('reports')->group(function () {
        Route::apiResource('ledger', UserReportController::class);
        Route::get('payout', [PayoutFlowController::class, 'index']);
        Route::post('export', [UserReportController::class, 'export']);
    });
});

/**************** Admin Routes ****************/
Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('fund-requests', AdminFundRequestController::class);
    Route::post('funds/assign-request', [AdminFundRequestController::class, 'assignRequest']);

    Route::group(['prefix' => 'manage-access'], function () {
        Route::put('update-role', [AdminController::class, 'updateRole']);
        Route::put('sync-user-permissions/{user}', [AdminController::class, 'updateUserPermission']);
        Route::put('sync-role-permissions/{role}', [AdminController::class, 'updateRolePermission']);
        Route::get('role-permissions/{role}', [AdminController::class, 'rolePermissions']);
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
        Route::post('export', [UserReportController::class, 'export']);
    });

    Route::group(['prefix' => 'manage-user'], function () {
        Route::apiResource('users', AdminUserController::class);
        Route::post('update-user/{user}', [AdminUserController::class, 'update']);
        Route::put('send-credentials/{user}', [AdminUserController::class, 'sendCredential']);
        Route::put('restore/{id}', [AdminUserController::class, 'restore']);
        Route::get('permissions/{user}', [AdminUserController::class, 'userPermissions']);
        Route::post('document/{user}', [AdminUserController::class, 'uploadDocument']);
    });

    Route::get('document/{path}', [AdminUserController::class, 'downloadDocument']);

    Route::apiResource('plans', PlanController::class);

    Route::prefix('commissions')->group(function () {
        Route::get('get-commission/{id}', [CommissionController::class, 'getCommission']);
        Route::post('create-commission', [CommissionController::class, 'createCommission']);
        Route::put('update-commission/{id}', [CommissionController::class, 'updateCommission']);
    });

    Route::prefix('transactions')->group(function () {
        Route::put('payout/{id}', [PayoutFlowController::class, 'update']);
    });
});

Route::prefix('callback/payout')->controller(CallbackController::class)->group(function () {
    Route::post('eko', 'eko');
});
