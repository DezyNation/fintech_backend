<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public static function store(string $user_id, string $reference_id, string $service, string $description, float $credit_amount, float $debit_amount, array $response)
    {
        $user = auth()->user();
        $closing_balance = $user->balance + $credit_amount - $debit_amount;
        Transaction::create([
            'user_id' => $user_id,
            'updated_by' => $user->id,
            'triggered_by' => $user->id,
            'reference_id' => $reference_id,
            'service' => $service,
            'description' => $description,
            'credit_amount' => $credit_amount,
            'debit_amount' => $debit_amount,
            'opening_balance' => $user->balance,
            'closing_balance' => $closing_balance,
            'metadata' => json_encode($response)
        ]);

        User::where('id', $user->id)->update([
            'balance' => $closing_balance,
        ]);
    }
}
