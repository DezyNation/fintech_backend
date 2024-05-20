<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function eko(Request $request)
    {
        Log::info(['callback-eko' => $request->all()]);
        $response = DB::transaction(function () use ($request) {
            $transaction = Transaction::where('reference_id', $request->client_ref_id)->firstOrFail();
            $lock = $this->lockRecords($transaction->user_id);

            if (!$lock->get()) {
                throw new HttpResponseException(response()->json(['data' => ['message' => "Failed to acquire lock"]], 423));
            }

            if (in_array($request['tx_status'], [1, 4])) {
                if ($transaction->status == 'failed') {
                    return response("Success", 200);
                }
                TransactionController::reverseTransaction($transaction->reference_id);
                Payout::where('reference_id', $transaction->reference_id)->update([
                    'status' => 'failed',
                    'utr' => $request['bank_ref_num']
                ]);
            } elseif ($request['tx_status'] == 0) {
                Payout::where('reference_id', $transaction->reference_id)->update([
                    'status' => 'success',
                    'utr' => $request['bank_ref_num']
                ]);
            }


            $lock->release();
            return response("Success", 200);
        }, 2);

        return $response;
    }
}
