<?php

namespace App\Http\Controllers\Services\DMT;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EkoController extends Controller
{
    public function customerInfo(Request $request): Response
    {
        $customer_id = $request->customerId;
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code ?? 20810200
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v2/customers/mobile_number:$customer_id", $data);

        return $response;
    }

    public function createCustomer(Request $request)
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code ?? 20810200,
            'name' => $request->name,
            'dob' => $request->dob,
            'residence_address' => json_encode($request->address),
            'skip_verification' => $request->verification
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->put("https://staging.eko.in/ekoapi/v2/customers/mobile_number:{$request->phoneNumber}", $data);

        return $response;
    }

    public function verifyCustomer(Request $request): Response
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code ?? 20810200,
            'customer_id_type' => 'mobile_number',
            'customer_id' => $request->phoneNumber
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->put("https://staging.eko.in/ekoapi/v2/customers/verification/otp:{$request->otp}", $data);

        return $response;
    }

    public function addRecipient(Request $request): Response
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code ?? 20810200,
            'bank_id' => $request->bankId,
            'recipient_name' => $request->recipientName,
            'recipient_mobile' => $request->recipientMobile,
            'recipient_type' => 3,
        ];
        $acc_ifsc = $request->accountNumber . '_' . $request->ifsc;

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->put("https://staging.eko.in/ekoapi/v2/customers/mobile_number:{$request->phoneNumber}/recipients/{$request->recipient_id_type}:$acc_ifsc", $data);

        return $response;
    }

    public function recipientList(int $customer_id): Response
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => auth()->user()->user_eko_code ?? 20810200
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v2/customers/mobile_number:$customer_id/recipients", $data);

        return $response;
    }

    public function reipientDetails(Request $request): Response
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code ?? 20810200
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v2/customers/mobile_number:{$request->phoneNumber}/recipients/recipient_id:{$request->recipintId}", $data);

        return $response;
    }

    public function initiateTransaction(Request $request): Response
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code ?? 20810200,
            'client_ref_id' => uniqid('DMT-MT'), //change it
            'timestamp' => now(),
            'currency' => 'INR',
            'recipient_id' => $request->recipientId,
            'amount' => $request->amount,
            'customer_id' => $request->customerId,
            'state' => $request->state,
            'channel' => $request->channel,
            'latlong' => $request->latlong
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asForm()
            ->post('https://staging.eko.in/ekoapi/v2/transactions', $data);

        return $response;
    }

    public function transactionInquiry(Request $request): Response
    {
        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code
        ];
        $transaction_id = $request->transactionId;

        $response = Http::withHeaders($this->ekoHeaders())
            ->get("https://staging.eko.in/ekoapi/v2/transactions/$transaction_id", $data);


        return $response;
    }

    public function initiateRefund(Request $request): Response
    {

        $data = [
            'initiator_id' => env('INITIATOR_ID'),
            'user_code' => $request->user()->user_eko_code,
            'state' => 1,
            'otp' => $request->otp
        ];

        $response = Http::withHeaders($this->ekoHeaders())
            ->post("https://staging.eko.in/ekoapi/v2/transactions/{$request->transactionId}/refund", $data);

        return $response;
    }

    public function resendRefundOtp(string $transaction_id)
    {
        $response = Http::withHeaders($this->ekoHeaders())
            ->post("https://staging.eko.in/ekoapi/transactions/{$transaction_id}/refund/otp", ['initiator_id' => env('INITIATOR_ID')]);

        return $response;
    }
}
