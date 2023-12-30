<?php

namespace App\Http\Controllers\Services\BBPS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EkoController extends Controller
{
    public function categoryList(): Response
    {
        $response = Http::withHeader($this->ekoHeaders())->asJson()
            ->get('https://staging.eko.in/ekoapi/v2/billpayments/operators_category');

        return $response;
    }

    public function locationList(): Response
    {
        $response = Http::withHeader($this->ekoHeaders())->asJson()
            ->get('https://staging.eko.in/ekoapi/v2/billpayments/operators_location');

        return $response;
    }

    public function operatorList(Request $request): Response
    {
        $data = [
            'Location' => $request->location,
            'Category' => $request->category
        ];

        $response = Http::withHeader($this->ekoHeaders())->asJson()
            ->get('https://staging.eko.in/ekoapi/v2/billpayments/operators_location', $data);

        return $response;
    }

    public function operatorParams($id): Response
    {
        $response = Http::withHeader($this->ekoHeaders())->asJson()
            ->get("https://staging.eko.in/ekoapi/v2/billpayments/operators/$id");

        return $response;
    }

    public function fetchBill(Request $request): Response
    {
        $user = auth()->user();
        $data = [
            'user_code' => $user->eko_user_code,
            'cliend_ref_id' => uniqid('BBPS-FB'), //change it
            'sender_name' => $user->name,
            'operator_id' => $request->operatorId,
            'utility_acc_no' => $request->utilityAccNo,
            'confirmation_mobile_no' => $request->confirmationMobileNo,
            'source_ip' => $request->ip(),
            'hc_channel' => $request->hcChannel ?? 0,
            'latlong' => $request->latlong,
            //dob7
        ];

        $response = Http::withHeaders($this->ekoHeaders())->asJson()
            ->post('https://staging.eko.in/ekoapi/v2/billpayments/fetchbill', $data);

        return $response;
    }

    public function payBill(Request $request): Response
    {
        $user = auth()->user();
        $hash_data = [
            'utility_number' => $request->utilityAccNo,
            'amount' => $request->amount,
            'user_code' => $user->eko_user_code
        ];

        $data = [
            'user_code' => $user->eko_user_code,
            'cliend_ref_id' => uniqid('BBPS-PB'), //change it
            'sender_name' => $user->name,
            'operator_id' => $request->operatorId,
            'utility_acc_no' => $request->utilityAccNo,
            'confirmation_mobile_no' => $request->confirmationMobileNo,
            'source_ip' => $request->ip(),
            'latlong' => $request->latlong,
            'amount' => $request->amount,
            'billfetchresponse' => $request->bill
            //dob7
        ];

        $response = Http::withHeaders(array_merge($this->ekoHeaders(), $this->requestHash($hash_data)))->asJson()
            ->post('https://staging.eko.in/ekoapi/v2/billpayments/paybill', $data);

        return $response;
    }
}
