<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(string $user_id, string $reference_id)
    {
        $auth = auth()->user();
        Transaction::create([
            'user_id' => $user_id,
            'updated_by' => $auth->id,
            'triggered_by' => $auth->id,
            'reference_id' => $reference_id,
            'service' => '',
            'description' => '',
            'credit_amount' => '',
            'debit_amount' => '',
            'opening_balance' => '',
            'closing_balance' => '',
            'metadata' => ''
        ]);
    }
}
