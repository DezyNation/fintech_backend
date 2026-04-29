<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FeeSettlementService
{
    public function adjustSingleUserWithCap(
        string $userId,
        string $startDate,
        string $endDate,
        float $percentage,
        float $targetAmount,
    ) {
        return DB::transaction(function () use (
            $userId,
            $startDate,
            $endDate,
            $percentage,
            $targetAmount,
        ) {
            $totalAdded = 0;
            $processedRefs = [];

            // Step 1: Fetch only this user's transactions
            $transactions = Transaction::where("user_id", $userId)
                ->whereBetween("created_at", [$startDate, $endDate])
                ->whereIn("service", ["payout", "payout_commission"])
                ->where("debit_amount", ">", 0)
                ->where("debit_amount", "<", 42500) // strictly < to allow room
                ->orderBy("created_at")
                ->lockForUpdate()
                ->get();

            $totalAdded = 0;
            $processedRefs = [];
            $affectedTransactions = [];
            $affectedMap = [];

            foreach ($transactions as $txn) {
                if ($totalAdded >= $targetAmount) {
                    break;
                }

                $oldDebit = $txn->debit_amount;

                $percentageIncrease = ($txn->debit_amount * $percentage) / 100;

                $remaining = $targetAmount - $totalAdded;
                $maxAllowedIncrease = 20000 - $txn->debit_amount;

                $increase = min(
                    $percentageIncrease,
                    $remaining,
                    $maxAllowedIncrease,
                );

                // skip if nothing can be added
                if ($increase <= 0) {
                    continue;
                }

                $txn->debit_amount += $increase;
                $txn->save();

                $totalAdded += $increase;

                // 🔥 Track affected txn
                $affectedTransactions[] = [
                    "id" => $txn->id,
                    "reference_id" => $txn->reference_id,
                    "old_debit_amount" => $oldDebit,
                    "new_debit_amount" => $txn->debit_amount,
                    "increase" => $increase,
                    "created_at" => $txn->created_at
                ];

                // avoid double payout update
                if (!in_array($txn->reference_id, $processedRefs)) {
                    Payout::where(
                        "reference_id",
                        $txn->reference_id,
                    )->increment("amount", $increase);

                    $processedRefs[] = $txn->reference_id;
                }
            }

            if ($totalAdded < $targetAmount) {
                return [
                    "success" => false,
                    "message" => "Target amount not reached",
                    "achieved" => $totalAdded,
                ];
            }

            // 🔥 Step 2: Rebuild ledger ONLY for this user

            // Get starting balance before range
            $prevClosing =
                Transaction::where("user_id", $userId)
                    ->where("created_at", "<", $startDate)
                    ->orderByDesc("created_at")
                    ->value("closing_balance") ?? 0;

            $allTransactions = Transaction::where("user_id", $userId)
                ->orderBy("created_at")
                ->lockForUpdate()
                ->get();

            $balanceChanges = [];

            foreach ($allTransactions as $txn) {
                $txn->opening_balance = $prevClosing;
                $oldOpening = $txn->opening_balance;
                $oldClosing = $txn->closing_balance;

                $txn->closing_balance =
                    $txn->opening_balance +
                    $txn->credit_amount -
                    $txn->debit_amount;

                $txn->save();

                // track only if txn was affected
                if (isset($affectedMap[$txn->id])) {
                    $balanceChanges[$txn->id] = [
                        "old_opening" => $oldOpening,
                        "new_opening" => $txn->opening_balance,
                        "old_closing" => $oldClosing,
                        "new_closing" => $txn->closing_balance,
                    ];
                }

                $prevClosing = $txn->closing_balance;
            }

            // 🔥 Step 3: Update wallet
            User::where("id", $userId)->update(["wallet" => $prevClosing]);

            return [
                "success" => true,
                "affected_transactions" => $affectedTransactions,
            ];
        });
    }
}
