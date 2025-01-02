<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GroscopeController extends Controller
{

    public function processResponse(Response $response, int $status): array
    {
        switch ($status) {
            case 0005:
                if (in_array(strtolower($response['statusCode']), ['pending', 'success'])) {
                    $data = [
                        'status' => 'success',
                        'message' => $response['msg'],
                        'utr' => null,
                        'transaction_status' => strtolower($response['statusCode'])
                    ];
                } else {
                    $data = [
                        'status' => 'failed',
                        'message' => $response['msg'],
                        'transaction_status' => strtolower($response['statusCode']),
                        'utr' => null
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['msg'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function updateResponse(Response $response, int $status): array
    {
        switch ($status) {
            case true:
                if (in_array(strtolower($response['data']['status']), ['pending', 'success'])) {
                    $data = [
                        'status' => 'success',
                        'message' => $response['msg'],
                        'utr' => $response['data']['utr_number'],
                        'transaction_status' => strtolower($response['data']['status'])
                    ];
                } else {
                    $data = [
                        'status' => 'failed',
                        'message' => $response['msg'],
                        'utr' => $response['data']['utr_number'],
                        'transaction_status' => strtolower($response['data']['status'])
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['msg'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id)
    {
        $data = [
            'order_id' => $reference_id,
            'payment_mode' => $request->mode,
            'bank_name' => $request->bank_name,
            'account_holder_name' => $request->beneficiary_name,
            'account_number' => $request->account_number,
            'ifsc_code' => strtoupper($request->ifsc_code),
            'ip_address' => $request->ip()
        ];

        $response = Http::withHeaders([
            'X-Client-IP' => $_SERVER['SERVER_ADDR'],
            'X-Auth-Token' => config('services.groscope.token')
        ])->post(config('services.groscope.base_url' . '/payout'), $data);

        if ($response->failed()) {
            $this->releaseLock($request->user()->id);
            abort($response->status(), "Gateway Failure!");
        }

        return $this->processResponse($response, $response['status']);
    }

    public function updateTransaction(string $transaction_id)
    {
        $response = Http::withHeaders([
            'X-Client-IP' => $_SERVER['SERVER_ADDR'],
            'X-Auth-Token' => config('services.groscope.token')
        ])->post(config('services.groscope.base_url' . '/check-status'), ['transaction_id' => $transaction_id]);

        return $this->updateResponse($response, $response['status']);
    }
}