<?php

use App\Http\Controllers\Services\AePS\CommissionController;
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
    return ['Laravel' => app()->version()];
});

Route::get('test', function () {
    $user = User::find('9b0b02eb-df1e-4616-bcb3-43fa5e26d5b7');
    $class = new CommissionController();
    $commision = $class->distributeCommission($user, 'CW', 500);
    return $commision;
});

require __DIR__ . '/auth.php';
