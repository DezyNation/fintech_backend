<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public static function store(User $user, string $reference_id, string $service, string $description, float $credit_amount, float $debit_amount, array $response)
    {
        $auth_user = auth()->user();
        $closing_balance = $user->wallet + $credit_amount - $debit_amount;
        Transaction::create([
            'user_id' => $user->id,
            'updated_by' => $auth_user->id,
            'triggered_by' => $auth_user->id,
            'reference_id' => $reference_id,
            'service' => $service,
            'description' => $description,
            'credit_amount' => $credit_amount,
            'debit_amount' => $debit_amount,
            'opening_balance' => $user->wallet,
            'closing_balance' => $closing_balance,
            'metadata' => json_encode($response)
        ]);

        $user->wallet = $closing_balance;
        $user->save();
    }
}
