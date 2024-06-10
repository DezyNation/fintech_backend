<?php

use App\Http\Controllers\Services\DMT\EkoController;
use App\Http\Controllers\Services\Payout\RblController;
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
    return [config('app.name') => 'Dezynation'];
});

Route::get('test', [RblController::class, 'initiateTransaction']);

require __DIR__ . '/auth.php';
