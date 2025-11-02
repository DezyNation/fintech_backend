<?php

namespace App\Http\Controllers\Services\Payout;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SddsplController extends Controller
{
    public function login()
    {
        $key = "login_token_sddspl";
        if (Cache::has($key)) {
            return Cache::get($key);
        } else {
            $data = [
                "username" => config("services.sddspl.username"),
                "password" => config("services.sddspl.password"),
                "otp" => "yes",
                "browser_id" => "52e49c456f92b900cf0ed2e20172a7c2",
                "lat" => "26.9194401",
                "long" => "75.7531271",
            ];

            $response = Http::withoutVerifying()->post(
                config("services.sddspl.base_url") . "/api/api-login",
                $data,
            );

            if ($response["status"] == true) {
                Cache::put(
                    $key,
                    $response["data"]["authorisation"]["token"],
                    now()->addHours(23),
                );
                return $response["data"]["authorisation"]["token"];
            } else {
                Log::info($response->body(), ['login_fail_sddspl']);
                abort(400, "Login failed");
            }
        }
    }

    public function initiateTransaction(
        PayoutRequest $request,
        string $reference_id,
    ) {
        $token = $this->login();
        $data = [
            "TRANSFER_TYPE_DESC" => strtoupper($request->mode),
            "BENE_BANK" => $request->bank_name ?? "HDFC Bank",
            "INPUT_DEBIT_AMOUNT" => $request->amount,
            "INPUT_VALUE_DATE" => today(),
            "TRANSACTION_TYPE" => "SINGLE",
            "BENE_ACC_NAME" => $request->beneficiary_name,
            "BENE_ACC_NO" => $request->account_number,
            "BENE_BRANCH" => "NA",
            "BENE_IDN_CODE" => $request->ifsc_code,
            "REMITTER_ID" => config("services.sddspl.remitter_id"),
        ];

        $beneficiary = $this->createBeneficiary($data);
        $data["REMITTER_BENE_ID"] = $beneficiary;

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->post(
                config("services.sddspl.base_url") .
                    "/api/hdfc/cbx-transaction-api",
                $data,
            );

        return $this->processResponse($response);
    }

    public function createBeneficiary($input)
    {
        $token = $this->login();
        $phone = auth()->user()->phone_number ?? "9971914198";
        $data = [
            "mobile" => $phone,
            "bank_name" => $input["BENE_BANK"],
            "bank_account_number" => $input["BENE_ACC_NO"],
            "bank_account_holder_name" => $input["BENE_ACC_NAME"],
            "bank_ifsc" => $input["BENE_IDN_CODE"],
            "BeneficiaryMobile" => $phone,
            "status" => 1,
        ];

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->post(
                config("services.sddspl.base_url") .
                    "/api/remitter-bank-details/add_bank",
                $data,
            );

        if ($response["status"] == true) {
            return $response["data"]["id"];
        } else {
            abort(400, $response["message"] ?? "Failure");
        }
    }

    public function processResponse($response)
    {
        Log::info($response, ['sddspl']);
        if ($response["status"] == true) {
            $data = [
                "status" => "success",
                "message" => $response["message"],
                "utr" => $response["data"]["utr_no"],
                "transaction_status" => strtolower($response["data"]["status"]),
            ];
        } else {
            $data = [
                "status" => "failed",
                "message" => $response["message"],
                "utr" => null,
                "transaction_status" => "failed",
            ];
        }

        return ["data" => $data, "response" => $response->body()];
    }
}
