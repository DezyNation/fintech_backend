<?php

namespace App\Http\Controllers\Services\Payout;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlipzikController extends Controller
{
    public function headers($data, string $path, string $query_string, string $method)
    {
        $timestamp = floor(microtime(true) * 1000);
        $secret_key = config('services.flipzik.client_secret');
        $message = $method . "\n" . $path . "\n" . $query_string . "\n" . $data . "\n" . $timestamp . "\n";
        $signature = hash_hmac('sha512', $message, $secret_key);

        return [
            'signature' => $signature,
            'X-Timestamp' => $timestamp,
            'access_key' => config('services.flipzik.client_id'),
        ];
    }

    public function processResponse($response)
    {
        if ($response['success'] == true) {
            $data = [
                'status' => 'success',
                'message' => $response['data']['acquirer_message'] ?? 'Transaction has been initiated.',
                'utr' => $response['data']['bank_reference_id'] ?? null,
                'transaction_status' => strtolower($response['data']['status'])
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => "An error occurred while processing your request"
            ];
            Log::info(['msg_fzik' => $response->body()]);
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function processUpdateResponse($response)
    {
        if ($response['success'] == true) {
            $data = [
                'status' => 'success',
                'message' => $response['data']['acquirer_message'] ?? 'Transaction has been initiated.',
                'utr' => $response['data']['bank_reference_id'] ?? null,
                'transaction_status' => strtolower($response['data']['status'])
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => "An error occurred while processing your request"
            ];
            Log::info(['msg_fzik' => $response->body()]);
        }

        return ['data' =>  $data, 'response' => $response->body()];
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id)
    {
        $match = ['neft' => 1, 'imps' => 3, 'rtgs' => 4];
        $data = [
            'address' => 'VIJAYANAGAR GHAZIABAD',
            'payment_type' => $match[strtolower($request->mode)],
            'amount' => $request->amount * 100,
            'name' => $request->beneficiary_name,
            'email' => $request->user()->email,
            'mobile_number' => $request->user()->phone_number,
            'account_number' => $request->account_number,
            'ifsc_code' => $request->ifsc_code,
            'merchant_order_id' => $reference_id
        ];

        $response = Http::withBasicAuth(config('services.flipzik.client_id'), config('services.flipzik.client_id'))
            ->withHeaders($this->headers(json_encode($data), '/api/v1/payout/process', '', 'POST'))->asJson()
            ->post(config('services.flipzik.base_url') . '/payout/process', $data);

        if ($response->failed()) {
            Log::info(['err_fzik' => $response->body()]);
            abort($response->status(), "Gateway Failure!");
        }

        return $this->processResponse($response);
    }

    public function updateTransaction(string $reference_id)
    {
        $data = [];
        $response = Http::withBasicAuth(config('services.flipzik.client_id'), config('services.flipzik.client_id'))
            ->withHeaders($this->headers(json_encode($data), "/api/v1/payout/$reference_id", '', 'POST'))->asJson()
            ->get(config('services.flipzik.base_url') . "/payout/$reference_id");

        if ($response->failed()) {
            Log::info(['err_fzik' => $response->body()]);
            abort($response->status(), "Gateway Failure!");
        }
    }

    public function verifySignature(Request $request)
    {
        $signingSecret = config('services.flipzik.endpoint_secret');
        $signatureHeader = $request->header('Signature');
        if (!$signatureHeader) return false;

        $elements = array_map(fn($e) => explode('=', $e, 2), explode(',', $signatureHeader));
        $timestamp = (int) collect($elements)->firstWhere(fn($e) => $e[0] === 't')[1] ?? 0;
        $signatures = collect($elements)->filter(fn($e) => $e[0] === 'v0')->pluck(1)->all();

        if (!$timestamp || empty($signatures)) return false;

        $expectedSignature = hash_hmac('sha256', "$timestamp." . $request->getContent(), $signingSecret);

        return collect($signatures)->contains(fn($sig) => hash_equals($expectedSignature, $sig));
    }
}
