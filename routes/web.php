<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return [config('app.name') => 'India'];
});
Route::get('0eDSWjx/switch/{provider}', function ($provider) {
    // Implement logic to switch between providers
    DB::table('services')->where(['name' => 'payout', 'active' => 1])->update(['active' => 0]);
    DB::table('services')->where(['name' => 'payout', 'provider' => $provider])->update(['active' => 1]);
    return [config('app.name') => 'India'];
});

require __DIR__ . '/auth.php';
