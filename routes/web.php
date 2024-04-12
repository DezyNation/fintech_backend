<?php

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
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
    $data = [
        'initiator_id' => config('services.eko.initiator_id'),
        'user_code' => config('services.eko.developer_key'),
        'service_code' => $service_code = 4
    ];
    $controller = new Controller;
    $response = Http::withHeaders($controller->ekoHeaders())->asForm()
        ->put(config('services.eko.base_url') . '/v1/user/service/activate', $data);

    return $response;
    return [config('app.name') => 'Dezynation'];
});

require __DIR__ . '/auth.php';
