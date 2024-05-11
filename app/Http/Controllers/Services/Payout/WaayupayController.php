<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WaayuPayController extends Controller
{

    public function processResponse(Response $response, bool $status): array
    {
        switch ($status) {
            case 'true':
                if (in_array($response['msg'], ['processing', 'success'])) {
                    $data = [
                        'status' => 'success',
                        'message' => $response['error']['message'],
                        'utr' => $response['error']['payoutid'],
                        'transaction_status' => strtolower($response['error']['status'])
                    ];
                } else {
                    $data = [
                        'status' => 'failed',
                        'message' => $response['error']['message'],
                        'transaction_status' => strtolower($response['error']['status']),
                        'utr' => $response['error']['payoutid']
                    ];
                }
                break;

            default:
                $data = [
                    'status' => 'error',
                    'message' => $response['error']['message'] ?? "An error occurred while processing your request",
                ];
                break;
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id): array | Exception
    {
        if (!Cache::has('waayupay-token')) {
            $token = $this->waayupayToken();
            Cache::put('waayupay-token', $token['token'], 600);
        }

        $token = Cache::get('waayupay-token');

        $data = [
            'name' => $request->beneficiary_name,
            'email' => $request->user()->email,
            'mobile' => $request->user()->phone_number,
            'amount' => $request->amount,
            'contactid' => $reference_id,
            'userKey' => config('services.waayupay.user_key'),
            'password' => config('services.waayupay.password'),
            'accountNumber' => $request->account_number,
            'ifscCode' => $request->ifsc_code
        ];
        $response = Http::withToken($token)
            ->withoutVerifying()
            ->post(config('services.waayupay.base_url') . '/payout/transaction', $data);

        return $this->processResponse($response, $response['status']);
    }
}
