<?php

use App\Http\Controllers\Dashboard\Admin\ReportController;
use App\Http\Controllers\Services\AePS\CommissionController;
use App\Http\Controllers\Services\Payout\FlowController;
use App\Http\Controllers\Services\Payout\PaydeerController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['NXGenius' => 'v1'];
});


require __DIR__ . '/auth.php';
