<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\PayoutRequest;
use App\Models\Payout;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AeronpayController extends Controller
{

    public function errorHandling(Response $response, string $reference_id, $request = '')
    {
        if ($response->failed()) {
            Log::info(['arnp_resp' => $response->body()]);
            Log::info(['arnp_request' => $request]);
            Payout::where('reference_id', $reference_id)->delete();
            TransactionController::reverseTransaction($reference_id);
            abort(400, $response['description'] ?? "Unknown error occured");
        }
    }

    public function initiateTransaction(PayoutRequest $request, string $reference_id)
    {
        $data = [
            'accountNumber' => 7017601927,
            'amount' => $request['amount'],
            'client_referenceId' => $reference_id,
            'transferMode' => $request['mode'],
            'remarks' => 'process salary',
            'bankProfileId' => 1,
            'bankid' => $this->fetchBankId($request['ifsc_code']),
            'latitude' => '77.25382',
            'longitude' => '28.40082',
            'beneDetails' => [
                'bankAccount' => $request['account_number'],
                'ifsc' => $request['ifsc_code'],
                'name' => $request['beneficiary_name'],
                'email' => $request->user()->email,
                'phone' => $request->user()->phone_number,
                'address1' => 'VIJAYANAGAR GHAZIABAD'
            ]
        ];

        $response = Http::withHeaders(
            ['client-id' => config('services.aeronpay.client_id'), 'client-secret' => config('services.aeronpay.client_secret')]
        )->asJson()
            ->post(config('services.aeronpay.base_url') . '/api/payout/imps', $data);

        $this->errorHandling($response, $reference_id, $data);
        unset($data);
        return $this->processResponse($response);
    }

    public function updateTransaction(string $reference_id)
    {
        $parameters = [
            'client_referenceId' => $reference_id,
            'mobile' => '7017601927'
        ];

        $response = Http::withHeaders(
            ['client-id' => config('services.aeronpay.client_id'), 'client-secret' => config('services.aeronpay.client_secret')]
        )->asJson()
            ->post(config('services.aeronpay.base_url') . '/api/reports/transactionStatus', $parameters);

        return $this->updateResponse($response);
    }

    public function updateResponse($response)
    {
        if (in_array($response['statusCode'], [400, 200, 201])) {
            $data = [
                'status' => 'success',
                'message' => $response['description'],
                'utr' => $response['utr'] ?? null,
                'transaction_status' => strtolower($response['status'])
            ];
        } else {
            Log::info(['resp' => $response->body()]);
            $data = [
                'status' => 'error',
                'message' => $response['description'],
            ];
        }

        return ['data' => $data, 'response' => $response->body()];
    }

    public function processResponse($response)
    {
        $status = strtolower($response['status']);
        if (in_array($status, ['pending', 'success', 'initiated'])) {
            $data = [
                'status' => 'success',
                'message' => $response['message'] ?? "Transaction has been initiated.",
                'utr' => $response['data']['utr'] ?? null,
                'transaction_status' => $status
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => $response['message'] ?? "An error occurred while processing your request",
            ];
        }

        return ['data' => $data, 'response' => $response->body()];
    }

    public function fetchBankId(string $ifsc)
    {
        $bank_code = strtoupper(substr($ifsc, 0, 4));
        $response = Http::withHeaders(
            ['client-id' => config('services.aeronpay.client_id'), 'client-secret' => config('services.aeronpay.client_secret')]
        )->asJson()
            ->post('https://api.aeronpay.in/api/serviceapi-prod/api/payout/bankid_list', ['category' => 'bankids']);

        $bank_id = collect($response['data'])->firstWhere('bank_code', $bank_code)['bank_id'] ?? null;
        return $bank_id;
    }
}
