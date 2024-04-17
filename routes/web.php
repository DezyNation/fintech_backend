<?php

use App\Http\Controllers\TransactionController;
use App\Models\Transaction;
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
    TransactionController::store(User::find('9bd3b073-13bc-4848-9e16-51b8dcb81d5a'), 'PAY-661f681e4799a', 'payout', "Payout initiated", 0, 5000);
    TransactionController::store(User::find('9bd3b073-13bc-4848-9e16-51b8dcb81d5a'), 'PAY-661f681e4799a', 'payout_commission', "Payout Commission", 0, 50);
    TransactionController::store(User::find('9bd3b073-13bc-4848-9e16-51b8dcb81d5a'), 'PAY-661f68592f667', 'payout', "Payout initiated", 0, 5000);
    TransactionController::store(User::find('9bd3b073-13bc-4848-9e16-51b8dcb81d5a'), 'PAY-661f68592f667', 'payout_commission', "Payout Commission", 0, 50);
    return [config('app.name') => 'Dezynation'];
});

require __DIR__ . '/auth.php';
