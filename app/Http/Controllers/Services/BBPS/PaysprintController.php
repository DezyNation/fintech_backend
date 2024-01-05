<?php

namespace App\Http\Controllers\Services\BBPS;

use App\Http\Controllers\Controller;
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
            'canumber' => $request->canumber,
            'mode' => 'online'
        ];

        $response = Http::withHeaders($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/fetchbill', $data);

        return $response;
    }

    public function payBill(Request $request): Response
    {
        $data = [
            'mode' => 'online',
            'referenceid' => uniqid('BBPS-PB'),
            'operator' => $request->operatorId,
            'canumber' => $request->canumber,
            'amount' => $request->amount,
            'latitude' => $request->latlong, //divide
            'longitude' => $request->latlong,  //divide
            'bill_fetch' => $request->bill
        ];

        $response = Http::withHeader($this->paysprintHeaders())->asJson()
            ->post('https://paysprint.in/service-api/api/v1/service/bill-payment/bill/paybill', $data);

        return $response;
    }
}
