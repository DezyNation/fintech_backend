<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UniversepayController extends Controller
{

    public function login()
    {
        if (Cache::has('universepay_token')) {
            $token = Cache::get('universepay_token');
        } else {
            $data = [
                'email' => config('services.universepay.username'),
                'password' => config('services.universepay.password')
            ];

            $response = Http::asJson()->post(config('services.universepay.base_url').'/auth/login', data: $data);

            $token = $response['access_token'];
            Cache::put('universepay_token', $token, now()->addHours(1));
        }

        return $token;
    }

    public function processResponse($response)
    {
        Log::info('Universepay response: '.json_encode($response->body()));
        if($response['status'] == true)
        {
            if(strtolower($response['data']['data']['status']) == 'completed'){
                $status = 'success';
            } else {
                $status = strtolower($response['data']['data']['status']);
            }
            $data = [
                'status' => 'success',
                'message' => $response['data']['message'],
                'utr' => $response['data']['data']['transactionId'],
                'transaction_status' => $status,
                'reference_id' => $response['data']['data']['orderId']
            ];
        } else {
            $data = [
                'status' => 'failed',
                'message' => $response['message'],
                'utr' => null,
                'transaction_status' => null
            ];
        }
        return ['data' => $data, 'response' => $response->body()];
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id='test')
    {
        $token = $this->login();

        $data = [
            'amount' => $request->amount,
            'ifsc' => $request->ifsc_code,
            'accountno' => $request->account_number,
            'udf1' => $reference_id,
            'name' => $request->beneficiary_name,
            'paymode' => strtoupper($request->mode),
            'remarks' => $request->remarks ?? 'Payouts',
            'mode' => 'bank',
            'branch' => $request->bank_name ?? $request->ifsc_code
        ];

        $response = Http::asJson()->withHeader('authorization', "Bearer $token")->post(config('services.universepay.base_url').'/transfer', data: $data);

        return $this->processResponse($response);
    }

    public function updateTransaction(string $transaction_id)
    {
        $token = $this->login();

        $data = [
            'orderid' => $transaction_id,
        ];

        $response = Http::asJson()->withHeader('authorization', "Bearer $token")->post(config('services.universepay.base_url').'/status', data: $data);

        return $this->processResponse($response);
    }
}
