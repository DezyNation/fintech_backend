<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PaydeerController extends Controller
{
    /**
     * Initiate a payout transaction.
     *
     * @param PayoutRequest $request The request instance containing all the necessary data for the transaction.
     * @return Response The response instance from the paydeer API.
     */

    public function initiateTransaction(PayoutRequest $request): array
    {
        if (!Cache::has('paydeer-token')) {
            $token = $this->paydeerToken();
            Cache::put('paydeer-token', $token['data']['access_token']);
        }

        $token = Cache::get('paydeer-token');

        $data = [
            'name' => $request->beneficiary_name,
            'email' => $request->user()->email,
            'mobile' => $request->user()->phone_number,
            'address' => $request->user()->address ?? 'Dubai',
            'amount' => $request->amount,
            'reference' => uniqid('PAYO-PD'),
            'trans_mode' => $request->mode,
            'account' => $request->account_number,
            'ifsc' => $request->ifsc_code
        ];
        $response = Http::withHeader('Authorization', 'Bearer ' . $token)
            ->post('https://paydeer.in/API/public/api/v1.1/payoutTransaction/async', $data);

        $array = [
            'status' => $response['status'] ?? 'error',
            'message' => $response['message'] ?? $response['data']['message'],
            'transaction_status' => $response['data']['status']
        ];

        return ['response' => $response, 'metadata' => $array];
    }
}
