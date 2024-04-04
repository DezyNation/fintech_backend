<?php

namespace App\Http\Controllers\Services\BBPS;

use App\Http\Controllers\Controller;
use App\Http\Requests\BbpsTransactionRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaysprintController extends Controller
{
    public function operatorList(Request $request): Response
    {
        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/getoperator', ['mode' => $request->mode]);

        return $response;
    }

    public function fetchBill(Request $request): Response
    {
        $data = [
            'operator' => $request->operatorId,
            'canumber' => $request->utilityAccNo,
            'mode' => 'online'
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/fetchbill', $data);

        return $response;
    }

    public function payBill(BbpsTransactionRequest $request, string $reference_id): Response
    {
        $data = [
            'mode' => 'online',
            'referenceid' => $reference_id,
            'operator' => $request->operator_id,
            'canumber' => $request->utility_number,
            'amount' => $request->amount,
            'latitude' => $request->latitude, //divide
            'longitude' => $request->longitude,  //divide
            'bill_fetch' => $request->bill_response
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/paybill', $data);

        return $response;
    }
}
