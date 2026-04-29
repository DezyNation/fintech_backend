<?php

use App\Models\Service;
use App\Services\FeeSettlementService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [config('app.name') => 'India'];
});

Route::get("deduct-money/{userId}", function ($userId) {
    $summary = app(FeeSettlementService::class)
        ->adjustSingleUserWithCap($userId, '2026-03-29', '2026-04-04', 10, 10000);
    return $summary;
    return [config('app.name') => 'India'];
});
Route::get("cache/{operation}", function ($operation) {
    if ($operation == "clear") {
        Artisan::call("optimize:clear");
        return ["status" => "optimization cleared"];
    } elseif ($operation == "optimize") {
        Artisan::call('optimize');
        return ["status" => "system optimized"];
    }
    return ["invalid arguments"];
});

require __DIR__ . "/auth.php";
