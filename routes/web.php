<?php

use App\Http\Controllers\Services\Payout\AeronpayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [config('app.name') => 'Dezynation India'];
});


Route::get('test', [AeronpayController::class, 'fetchBankId']);

require __DIR__ . '/auth.php';
