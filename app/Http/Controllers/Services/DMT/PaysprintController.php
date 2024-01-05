<?php

namespace App\Http\Controllers\Services\DMT;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaysprintController extends Controller
{
    public function customerInfo(Request $request): Response
    {
        $data = [
            'mobile' => $request->mobileNo,
            'bank3_flag' => $request->bank3Flag
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/remitter/queryremitter', $data);

        return $response;
    }

    public function createCustomer(Request $request): Response
    {
        $data = [
            'mobile' => $request->mobile,
            'firstname' => $request->firstName,
            'lastname' => $request->lastName,
            'address' => $request->address,
            'otp' => $request->otp,
            'pincode' => $request->pincode,
            'stateresp' => $request->stateresp,
            'bank3_flag' => $request->bank3Flag,
            'dob' => $request->dob,
            'gst_state' => $request->gstState,
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/remitter/registerremitter', $data);

        return $response;
    }

    public function addRecipient(Request $request): Response
    {
        $data = [
            'mobile' => $request->mobile,
            'benename' => $request->recipientName,
            'address' => $request->address,
            'bankid' => $request->bankId,
            'accno' => $request->accNo,
            'ifsccode' => $request->ifsc,
            'verified' => 0,
            'pincode' => $request->pincode,
            'dob' => $request->dob,
            'gst_state' => $request->gstState,
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary', $data);

        return $response;
    }

    public function recipientList(Request $request): Response
    {
        $data = [
            'mobile' => $request->mobile
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary/fetchbeneficiary', $data);

        return $response;
    }

    public function reipientDetails(Request $request): Response
    {
        $data = [
            'mobile' => $request->mobile,
            'beneid' => $request->beneId
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/beneficiary/registerbeneficiary/fetchbeneficiarybybeneid', $data);

        return $response;
    }

    public function initiateTransaction(Request $request): Response
    {
        $data = [
            'mobile' => $request->customerId,
            'referenceid' => uniqid('DMT-MT'), //change it
            'pipe' => 'bank1|bank2|bank3',
            'pincode' => $request->pinCode,
            'address' => $request->address,
            'dob' => $request->dob,
            'gst_state' => 07,
            'bene_id' => $request->recipientId,
            'txntype' => $request->txnType,
            'amount' => $request->amount
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/transact/transact', $data);

        return $response;
    }

    public function transactionInquiry(Request $request): Response
    {
        $transaction_id = $request->transactionId;

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/transact/transact/querytransact', ['referenceid' => $transaction_id]);

        return $response;
    }

    public function initiateRefund(Request $request): Response
    {
        $data = [
            'referenceid' => $request->ransactionId,
            'ackno' => $request->ackno
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/refund/refund/resendotp', $data);

        return $response;
    }

    public function claimRefund(Request $request): Response
    {
        $data = [
            'referenceid' => $request->ransactionId,
            'ackno' => $request->ackno,
            'otp' => $request->otp
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/dmt/refund/refund', $data);

        return $response;
    }
}
