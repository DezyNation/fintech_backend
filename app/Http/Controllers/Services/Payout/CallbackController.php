<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallbackController extends Controller
{
    public function eko(Request $request)
    {
        $response = DB::transaction(function () use ($request) {
            $transaction = Transaction::where('reference_id', $request->client_ref_id)->firstOrFail();
            $lock = $this->lockRecords($transaction->user_id);

            if (!$lock->get()) {
                throw new HttpResponseException(response()->json(['data' => ['message' => "Failed to acquire lock"]], 423));
            }

            if ($request['tx_status'] == 1) {
                Payout::where('reference_id', $transaction->reference_id)->update([
                    'status' => 'failed'
                ]);
                TransactionController::reverseTransaction($transaction->reference_id);
            }

            $lock->release();
            return response("Success", 200);
        }, 2);

        return $response;
    }
}
