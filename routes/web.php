<?php

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

Route::get('test1', function () {
    $payout = Payout::where('reference_id', 'PAY-663F0BBACA709')->first();
    return $payout->metadata['error']['txnid'];
});

require __DIR__ . '/auth.php';
