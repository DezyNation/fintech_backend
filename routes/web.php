<?php

use App\Models\Service;
use App\Services\FeeSettlementService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    // return Service::all();
    $userId = 'a0798967-8aeb-441e-9e09-f825fede2c25';
    $summary = app(FeeSettlementService::class)
        ->adjustSingleUserWithCap($userId, '2026-03-29', '2026-04-04', 10, 10000);
    return $summary;
    return [config('app.name') => 'India'];
});
Route::get('0eDSWjx/switch/{provider}', function ($provider) {
    // Implement logic to switch between providers
    DB::table('services')->where(['name' => 'payout', 'active' => 1])->update(['active' => 0]);
    DB::table('services')->where(['name' => 'payout', 'provider' => $provider])->update(['active' => 1]);
    return [config('app.name') => 'India'];
});

require __DIR__ . '/auth.php';
