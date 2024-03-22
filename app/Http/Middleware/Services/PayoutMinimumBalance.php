<?php

namespace App\Http\Middleware\Services;

use App\Http\Controllers\Services\Payout\CommissionController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayoutMinimumBalance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $minimum_balance = $user->capped_balance;
        $wallet_balance = $user->wallet;

        $transaction_amount = $request->amount;

        $commission_class = new CommissionController;
        $calculations = $commission_class->distributeCommission($user, $request->amount, false, true);
        $commission = $calculations['credit_amount'] - $calculations['debit_amount'];

        $lumpsum = $transaction_amount + $commission;
        $balance_left = $wallet_balance - $lumpsum;
        $required_amount = $minimum_balance + $lumpsum;

        if ($balance_left < $minimum_balance) {
            return response()->json(['message' => "Insufficient Balance! You need at least ₹ {$required_amount} to proceed with this transaction"], 403);
        }

        return $next($request);
    }
}
