<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    $response = Http::get('https://backend.greenfieldorg.com/receive');
    return $response;
    return [config('app.name') => 'Dezynation'];
});

Route::get('receive', function (Request $request) {
    return ['ip' => $request->ip()];
});

require __DIR__ . '/auth.php';
